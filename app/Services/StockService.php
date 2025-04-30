<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Item;
use App\Models\StockHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class StockService
{
    public function getAggregatedStockHistory(
        int $itemId,
        int $days = 30,
        $specificDate = null,
        bool $archived = false,
        ?Carbon $customStartDate = null,
        ?Carbon $customEndDate = null
    ): Collection
    {
        $endDate = $customEndDate ?? ($specificDate ? Carbon::parse($specificDate)->endOfDay() : Carbon::now()->endOfDay());
        $startDate = $customStartDate ?? $endDate->copy()->subDays($days)->startOfDay();

        Log::debug("getAggregatedStockHistory: item_id={$itemId}, startDate={$startDate->toDateString()}, endDate={$endDate->toDateString()}");

        $histories = StockHistory::where('item_id', $itemId)
            ->where('is_archived', $archived)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        if ($histories->isEmpty()) {
            $dateStr = $specificDate ?? $endDate->toDateString();
            Log::info("Tidak ada histories untuk item_id={$itemId}, tanggal={$dateStr}");
            return collect([(object) [
                'tanggal' => $dateStr,
                'tanggal_asli' => $dateStr,
                'created_at' => now(),
                'stok_awal' => 0,
                'in' => 0,
                'out' => 0,
                'stok' => 0,
                'pengirim_penerima' => null,
                'is_initial' => true,
            ]]);
        }

        $initialStock = StockHistory::where('item_id', $itemId)
            ->where('is_archived', $archived)
            ->where('tanggal', '<', $startDate)
            ->whereNotNull('stok')
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->value('stok') ?? 0;

        $aggregated = [];
        $currentStock = $initialStock;

        foreach ($histories as $history) {
            $stokAwal = $currentStock;
            $stokIn = $history->tipe === 'IN' ? $history->qty : 0;
            $stokOut = $history->tipe === 'OUT' ? $history->qty : 0;

            if ($history->tipe === null) {
                $stokAwal = $history->stok ?? $history->qty ?? 0;
                $stokIn = 0;
                $currentStock = $stokAwal;
            } else {
                $currentStock = $stokAwal + $stokIn - $stokOut;
            }

            $currentStock = max(0, $currentStock);

            $aggregated[] = (object) [
                'tanggal' => $history->tanggal,
                'tanggal_asli' => $history->tanggal,
                'created_at' => $history->created_at,
                'stok_awal' => $stokAwal,
                'in' => $stokIn,
                'out' => $stokOut,
                'stok' => $currentStock,
                'pengirim_penerima' => $history->pengirim_penerima,
                'is_initial' => $history->tipe === null,
            ];
        }

        $result = collect($aggregated);

        if ($specificDate) {
            $specificDateParsed = Carbon::parse($specificDate)->startOfDay();
            $filtered = $result->filter(function ($data) use ($specificDateParsed) {
                return Carbon::parse($data->tanggal)->isSameDay($specificDateParsed);
            });

            if ($filtered->isEmpty()) {
                $lastAvailable = $result->filter(function ($data) use ($specificDateParsed) {
                    return Carbon::parse($data->tanggal)->lte($specificDateParsed);
                })->last();

                if ($lastAvailable) {
                    return collect([(object) [
                        'tanggal' => $specificDateParsed->toDateString(),
                        'tanggal_asli' => $lastAvailable->tanggal,
                        'created_at' => $lastAvailable->created_at,
                        'stok_awal' => $lastAvailable->stok,
                        'in' => 0,
                        'out' => 0,
                        'stok' => $lastAvailable->stok,
                        'pengirim_penerima' => null,
                        'is_initial' => false,
                    ]]);
                }

                return collect([(object) [
                    'tanggal' => $specificDateParsed->toDateString(),
                    'tanggal_asli' => $specificDateParsed->toDateString(),
                    'created_at' => now(),
                    'stok_awal' => 0,
                    'in' => 0,
                    'out' => 0,
                    'stok' => 0,
                    'pengirim_penerima' => null,
                    'is_initial' => true,
                ]]);
            }

            return $filtered;
        }

        return $result;
    }

    public function generateLowStockReport($tanggal = null)
    {
        if (!$tanggal) {
            Log::info("generateLowStockReport: Tidak ada tanggal, return kosong");
            return '';
        }

        // Cek apakah ada data StockHistory aktif untuk tanggal ini
        $hasData = StockHistory::where('tanggal', $tanggal)
            ->where('is_archived', false)
            ->exists();

        if (!$hasData) {
            Log::info("generateLowStockReport: Tidak ada StockHistory aktif untuk tanggal {$tanggal}");
            return '';
        }

        $tanggalFormatted = Carbon::parse($tanggal)->isoFormat('DD MMMM YYYY');
        $minimalOrders = $this->getMinimalOrders();

        // Ambil item yang punya StockHistory aktif
        $items = Item::whereHas('stockHistories', function ($query) use ($tanggal) {
            $query->where('tanggal', $tanggal)
                  ->where('is_archived', false);
        })
        ->whereIn('nama', array_keys($minimalOrders))
        ->get()
        ->map(function ($item) use ($minimalOrders, $tanggal) {
            $stockData = $this->getAggregatedStockHistory(
                $item->id,
                1,
                $tanggal,
                false
            );

            $stok = $stockData->isNotEmpty() ? $stockData->last()->stok : 0;
            $itemName = strtoupper(trim($item->nama));
            $itemData = $minimalOrders[$itemName] ?? null;

            if (!$itemData) {
                Log::warning("Item {$itemName} tidak ada di minimalOrders");
                return null;
            }

            return (object) [
                'nama' => $itemName,
                'stok' => $stok,
                'threshold' => $itemData['threshold'],
                'conversion' => $itemData['conversion'],
                'unit_besar' => $itemData['unit'],
            ];
        })
        ->filter()
        ->values();

        // Tambah item dengan stok 0 (nggak ada di StockHistory tapi ada di minimalOrders)
        $allItemNames = array_keys($minimalOrders);
        $activeItemNames = $items->pluck('nama')->toArray();
        $missingItems = array_diff($allItemNames, $activeItemNames);

        foreach ($missingItems as $itemName) {
            $itemData = $minimalOrders[$itemName];
            $items->push((object) [
                'nama' => $itemName,
                'stok' => 0,
                'threshold' => $itemData['threshold'],
                'conversion' => $itemData['conversion'],
                'unit_besar' => $itemData['unit'],
            ]);
        }

        // Kategorikan item
        $stokHabis = $items->filter(function ($item) {
            return $item->stok == 0;
        })->sortBy('nama');

        $stokTerbatas = $items->filter(function ($item) {
            return $item->stok > 0 && $item->stok < 0.5 * $item->threshold;
        })->sortBy('stok');

        $stokHampirHabis = $items->filter(function ($item) {
            return $item->stok >= 0.5 * $item->threshold && $item->stok < $item->threshold;
        })->sortBy('stok');

        // Buat laporan
        $report = "Assalamu'alaikum wr. wb.\n";
        $report .= "📅 " . $tanggalFormatted . "\n\n";
        $report .= "📢 Informasi Stok Stokis 📢\n\n";

        if ($stokHabis->isNotEmpty()) {
            $report .= "🔴 Stok Habis\n";
            foreach ($stokHabis as $item) {
                $report .= "> ❌ {$item->nama}\n";
            }
            $report .= "\n";
        }

        if ($stokTerbatas->isNotEmpty()) {
            $report .= "🟠 Stok Terbatas\n";
            foreach ($stokTerbatas as $item) {
                $stokFormatted = number_format($item->stok / $item->conversion, 0, ',', '.');
                $report .= "> ⚠️ {$item->nama} – {$stokFormatted} {$item->unit_besar}\n";
            }
            $report .= "\n";
        }

        if ($stokHampirHabis->isNotEmpty()) {
            $report .= "🟡 Hampir Habis\n";
            foreach ($stokHampirHabis as $item) {
                $stokFormatted = number_format($item->stok / $item->conversion, 0, ',', '.');
                $report .= "> ⚠️ {$item->nama} – {$stokFormatted} {$item->unit_besar}\n";
            }
            $report .= "\n";
        }

        if ($stokHabis->isEmpty() && $stokTerbatas->isEmpty() && $stokHampirHabis->isEmpty()) {
            $report .= "✅ Tidak ada stok kritis hari ini.\n\n";
        }

        $report .= "terimakasih 🙏";

        Log::info("generateLowStockReport: Laporan untuk {$tanggal}, items=" . json_encode($items->pluck('nama')->toArray()));
        return $report;
    }

    public function generateWhatsappReport($tanggal = null)
    {
        if (!$tanggal) {
            return '';
        }

        $tanggalFormatted = Carbon::parse($tanggal)->isoFormat('dddd, DD MMMM YYYY');
        $tanggalSebelumnya = Carbon::parse($tanggal)->subDay()->toDateString();

        $allowedItems = [
            'KULIT AYAM MARINASI',
            'AYAM ORIGINAL',
            'AYAM UTUH',
            'DADA FILLET MARINASI',
            'DADA FILLET BURGER',
            'AYAM SADAS',
            'PATTY BESAR 360 LBR',
            'PATTY KECIL 520 LBR',
            'ROTI BESAR',
            'ROTI KECIL',
            'KENTANG',
            'ICE CREAM COKLAT',
            'ICE CREAM VANILLA',
            'MOZZARELLA',
            'SPAGHETTI',
            'SAOS KENTANG',
            'CHICKEN NUGGET',
        ];

        $coldStorageMap = [
            'KULIT AYAM MARINASI' => 750,
            'DADA FILLET MARINASI' => 3050,
            'DADA FILLET BURGER' => 3000,
            'AYAM ORIGINAL' => 0,
        ];

        $unitConversions = [
            'KULIT AYAM MARINASI' => 500,
            'DADA FILLET MARINASI' => 500,
            'AYAM ORIGINAL' => 9,
            'CHICKEN NUGGET' => 100,
            'ICE CREAM COKLAT' => 4178,
            'ICE CREAM VANILLA' => 4178,
        ];

        function normalize($name)
        {
            return strtoupper(trim(preg_replace('/\s+/', ' ', $name)));
        }

        $orderMap = collect($allowedItems)->mapWithKeys(function ($item, $index) {
            return [normalize($item) => $index];
        });

        $items = StockHistory::where('tanggal', $tanggal)
            ->where('is_archived', true)
            ->whereHas('item', function ($query) use ($allowedItems) {
                $query->whereIn('nama', $allowedItems);
            })
            ->with('item')
            ->get()
            ->pluck('item')
            ->unique('id')
            ->sortBy(function ($item) use ($orderMap) {
                return $orderMap[normalize($item->nama)] ?? 999;
            });

        $report = "Assalammu'alaikum wr wb\n";
        $report .= "STOK AYAM, SADAS, PATTY, DLL\n\n";
        $report .= "STOKIS BANJARMASIN\n";
        $report .= $tanggalFormatted . "\n";

        foreach ($items as $item) {
            $nama = normalize($item->nama);
            Log::info("Processing item: id={$item->id}, nama={$nama}, tanggal={$tanggal}");

            $hasArchivedData = StockHistory::where('item_id', $item->id)
                ->where('is_archived', true)
                ->where('tanggal', $tanggal)
                ->exists();

            if (!$hasArchivedData) {
                Log::warning("No archived data for item_id={$item->id}, nama={$nama}, tanggal={$tanggal}");
                $stokAwal = 0;
                $stokIn = 0;
                $stokOut = 0;
                $stokAkhir = 0;
            } else {
                $stokAwal = StockHistory::where('item_id', $item->id)
                    ->where('is_archived', true)
                    ->where('tanggal', '<=', $tanggalSebelumnya)
                    ->orderBy('tanggal', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->value('stok') ?? 0;

                $stokImport = StockHistory::where('item_id', $item->id)
                    ->where('is_archived', true)
                    ->where('tanggal', $tanggal)
                    ->whereNull('tipe')
                    ->value('stok') ?? null;

                if ($stokImport !== null) {
                    $stokAwal = $stokImport;
                }

                $stokIn = StockHistory::where('item_id', $item->id)
                    ->where('is_archived', true)
                    ->where('tipe', 'IN')
                    ->where('tanggal', $tanggal)
                    ->sum('qty');

                $stokOut = StockHistory::where('item_id', $item->id)
                    ->where('is_archived', true)
                    ->where('tipe', 'OUT')
                    ->where('tanggal', $tanggal)
                    ->sum('qty');

                $stokAkhir = $stokAwal + $stokIn - $stokOut;

                Log::info("Stok calculated: item_id={$item->id}, nama={$nama}, stokAwal={$stokAwal}, stokIn={$stokIn}, stokOut={$stokOut}, stokAkhir={$stokAkhir}");
            }

            $unit = $unitConversions[$nama] ?? 1;
            $stokAwal = $stokAwal / $unit;
            $stokIn = $stokIn / $unit;
            $stokOut = $stokOut / $unit;
            $stokAkhir = $stokAkhir / $unit;

            $stokAwalStr = number_format($stokAwal, 0, ',', '.');
            $stokInStr = $stokIn > 0 ? number_format($stokIn, 0, ',', '.') : '-';
            $stokOutStr = $stokOut > 0 ? number_format($stokOut, 0, ',', '.') : '-';
            $stokAkhirStr = number_format($stokAkhir, 0, ',', '.');

            $report .= "..................................\n";
            $report .= "{$nama} : {$stokAwalStr}\n";
            $report .= "IN" . str_repeat(" ", 21) . ": {$stokInStr}\n";
            $report .= "OUT" . str_repeat(" ", 17) . ": {$stokOutStr}\n";
            $report .= "AKHIR" . str_repeat(" ", 13) . ": {$stokAkhirStr}\n";

            if (array_key_exists($nama, $coldStorageMap)) {
                $cold = $coldStorageMap[$nama];
                $stokis = max($stokAkhir - $cold, 0);
                $report .= "Cold storage : " . ($cold == 0 ? '-' : number_format($cold, 0, ',', '.')) . "\n";
                $report .= "Stokis" . str_repeat(" ", 13) . ": " . number_format($stokis, 0, ',', '.') . "\n";
            }
        }

        $report .= "..................................\n\n";
        $report .= "Terima kasih🙏🙏";

        return $report;
    }

    public function getMinimalOrders()
    {
        return [
            'KULIT AYAM MARINASI' => ['minimal' => 1, 'unit' => 'pack', 'conversion' => 500, 'threshold' => 50000],
            'AYAM ORIGINAL' => ['minimal' => 1, 'unit' => 'ekor', 'conversion' => 9, 'threshold' => 4500],
            'AYAM SADAS' => ['minimal' => 7, 'unit' => 'pcs', 'conversion' => 1, 'threshold' => 350],
            'AYAM UTUH' => ['minimal' => 1, 'unit' => 'ekor', 'conversion' => 1, 'threshold' => 100],
            'DADA FILLET MARINASI' => ['minimal' => 1, 'unit' => 'pack', 'conversion' => 500, 'threshold' => 50000],
            'DADA FILLET BURGER' => ['minimal' => 5, 'unit' => 'pack', 'conversion' => 5, 'threshold' => 500],
            'CHICKEN NUGGET' => ['minimal' => 1, 'unit' => 'pack', 'conversion' => 100, 'threshold' => 5000],
            'MOZZARELLA' => ['minimal' => 1000, 'unit' => 'gr', 'conversion' => 1, 'threshold' => 8000],
            'KENTANG' => ['minimal' => 2500, 'unit' => 'gr', 'conversion' => 1, 'threshold' => 25000],
            'SPAGHETTI' => ['minimal' => 10, 'unit' => 'porsi', 'conversion' => 1, 'threshold' => 150],
            'SAOS KENTANG' => ['minimal' => 20, 'unit' => 'porsi', 'conversion' => 1, 'threshold' => 200],
            'TEPUNG 10 KG' => ['minimal' => 1000, 'unit' => 'gr', 'conversion' => 1, 'threshold' => 150000],
            'MINYAK' => ['minimal' => 15000, 'unit' => 'gr', 'conversion' => 1, 'threshold' => 1125000],
            'KANTONG KERTAS' => ['minimal' => 500, 'unit' => 'lbr', 'conversion' => 1, 'threshold' => 12500],
            'PLASTIK BESAR LOGO' => ['minimal' => 200, 'unit' => 'pcs', 'conversion' => 1, 'threshold' => 10000],
            'PLASTIK KECIL LOGO' => ['minimal' => 400, 'unit' => 'pcs', 'conversion' => 1, 'threshold' => 20000],
            'SAOS TOMAT SACHET' => ['minimal' => 500, 'unit' => 'pcs', 'conversion' => 1, 'threshold' => 25000],
            'SAOS CABE SACHET' => ['minimal' => 500, 'unit' => 'pcs', 'conversion' => 1, 'threshold' => 25000],
            'ROTI BESAR' => ['minimal' => 6, 'unit' => 'pcs', 'conversion' => 1, 'threshold' => 300],
            'ROTI KECIL' => ['minimal' => 6, 'unit' => 'pcs', 'conversion' => 1, 'threshold' => 300],
            'PATTY BESAR 360 LBR' => ['minimal' => 10, 'unit' => 'lbr', 'conversion' => 1, 'threshold' => 150],
            'PATTY KECIL 520 LBR' => ['minimal' => 10, 'unit' => 'lbr', 'conversion' => 1, 'threshold' => 150],
            'MAYONAISE' => ['minimal' => 1000, 'unit' => 'gr', 'conversion' => 1, 'threshold' => 20000],
            'SAOS CABE CURAH' => ['minimal' => 1000, 'unit' => 'gr', 'conversion' => 1, 'threshold' => 20000],
            'SAOS TOMAT CURAH' => ['minimal' => 1000, 'unit' => 'gr', 'conversion' => 1, 'threshold' => 20000],
            'BERAS 26 PORSI' => ['minimal' => 26, 'unit' => 'porsi', 'conversion' => 1, 'threshold' => 1300],
            'PIRING KERTAS' => ['minimal' => 300, 'unit' => 'lbr', 'conversion' => 1, 'threshold' => 4500],
            'KERTAS NASI LOGO' => ['minimal' => 1000, 'unit' => 'lbr', 'conversion' => 1, 'threshold' => 15000],
            'KERTAS BURGER LOGO' => ['minimal' => 1000, 'unit' => 'lbr', 'conversion' => 1, 'threshold' => 15000],
            'KERTAS KENTANG' => ['minimal' => 100, 'unit' => 'lbr', 'conversion' => 1, 'threshold' => 5000],
            'DUS NASI' => ['minimal' => 300, 'unit' => 'lbr', 'conversion' => 1, 'threshold' => 6000],
            'DUS BURGER' => ['minimal' => 300, 'unit' => 'lbr', 'conversion' => 1, 'threshold' => 6000],
            'KERTAS STRUCK' => ['minimal' => 1, 'unit' => 'roll', 'conversion' => 1, 'threshold' => 1000],
            'PLASTIK JUMBO LOGO' => ['minimal' => 30, 'unit' => 'lbr', 'conversion' => 1, 'threshold' => 1500],
            'TEH KOTAK 24 X 330ML' => ['minimal' => 24, 'unit' => 'pcs', 'conversion' => 1, 'threshold' => 1200],
            'S TEE 12 X 390ML' => ['minimal' => 12, 'unit' => 'botol', 'conversion' => 1, 'threshold' => 600],
            'SAOS SADAS LV 1' => ['minimal' => 1000, 'unit' => 'gr', 'conversion' => 1, 'threshold' => 100000],
            'SAOS SADAS LV 2' => ['minimal' => 1000, 'unit' => 'gr', 'conversion' => 1, 'threshold' => 100000],
            'SAOS SADAS LV 3' => ['minimal' => 1000, 'unit' => 'gr', 'conversion' => 1, 'threshold' => 100000],
            'DUS SADAS' => ['minimal' => 300, 'unit' => 'lbr', 'conversion' => 1, 'threshold' => 15000],
            'LID SEALCUP' => ['minimal' => 1, 'unit' => 'roll', 'conversion' => 1000, 'threshold' => 10],
            'AIR MINERAL PRIMA 24 X 330ML' => ['minimal' => 24, 'unit' => 'botol', 'conversion' => 1, 'threshold' => 480],
            'ICE CREAM COKLAT' => ['minimal' => 1, 'unit' => 'ember', 'conversion' => 4178, 'threshold' => 5],
            'ICE CREAM VANILLA' => ['minimal' => 1, 'unit' => 'ember', 'conversion' => 4178, 'threshold' => 5],
            'CUP SPAGHETTI' => ['minimal' => 50, 'unit' => 'pcs', 'conversion' => 1, 'threshold' => 2500],
            'BUMBU RACIK PREKTO' => ['minimal' => 250, 'unit' => 'gr', 'conversion' => 1, 'threshold' => 6250],
            'GELAS PLASTIK 16OZ' => ['minimal' => 50, 'unit' => 'pcs', 'conversion' => 1, 'threshold' => 2500],
            'SAOS BLACK PEPPER' => ['minimal' => 1, 'unit' => 'pack', 'conversion' => 250, 'threshold' => 80],
            'HONEY MUSTARD' => ['minimal' => 1000, 'unit' => 'gr', 'conversion' => 1, 'threshold' => 20000],
            'KEJU SLICE' => ['minimal' => 10, 'unit' => 'pcs', 'conversion' => 1, 'threshold' => 1000],
            'SAOS KENTANG' => ['minimal' => 20, 'unit' => 'pcs', 'conversion' => 1, 'threshold' => 200],
            'DUS KULIT' => ['minimal' => 100, 'unit' => 'pcs', 'conversion' => 1, 'threshold' => 5000],
            'SYRUP APPLE' => ['minimal' => 1, 'unit' => 'botol', 'conversion' => 750, 'threshold' => 50],
            'SYRUP PASSION FRUIT' => ['minimal' => 1, 'unit' => 'botol', 'conversion' => 750, 'threshold' => 50],
            'SODA LEMON' => ['minimal' => 6, 'unit' => 'botol', 'conversion' => 1625, 'threshold' => 60],
            'BUMBU TABUR SAPI PANGGANG' => ['minimal' => 1, 'unit' => 'pack', 'conversion' => 250, 'threshold' => 50],
            'BUMBU TABUR CABE PEDAS' => ['minimal' => 1, 'unit' => 'pack', 'conversion' => 250, 'threshold' => 50],
            'CHOCO MALT' => ['minimal' => 1000, 'unit' => 'gr', 'conversion' => 1, 'threshold' => 100000],
            'LEMON TEA NEW' => ['minimal' => 1000, 'unit' => 'gr', 'conversion' => 1, 'threshold' => 100000],
            'ORANGE NEW' => ['minimal' => 1000, 'unit' => 'gr', 'conversion' => 1, 'threshold' => 100000],
            'DUS AYAM UTUH' => ['minimal' => 100, 'unit' => 'pcs', 'conversion' => 1, 'threshold' => 5000],
            'SAUCE DISH' => ['minimal' => 50, 'unit' => 'pcs', 'conversion' => 1, 'threshold' => 2500],
            'SAOS MENTAI' => ['minimal' => 1000, 'unit' => 'gr', 'conversion' => 1, 'threshold' => 100000],
            'GELAS PLASTIK 12OZ' => ['minimal' => 50, 'unit' => 'pcs', 'conversion' => 1, 'threshold' => 2500],
            'BUMBU KABSAH' => ['minimal' => 1, 'unit' => 'pack', 'conversion' => 251, 'threshold' => 5],
            'REMPAH KABSAH' => ['minimal' => 1, 'unit' => 'pack', 'conversion' => 79, 'threshold' => 5],
            'SAMBAL NDOMBLE' => ['minimal' => 1, 'unit' => 'pack', 'conversion' => 1000, 'threshold' => 5],
            'BERAS BASMATI' => ['minimal' => 28, 'unit' => 'porsi', 'conversion' => 1, 'threshold' => 1400],
            'TOMATO PASTE' => ['minimal' => 170, 'unit' => 'gr', 'conversion' => 1, 'threshold' => 1700],
            'LEMAK AYAM' => ['minimal' => 1, 'unit' => 'bks', 'conversion' => 500, 'threshold' => 10],
        ];
    }

    private function getMultiplier($satuan, $minimalOrder)
    {
        $autoMultiply = ['pack', 'botol', 'gr', 'pcs', 'ember', 'porsi', 'ekor', 'lbr', 'roll', 'bks', 'ml'];
        return in_array($satuan, $autoMultiply) ? $minimalOrder : 1;
    }
}