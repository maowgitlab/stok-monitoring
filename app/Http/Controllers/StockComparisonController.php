<?php

namespace App\Http\Controllers;

use App\Models\StockHistory;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class StockComparisonController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        $tanggal = $request->input('tanggal');
        $spreadsheetData = $request->input('spreadsheet_data');
        $excelData = $request->input('excel_data');
        $results = [];
        $message = '';

        if ($request->isMethod('post') && $tanggal && ($spreadsheetData || $excelData)) {
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

                // Ambil konversi satuan dari StockService
                $minimalOrders = $this->stockService->getMinimalOrders();

                // Parse data spreadsheet
                $spreadsheetItems = [];
                if ($spreadsheetData) {
                    $spreadsheetLines = explode("\n", trim($spreadsheetData));
                    $headers = [];

                    foreach ($spreadsheetLines as $index => $line) {
                        $columns = array_map('trim', explode("\t", $line));
                        if ($index === 0) {
                            $headers = $columns;
                            Log::info("Header spreadsheet: " . json_encode($headers));
                            continue;
                        }
                        if (count($columns) < 7) {
                            Log::warning("Baris spreadsheet " . ($index + 1) . " tidak lengkap: " . json_encode($columns));
                            continue;
                        }
                        Log::info("Baris spreadsheet " . ($index + 1) . " raw: " . json_encode($columns));

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
                            Log::warning("Baris spreadsheet " . ($index + 1) . ": Saldo akhir negatif: nama={$nama}, saldo_akhir_raw={$saldoAkhirRaw}, saldo_akhir={$saldoAkhir}");
                            continue;
                        }
                        if (in_array($satuan, ['gr', 'lbr', 'ml']) && $saldoAkhir < 10) {
                            Log::warning("Baris spreadsheet " . ($index + 1) . ": Saldo akhir terlalu kecil untuk satuan {$satuan}: nama={$nama}, saldo_akhir_raw={$saldoAkhirRaw}, saldo_akhir={$saldoAkhir}");
                            continue;
                        }

                        Log::info("Baris spreadsheet " . ($index + 1) . ": nama={$nama}, satuan={$satuan}, saldo_akhir_raw={$saldoAkhirRaw}, saldo_akhir_clean={$saldoAkhirClean}, saldo_akhir_parsed={$saldoAkhir}");

                        if ($nama && isset($systemData[$nama])) {
                            $spreadsheetItems[$nama] = [
                                'saldo_akhir' => $saldoAkhir,
                                'satuan' => $satuan,
                            ];
                        } else {
                            Log::info("Baris spreadsheet " . ($index + 1) . " diabaikan: nama={$nama} tidak ada di data sistem");
                        }
                    }
                }

                Log::info("Spreadsheet items: " . json_encode(array_keys($spreadsheetItems)));

                // Parse data Excel
                $excelItems = [];
                if ($excelData) {
                    $excelLines = explode("\n", trim($excelData));
                    $headers = [];

                    foreach ($excelLines as $index => $line) {
                        $columns = array_map('trim', explode("\t", $line));
                        if ($index === 0) {
                            $headers = $columns;
                            Log::info("Header Excel: " . json_encode($headers));
                            continue;
                        }
                        if (count($columns) < 8) {
                            Log::warning("Baris Excel " . ($index + 1) . " tidak lengkap: " . json_encode($columns));
                            continue;
                        }
                        Log::info("Baris Excel " . ($index + 1) . " raw: " . json_encode($columns));

                        $nama = strtoupper(trim($columns[0])); // Nama Barang
                        $satuan = strtolower(trim($columns[1])); // Satuan
                        $stockAkhirRaw = $columns[7]; // Stock Akhir (mentah)

                        // Handle nilai kosong
                        if ($stockAkhirRaw === '-' || $stockAkhirRaw === '') {
                            $stockAkhirRaw = '0';
                        }

                        // Parse Stock Akhir: handle titik ribuan, koma desimal
                        $stockAkhirClean = preg_replace('/\.(?=\d{3})/', '', $stockAkhirRaw); // Hapus titik ribuan
                        $stockAkhirClean = str_replace(',', '.', $stockAkhirClean); // Koma → titik untuk desimal
                        $stockAkhirClean = preg_replace('/[^0-9.]/', '', $stockAkhirClean); // Hapus non-angka kecuali titik
                        $stockAkhir = floatval($stockAkhirClean);

                        // Konversi ke satuan kecil menggunakan minimalOrders
                        $conversion = $minimalOrders[$nama]['conversion'] ?? 1;
                        // Skip konversi kalau satuan Excel sama dengan satuan sistem (pcs, gr, lbr, porsi, ml)
                        if (in_array($satuan, ['pcs', 'gr', 'lbr', 'porsi', 'ml'])) {
                            $conversion = 1;
                        }
                        $stockAkhir *= $conversion;

                        // Validasi nilai
                        if ($stockAkhir < 0) {
                            Log::warning("Baris Excel " . ($index + 1) . ": Stock akhir negatif: nama={$nama}, stock_akhir_raw={$stockAkhirRaw}, stock_akhir={$stockAkhir}");
                            continue;
                        }

                        Log::info("Baris Excel " . ($index + 1) . ": nama={$nama}, satuan={$satuan}, stock_akhir_raw={$stockAkhirRaw}, stock_akhir_clean={$stockAkhirClean}, stock_akhir_parsed={$stockAkhir}, conversion={$conversion}");

                        if ($nama && isset($systemData[$nama])) {
                            $excelItems[$nama] = [
                                'saldo_akhir' => $stockAkhir,
                                'satuan' => $satuan,
                            ];
                        } else {
                            Log::info("Baris Excel " . ($index + 1) . " diabaikan: nama={$nama} tidak ada di data sistem");
                        }
                    }
                }

                Log::info("Excel items: " . json_encode(array_keys($excelItems)));

                // Bandingkan data
                foreach ($systemData as $nama => $systemItem) {
                    $spreadsheetItem = $spreadsheetItems[$nama] ?? null;
                    $excelItem = $excelItems[$nama] ?? null;
                    $satuan = $systemItem['satuan'];
                    $results[] = [
                        'nama' => $nama,
                        'satuan' => $satuan,
                        'saldo_sistem' => $systemItem['stok'],
                        'saldo_spreadsheet' => $spreadsheetItem ? $spreadsheetItem['saldo_akhir'] : 0,
                        'selisih_sistem_spreadsheet' => $spreadsheetItem ? ($systemItem['stok'] - $spreadsheetItem['saldo_akhir']) : $systemItem['stok'],
                        'saldo_excel' => $excelItem ? $excelItem['saldo_akhir'] : 0,
                        'selisih_sistem_excel' => $excelItem ? ($systemItem['stok'] - $excelItem['saldo_akhir']) : $systemItem['stok'],
                        'satuan_excel' => $excelItem ? $excelItem['satuan'] : '-',
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
            $message = 'Pilih tanggal dan masukkan data spreadsheet atau Excel terlebih dahulu.';
        }

        return view('stocks.compare', compact('tanggal', 'spreadsheetData', 'excelData', 'results', 'message'));
    }
}