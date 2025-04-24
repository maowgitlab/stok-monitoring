<?php

namespace App\Imports;

use App\Models\Item;
use App\Models\Import;
use App\Models\StockHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StockImport implements ToCollection, WithHeadingRow
{
    protected $importId;
    protected $tanggal;

    public function __construct($importId, $tanggal)
    {
        $this->importId = $importId;
        $this->tanggal = Carbon::parse($tanggal)->toDateString();
    }

    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        try {
            $dataCount = 0;

            Log::info("Memulai proses impor ID {$this->importId} untuk tanggal {$this->tanggal}");
            Log::info("Jumlah baris yang dibaca: " . $rows->count());

            // Cek apakah ada impor sukses di tanggal ini
            $existingImport = Import::where('tanggal_import', $this->tanggal)
                ->where('status', 'success')
                ->where('id', '!=', $this->importId)
                ->exists();

            if ($existingImport) {
                throw new \Exception("Data untuk tanggal {$this->tanggal} sudah diimpor.");
            }

            if ($rows->isEmpty()) {
                throw new \Exception("File Excel kosong, tidak ada data untuk diimpor.");
            }

            foreach ($rows as $index => $row) {
                // Normalisasi header ke lowercase dan trim spasi
                $row = $row->mapWithKeys(function ($value, $key) {
                    return [strtolower(trim($key)) => $value];
                });

                Log::info("Memproses baris " . ($index + 2) . ": " . json_encode($row->toArray()));

                // Validasi kolom
                if (!isset($row['kode']) || !isset($row['bahan_baku']) || !isset($row['stok']) || !isset($row['satuan'])) {
                    Log::warning("Baris " . ($index + 2) . " diabaikan karena kolom tidak lengkap: " . json_encode($row->toArray()));
                    continue;
                }

                // Validasi data
                $kode = trim($row['kode']);
                $bahanBaku = trim($row['bahan_baku']);
                $stokImpor = floatval($row['stok']);
                $satuan = trim($row['satuan']);

                if (empty($kode) || empty($bahanBaku) || empty($satuan)) {
                    Log::warning("Baris " . ($index + 2) . " diabaikan karena data kosong: kode={$kode}, bahan_baku={$bahanBaku}, satuan={$satuan}");
                    continue;
                }

                if ($stokImpor < 0) {
                    throw new \Exception("Stok tidak boleh negatif untuk item: {$bahanBaku} pada baris " . ($index + 2));
                }

                // Buat item baru (selalu)
                $item = Item::create([
                    'kode' => $kode,
                    'nama' => $bahanBaku,
                    'satuan' => $satuan,
                ]);

                Log::info("Item baru dibuat: id={$item->id}, kode={$kode}, nama={$bahanBaku}, satuan={$satuan}");

                // Cek histori impor untuk tanggal ini
                $existingHistory = StockHistory::where('item_id', $item->id)
                    ->where('tanggal', $this->tanggal)
                    ->whereNull('tipe')
                    ->where('is_archived', true)
                    ->first();

                if ($existingHistory) {
                    throw new \Exception("Impor untuk item {$bahanBaku} pada tanggal {$this->tanggal} sudah diarsipkan.");
                }

                // Buat histori (non-arsip)
                $stockHistory = StockHistory::create([
                    'item_id' => $item->id,
                    'tanggal' => $this->tanggal,
                    'qty' => $stokImpor,
                    'stok' => $stokImpor,
                    'tipe' => null,
                    'is_archived' => false,
                    'created_at' => now(),
                ]);

                Log::info("Impor diproses: id={$stockHistory->id}, item_id={$item->id}, nama={$bahanBaku}, tanggal={$this->tanggal}, stok={$stokImpor}");
                $dataCount++;
            }

            if ($dataCount === 0) {
                throw new \Exception("Tidak ada data valid untuk diimpor. Pastikan file berisi data dengan kolom kode, bahan_baku, stok, dan satuan.");
            }

            Import::find($this->importId)->update(['jumlah_data' => $dataCount]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal impor ID {$this->importId}: " . $e->getMessage());
            throw $e;
        }
    }
}