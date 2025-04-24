<?php

namespace App\Http\Controllers;

use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockController extends Controller
{
    public function pasteForm()
    {
        return view('stocks.paste');
    }

    public function processPaste(Request $request)
    {
        $request->validate(['pasted_data' => 'required|string'], [
            'pasted_data.required' => 'Data paste tidak boleh kosong.',
        ]);

        $rawData = trim($request->input('pasted_data'));
        Log::info("Raw pasted data (IN): " . $rawData);

        $lines = array_filter(explode("\n", $rawData));
        if (empty($lines)) {
            return redirect()->route('stocks.paste')->with('error', 'Data paste kosong atau format tidak valid.');
        }

        DB::beginTransaction();
        try {
            foreach ($lines as $index => $line) {
                $columns = preg_split("/\t+/", trim($line));
                Log::info("Baris " . ($index + 1) . ": " . json_encode($columns));

                if (count($columns) < 6) {
                    throw new \Exception("Format salah pada baris " . ($index + 1) . ": '$line'. Harus ada 6 kolom (Tanggal    Penerima    Tipe    Nama    Satuan    Qty).");
                }

                [$tanggal, $penerima, $tipe, $nama, $satuan, $qty] = $columns;

                try {
                    $tanggal = Carbon::createFromFormat('d/m/Y', $tanggal)->toDateString();
                } catch (\Exception $e) {
                    throw new \Exception("Tanggal salah pada baris " . ($index + 1) . ": '$line'. Gunakan format DD/MM/YYYY.");
                }

                $qty = floatval(str_replace(['.', ','], ['', '.'], $qty));
                if ($qty <= 0) {
                    throw new \Exception("Qty harus lebih dari 0 pada baris " . ($index + 1) . ": '$line'.");
                }

                // Cari item aktif berdasarkan nama, satuan, dan impor aktif
                $importHistory = StockHistory::whereNull('tipe')
                    ->where('is_archived', false)
                    ->where('tanggal', $tanggal)
                    ->whereHas('item', function ($query) use ($nama, $satuan) {
                        $query->whereRaw('TRIM(LOWER(nama)) = ?', [trim(strtolower($nama))])
                              ->whereRaw('TRIM(LOWER(satuan)) = ?', [trim(strtolower($satuan))]);
                    })
                    ->orderBy('tanggal', 'desc')
                    ->first();

                if (!$importHistory) {
                    throw new \Exception("Item '$nama' ($satuan) belum punya data impor aktif untuk tanggal $tanggal pada baris " . ($index + 1) . ".");
                }

                $item = $importHistory->item;
                Log::info("Item aktif ditemukan: id={$item->id}, nama={$item->nama}, satuan={$item->satuan}, impor_tanggal={$importHistory->tanggal}");

                $lastStock = StockHistory::where('item_id', $item->id)
                    ->where('tanggal', $tanggal)
                    ->orderBy('created_at', 'desc')
                    ->value('stok') ?? $importHistory->stok;

                $newStock = $lastStock + $qty;

                StockHistory::create([
                    'item_id' => $item->id,
                    'tanggal' => $tanggal,
                    'stok' => $newStock,
                    'qty' => $qty,
                    'tipe' => 'IN',
                    'pengirim_penerima' => $penerima,
                    'is_archived' => false,
                ]);

                $item->latestStock()->updateOrCreate(
                    ['item_id' => $item->id],
                    ['stok' => $newStock, 'tanggal' => $tanggal]
                );

                Log::info("Paste IN berhasil: item_id={$item->id}, tanggal={$tanggal}, qty={$qty}, stok={$newStock}, pengirim_penerima={$penerima}");
            }

            DB::commit();
            return redirect()->route('stocks.paste')->with('success', 'Data stok masuk berhasil diproses.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal paste IN: " . $e->getMessage());
            return redirect()->route('stocks.paste')->with('error', $e->getMessage());
        }
    }

    public function processPasteKeluar(Request $request)
    {
        $request->validate(['pasted_data' => 'required|string'], [
            'pasted_data.required' => 'Data paste tidak boleh kosong.',
        ]);

        $rawData = trim($request->input('pasted_data'));
        Log::info("Raw pasted data (OUT): " . $rawData);

        $lines = array_filter(explode("\n", $rawData));
        if (empty($lines)) {
            return redirect()->route('stocks.paste')->with('error', 'Data paste kosong atau format tidak valid.');
        }

        DB::beginTransaction();
        try {
            foreach ($lines as $index => $line) {
                $columns = preg_split("/\t+/", trim($line));
                Log::info("Baris " . ($index + 1) . ": " . json_encode($columns));

                if (count($columns) < 6) {
                    throw new \Exception("Format salah pada baris " . ($index + 1) . ": '$line'. Harus ada 6 kolom (Tanggal    Penerima    Tipe    Nama    Satuan    Qty).");
                }

                [$tanggal, $penerima, $tipe, $nama, $satuan, $qty] = $columns;

                try {
                    $tanggal = Carbon::createFromFormat('d/m/Y', $tanggal)->toDateString();
                } catch (\Exception $e) {
                    throw new \Exception("Tanggal salah pada baris " . ($index + 1) . ": '$line'. Gunakan format DD/MM/YYYY.");
                }

                $qty = floatval(str_replace(['.', ','], ['', '.'], $qty));
                if ($qty <= 0) {
                    throw new \Exception("Qty harus lebih dari 0 pada baris " . ($index + 1) . ": '$line'.");
                }

                // Cari item aktif berdasarkan nama, satuan, dan impor aktif
                $importHistory = StockHistory::whereNull('tipe')
                    ->where('is_archived', false)
                    ->where('tanggal', $tanggal)
                    ->whereHas('item', function ($query) use ($nama, $satuan) {
                        $query->whereRaw('TRIM(LOWER(nama)) = ?', [trim(strtolower($nama))])
                              ->whereRaw('TRIM(LOWER(satuan)) = ?', [trim(strtolower($satuan))]);
                    })
                    ->orderBy('tanggal', 'desc')
                    ->first();

                if (!$importHistory) {
                    throw new \Exception("Item '$nama' ($satuan) belum punya data impor aktif untuk tanggal $tanggal pada baris " . ($index + 1) . ".");
                }

                $item = $importHistory->item;
                Log::info("Item aktif ditemukan: id={$item->id}, nama={$item->nama}, satuan={$item->satuan}, impor_tanggal={$importHistory->tanggal}");

                $lastStock = StockHistory::where('item_id', $item->id)
                    ->where('tanggal', $tanggal)
                    ->orderBy('created_at', 'desc')
                    ->value('stok') ?? $importHistory->stok;

                $newStock = $lastStock - $qty;
                if ($newStock < 0) {
                    throw new \Exception("Stok tidak cukup untuk '$nama' pada tanggal $tanggal pada baris " . ($index + 1) . ". Stok saat ini: $lastStock.");
                }

                StockHistory::create([
                    'item_id' => $item->id,
                    'tanggal' => $tanggal,
                    'stok' => $newStock,
                    'qty' => $qty,
                    'tipe' => 'OUT',
                    'pengirim_penerima' => $penerima,
                    'is_archived' => false,
                ]);

                $item->latestStock()->updateOrCreate(
                    ['item_id' => $item->id],
                    ['stok' => $newStock, 'tanggal' => $tanggal]
                );

                Log::info("Paste OUT berhasil: item_id={$item->id}, tanggal={$tanggal}, qty={$qty}, stok={$newStock}, pengirim_penerima={$penerima}");
            }

            DB::commit();
            return redirect()->route('stocks.paste')->with('success', 'Data stok keluar berhasil diproses.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal paste OUT: " . $e->getMessage());
            return redirect()->route('stocks.paste')->with('error', $e->getMessage());
        }
    }
}