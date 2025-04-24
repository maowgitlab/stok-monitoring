<?php

namespace App\Http\Controllers;

use App\Models\StockHistory;
use App\Services\StockService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $filterDate = Carbon::parse($date)->startOfDay();
        $noDataMessage = null;

        if ($filterDate->gt(Carbon::today())) {
            $noDataMessage = 'Data tidak tersedia untuk tanggal masa depan.';
            $totalItems = 0;
            $totalStock = 0;
            $dailyChange = 0;
            $stockTrend = [];
            $lowStockItems = collect();
            $topMovingItems = [];
            Log::warning("Filter tanggal {$filterDate->toDateString()} di masa depan.");
        } else {
            $archivedDates = StockHistory::where('is_archived', true)
                ->where('tanggal', '<=', $filterDate->endOfDay())
                ->selectRaw('MIN(tanggal) as min_date, MAX(tanggal) as max_date')
                ->first();

            if (!$archivedDates || !$archivedDates->min_date) {
                $noDataMessage = 'Tidak ada data stok arsip untuk periode ini.';
                $totalItems = 0;
                $totalStock = 0;
                $dailyChange = 0;
                $stockTrend = [];
                $lowStockItems = collect();
                $topMovingItems = [];
                Log::info("Tidak ada data arsip sampai {$filterDate->toDateString()}.");
            } else {
                $startDate = Carbon::parse($archivedDates->min_date)->startOfDay();
                $endDate = $filterDate->lte(Carbon::parse($archivedDates->max_date))
                    ? $filterDate->endOfDay()
                    : Carbon::parse($archivedDates->max_date)->endOfDay();
                $days = $startDate->diffInDays($endDate) + 1;

                Log::info("Rentang arsip: {$startDate->toDateString()} sampai {$endDate->toDateString()}, days={$days}");

                $histories = StockHistory::where('is_archived', true)
                    ->whereBetween('tanggal', [$startDate, $endDate])
                    ->with('item')
                    ->get();

                if ($histories->isEmpty()) {
                    $noDataMessage = 'Tidak ada data stok arsip dalam rentang ini.';
                    $totalItems = 0;
                    $totalStock = 0;
                    $dailyChange = 0;
                    $stockTrend = [];
                    $lowStockItems = collect();
                    $topMovingItems = [];
                    Log::info("Histories kosong untuk rentang {$startDate->toDateString()} sampai {$endDate->toDateString()}.");
                } else {
                    $items = $histories->groupBy('item.nama')->map(function ($histories, $nama) use ($startDate, $endDate, $days, $filterDate) {
                        Log::info("Processing item: nama={$nama}, filterDate={$filterDate->toDateString()}");

                        $itemIds = $histories->pluck('item_id')->unique();
                        $sampleItem = $histories->first()->item;

                        $stockData = collect();
                        foreach ($itemIds as $itemId) {
                            $data = $this->getAggregatedStockHistory(
                                $itemId,
                                $days,
                                null,
                                true,
                                $startDate,
                                $endDate
                            );
                            $stockData = $stockData->merge($data);
                        }

                        $stockData = $stockData->groupBy(function ($data) {
                            return Carbon::parse($data->tanggal)->toDateString();
                        })->map(function ($dailyData, $tanggal) use ($nama) {
                            return (object) [
                                'tanggal' => $tanggal,
                                'stok' => $dailyData->sum('stok'),
                                'in' => $dailyData->sum('in'),
                                'out' => $dailyData->sum('out'),
                            ];
                        })->sortBy('tanggal')->values();

                        return (object) [
                            'id' => $sampleItem->id,
                            'nama' => $nama,
                            'kode' => $sampleItem->kode,
                            'satuan' => $sampleItem->satuan,
                            'stockData' => $stockData,
                        ];
                    })->values();

                    $totalItems = $items->filter(function ($item) use ($filterDate) {
                        return $item->stockData->contains(function ($data) use ($filterDate) {
                            return Carbon::parse($data->tanggal)->isSameDay($filterDate);
                        });
                    })->count();
                    Log::info("Total Items untuk {$filterDate->toDateString()}: $totalItems");

                    $totalStock = $items->sum(function ($item) use ($filterDate) {
                        $dailyData = $item->stockData->firstWhere('tanggal', $filterDate->toDateString());
                        $stok = $dailyData ? $dailyData->stok : 0;
                        Log::info("Item {$item->nama} stok pada {$filterDate->toDateString()}: $stok");
                        return $stok;
                    });

                    $dailyIn = $items->sum(function ($item) use ($filterDate) {
                        $dailyData = $item->stockData->firstWhere('tanggal', $filterDate->toDateString());
                        return $dailyData ? $dailyData->in : 0;
                    });
                    $dailyOut = $items->sum(function ($item) use ($filterDate) {
                        $dailyData = $item->stockData->firstWhere('tanggal', $filterDate->toDateString());
                        return $dailyData ? $dailyData->out : 0;
                    });
                    $dailyChange = $dailyIn - $dailyOut;

                    $stockTrend = [];
                    $currentDate = $startDate->copy();
                    while ($currentDate->lte($endDate)) {
                        $dateStr = $currentDate->toDateString();
                        $dailyStock = $totalItems > 0 ? $items->sum(function ($item) use ($dateStr) {
                            $dailyData = $item->stockData->firstWhere('tanggal', $dateStr);
                            return $dailyData ? $dailyData->stok : 0;
                        }) : 0;
                        $dailyInTrend = $totalItems > 0 ? $items->sum(function ($item) use ($dateStr) {
                            $dailyData = $item->stockData->firstWhere('tanggal', $dateStr);
                            return $dailyData ? $dailyData->in : 0;
                        }) : 0;
                        $dailyOutTrend = $totalItems > 0 ? $items->sum(function ($item) use ($dateStr) {
                            $dailyData = $item->stockData->firstWhere('tanggal', $dateStr);
                            return $dailyData ? $dailyData->out : 0;
                        }) : 0;
                        $stockTrend[] = [
                            'tanggal' => $dateStr,
                            'total_stock' => $dailyStock,
                            'total_in' => $dailyInTrend,
                            'total_out' => $dailyOutTrend,
                        ];
                        $currentDate->addDay();
                    }

                    $lowStockItems = $items->map(function ($item) use ($filterDate) {
                        $dailyData = $item->stockData->firstWhere('tanggal', $filterDate->toDateString());
                        $item->stok = $dailyData ? $dailyData->stok : 0;
                        return $item;
                    })->filter(function ($item) {
                        return $item->stok > 0;
                    })->sortBy('stok')->take(10);

                    $topMovingItems = $items->map(function ($item) use ($startDate, $endDate, $filterDate) {
                        $movement = $item->stockData->filter(function ($data) use ($filterDate) {
                            return Carbon::parse($data->tanggal)->isSameDay($filterDate);
                        })->sum(function ($data) {
                            return $data->in + $data->out;
                        });
                        return ['item' => $item, 'movement_count' => $movement];
                    })->filter(function ($data) {
                        return $data['movement_count'] > 0;
                    })->sortByDesc('movement_count')->take(5)->values()->all();

                    if ($totalItems === 0 && !$noDataMessage) {
                        $noDataMessage = "Tidak ada data stok arsip untuk tanggal {$filterDate->toDateString()}.";
                        $totalStock = 0;
                        $dailyChange = 0;
                        $stockTrend = [];
                        $lowStockItems = collect();
                        $topMovingItems = [];
                        Log::info("Tidak ada data untuk filter tanggal {$filterDate->toDateString()}.");
                    }
                }
            }
        }

        return view('dashboard.index', compact('totalItems', 'totalStock', 'stockTrend', 'lowStockItems', 'topMovingItems', 'date', 'dailyChange', 'noDataMessage'));
    }

    public function generateReport(Request $request)
    {
        $filterDate = $request->input('date') ? Carbon::parse($request->input('date'))->startOfDay() : null;

        $archivedDates = StockHistory::where('is_archived', true);
        if ($filterDate) {
            $archivedDates->whereDate('tanggal', $filterDate);
        }
        $archivedDates = $archivedDates->selectRaw('MIN(tanggal) as min_date, MAX(tanggal) as max_date')->first();

        if (!$archivedDates || !$archivedDates->min_date) {
            Log::warning("Tidak ada data arsip untuk generate laporan.");
            return redirect()->back()->with('error', 'Tidak ada data arsip untuk membuat laporan.');
        }

        $startDate = Carbon::parse($archivedDates->min_date)->startOfDay();
        $endDate = Carbon::parse($archivedDates->max_date)->endOfDay();

        $historiesQuery = StockHistory::where('is_archived', true)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->with('item')
            ->orderBy('tanggal', 'asc')
            ->orderBy('created_at', 'asc');
        
        if ($filterDate) {
            $historiesQuery->whereDate('tanggal', $filterDate);
        }
        
        $histories = $historiesQuery->get();

        if ($histories->isEmpty()) {
            Log::warning("Histories kosong untuk rentang {$startDate->toDateString()} sampai {$endDate->toDateString()}.");
            return redirect()->back()->with('error', 'Data arsip kosong.');
        }

        $reportData = $histories->groupBy(function ($history) {
            return Carbon::parse($history->tanggal)->toDateString();
        })->map(function ($dailyHistories, $tanggal) use ($startDate) {
            return $dailyHistories->groupBy('item_id')->map(function ($itemHistories, $itemId) use ($tanggal, $startDate) {
                $item = $itemHistories->first()->item;

                $previousDay = Carbon::parse($tanggal)->subDay()->endOfDay();
                $stokAwal = 0;
                $import = $itemHistories->whereNull('tipe')->first();
                if ($import) {
                    $stokAwal = $import->stok ?? $import->qty ?? 0;
                } else {
                    $stokAwal = StockHistory::where('item_id', $itemId)
                        ->where('is_archived', true)
                        ->where('tanggal', '<=', $previousDay)
                        ->orderBy('tanggal', 'desc')
                        ->orderBy('created_at', 'desc')
                        ->value('stok') ?? 0;
                }

                $stokIn = $itemHistories->where('tipe', 'IN')->sum('qty');
                $stokOut = $itemHistories->where('tipe', 'OUT')->sum('qty');

                $stokAkhir = $stokAwal + $stokIn - $stokOut;

                Log::debug("Item {$item->nama} pada {$tanggal}: stok_awal={$stokAwal}, in={$stokIn}, out={$stokOut}, stok_akhir={$stokAkhir}");

                return [
                    'item_id' => $itemId,
                    'nama' => $item->nama,
                    'kode' => $item->kode,
                    'satuan' => $item->satuan,
                    'stok_awal' => $stokAwal,
                    'in' => $stokIn,
                    'out' => $stokOut,
                    'stok_akhir' => max(0, $stokAkhir),
                    'transactions' => $itemHistories->where('tipe', 'OUT')->map(function ($history) {
                        return [
                            'pengirim_penerima' => $history->pengirim_penerima,
                            'qty' => $history->qty,
                        ];
                    })->values(),
                ];
            })->sortByDesc('stok_akhir')->take(5)->values();
        })->sortBy(function ($data, $tanggal) {
            return $tanggal;
        });

        $stockTrend = [];
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->toDateString();
            $dailyStock = $histories->where('tanggal', '>=', $dateStr . ' 00:00:00')
                ->where('tanggal', '<=', $dateStr . ' 23:59:59')
                ->groupBy('item_id')
                ->sum(function ($itemHistories) {
                    $last = $itemHistories->last();
                    return $last->stok ?? 0;
                });
            $stockTrend[] = [
                'tanggal' => $dateStr,
                'total_stock' => $dailyStock,
            ];
            $currentDate->addDay();
        }

        $restoOut = $histories->where('tipe', 'OUT')
            ->groupBy('pengirim_penerima')
            ->map(function ($restoHistories, $resto) {
                return [
                    'resto' => $resto,
                    'total_out' => $restoHistories->sum('qty'),
                ];
            })->sortByDesc('total_out')
            ->values();

        $topRestoOut = $restoOut->take(5);
        $otherOut = $restoOut->slice(5)->sum('total_out');
        if ($otherOut > 0) {
            $topRestoOut->push([
                'resto' => 'Lainnya',
                'total_out' => $otherOut,
            ]);
        }

        $highlights = [
            'total_stock' => $reportData->flatten(1)->sum('stok_akhir'),
            'total_out' => $histories->where('tipe', 'OUT')->sum('qty'),
            'top_item' => $reportData->flatten(1)->sortByDesc('stok_akhir')->first(),
            'top_resto' => $restoOut->first(),
        ];

        $restoRanking = $restoOut->take(10)->map(function ($resto, $index) {
            return [
                'resto' => $resto['resto'],
                'total_out' => $resto['total_out'],
                'transaction_count' => StockHistory::where('is_archived', true)
                    ->where('tipe', 'OUT')
                    ->where('pengirim_penerima', $resto['resto'])
                    ->count(),
            ];
        });

        $data = [
            'start_date' => $startDate->format('d M Y'),
            'end_date' => $endDate->format('d M Y'),
            'report_data' => $reportData,
            'stock_trend' => $stockTrend,
            'resto_out' => $topRestoOut,
            'resto_ranking' => $restoRanking,
            'highlights' => $highlights,
            'generated_at' => Carbon::now()->format('d M Y H:i'),
        ];

        Log::info("Generating laporan untuk rentang {$startDate->toDateString()} sampai {$endDate->toDateString()}");

        return view('dashboard.report', $data);
    }

    public function getAggregatedStockHistory(
        int $itemId,
        int $days = 30,
        $specificDate = null,
        bool $archived = false,
        ?Carbon $customStartDate = null,
        ?Carbon $customEndDate = null
    ): Collection {
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
                Log::info("Tidak ada data untuk item_id={$itemId}, specificDate={$specificDate}");
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
}