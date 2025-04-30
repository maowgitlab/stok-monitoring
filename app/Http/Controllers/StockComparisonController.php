<?php

namespace App\Http\Controllers;

use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class StockComparisonController extends Controller
{
    public function index(Request $request)
    {
        $tanggal = $request->input('tanggal');
        $spreadsheetData = $request->input('spreadsheet_data');
        $results = [];
        $message = '';

        if ($request->isMethod('post') && $tanggal && $spreadsheetData) {
            try {
                // Ambil data sistem dari StockHistory
                $systemData = StockHistory::where('tanggal', $tanggal)
                    ->whereNull('tipe') // Impor awal
                    ->where('is_archived', false)
                    ->with('item')
                    ->get()
                    ->mapWithKeys(function ($history) {
                        return [strtoupper(trim($history->item->nama)) => [
                            'stok' => floatval($history->stok),
                            'satuan' => strtolower(trim($history->item->satuan)),
                        ]];
                    })->toArray();

                Log::info("System data untuk tanggal {$tanggal}: " . json_encode(array_keys($systemData)));

                // Parse data spreadsheet
                $spreadsheetLines = explode("\n", trim($spreadsheetData));
                $spreadsheetItems = [];
                $headers = [];

                foreach ($spreadsheetLines as $index => $line) {
                    $columns = array_map('trim', explode("\t", $line));
                    if ($index === 0) {
                        $headers = $columns;
                        Log::info("Header spreadsheet: " . json_encode($headers));
                        continue;
                    }
                    if (count($columns) < 7) {
                        Log::warning("Baris " . ($index + 1) . " tidak lengkap: " . json_encode($columns));
                        continue; // Skip baris tidak lengkap
                    }
                    Log::info("Baris " . ($index + 1) . " raw: " . json_encode($columns));

                    $nama = strtoupper(trim($columns[0])); // NAMA BAHAN BAKU
                    $satuan = strtolower(trim($columns[1])); // SATUAN
                    $saldoAkhirRaw = $columns[6]; // SALDO AKHIR (mentah)

                    // Parse SALDO AKHIR: handle titik ribuan, koma desimal
                    $saldoAkhirClean = preg_replace('/\.(?=\d{3})/', '', $saldoAkhirRaw); // Hapus titik ribuan
                    $saldoAkhirClean = str_replace(',', '.', $saldoAkhirClean); // Koma → titik untuk desimal
                    $saldoAkhirClean = preg_replace('/[^0-9.]/', '', $saldoAkhirClean); // Hapus non-angka kecuali titik
                    $saldoAkhir = floatval($saldoAkhirClean);

                    // Normalisasi skala untuk pcs kalau punya desimal dan kecil
                    if ($satuan === 'pcs' && $saldoAkhir < 1000 && (strpos($saldoAkhirRaw, '.') !== false || strpos($saldoAkhirRaw, ',') !== false)) {
                        $saldoAkhir *= 1000; // Misal 6.156 → 6156
                    }

                    // Validasi nilai
                    if ($saldoAkhir < 0) {
                        Log::warning("Baris " . ($index + 1) . ": Saldo akhir negatif: nama={$nama}, saldo_akhir_raw={$saldoAkhirRaw}, saldo_akhir={$saldoAkhir}");
                        continue;
                    }
                    if (in_array($satuan, ['gr', 'lbr', 'ml']) && $saldoAkhir < 10) {
                        Log::warning("Baris " . ($index + 1) . ": Saldo akhir terlalu kecil untuk satuan {$satuan}: nama={$nama}, saldo_akhir_raw={$saldoAkhirRaw}, saldo_akhir={$saldoAkhir}");
                        continue;
                    }

                    Log::info("Baris " . ($index + 1) . ": nama={$nama}, satuan={$satuan}, saldo_akhir_raw={$saldoAkhirRaw}, saldo_akhir_clean={$saldoAkhirClean}, saldo_akhir_parsed={$saldoAkhir}");

                    if ($nama && isset($systemData[$nama])) {
                        $spreadsheetItems[$nama] = [
                            'saldo_akhir' => $saldoAkhir,
                            'satuan' => $satuan,
                        ];
                    } else {
                        Log::info("Baris " . ($index + 1) . " diabaikan: nama={$nama} tidak ada di data sistem");
                    }
                }

                Log::info("Spreadsheet items: " . json_encode(array_keys($spreadsheetItems)));

                // Bandingkan data
                foreach ($systemData as $nama => $systemItem) {
                    $spreadsheetItem = $spreadsheetItems[$nama] ?? null;
                    $satuan = $systemItem['satuan'];
                    $results[] = [
                        'nama' => $nama,
                        'satuan' => $satuan,
                        'saldo_sistem' => $systemItem['stok'],
                        'saldo_spreadsheet' => $spreadsheetItem ? $spreadsheetItem['saldo_akhir'] : 0,
                        'selisih' => $spreadsheetItem ? ($systemItem['stok'] - $spreadsheetItem['saldo_akhir']) : $systemItem['stok'],
                    ];
                }

                if (empty($results)) {
                    $message = 'Tidak ada data yang cocok untuk dibandingkan.';
                }

                Log::info("Hasil komparasi: " . json_encode($results));
            } catch (\Exception $e) {
                Log::error("Error komparasi stok: {$e->getMessage()}");         
                $message = 'Terjadi kesalahan saat memproses data: ' . $e->getMessage();
            }
        } elseif ($request->isMethod('post')) {
            $message = 'Pilih tanggal dan masukkan data spreadsheet terlebih dahulu.';
        }

        return view('stocks.compare', compact('tanggal', 'spreadsheetData', 'results', 'message'));
    }
}