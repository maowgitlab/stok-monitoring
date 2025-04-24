<?php

namespace App\Http\Controllers;

use App\Models\StockHistory;
use App\Services\StockService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StockAnalysisController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        // Ambil tanggal paling awal dan paling akhir dari data arsip
        $archivedDates = StockHistory::where('is_archived', true)
            ->selectRaw('MIN(tanggal) as min_date, MAX(tanggal) as max_date')
            ->first();

        // Set default rentang waktu
        $defaultStartDate = $archivedDates && $archivedDates->min_date
            ? Carbon::parse($archivedDates->min_date)
            : Carbon::now()->subDays(30);
        $defaultEndDate = $archivedDates && $archivedDates->max_date
            ? Carbon::parse($archivedDates->max_date)
            : Carbon::now();

        // Gunakan input user kalau ada
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : $defaultStartDate;
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : $defaultEndDate;

        // Pastikan startDate <= endDate
        if ($startDate->gt($endDate)) {
            $temp = $startDate;
            $startDate = $endDate;
            $endDate = $temp;
        }

        // Hitung jumlah hari
        $days = $startDate->diffInDays($endDate) + 1;

        // Konversi satuan (sama kayak generateWhatsappReport)
        $unitConversions = [
            'AYAM ORIGINAL' => 9,
            'KULIT AYAM MARINASI' => 500,
            'DADA FILLET MARINASI' => 500,
            'CHICKEN NUGGET' => 100,
            'ICE CREAM COKLAT' => 4178,
            'ICE CREAM VANILLA' => 4178,
        ];

        // Ambil item dengan data arsip, kelompokkan berdasarkan nama
        $items = StockHistory::where('is_archived', true)
            ->whereBetween('tanggal', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->with('item')
            ->get()
            ->groupBy('item.nama')
            ->map(function ($histories, $nama) use ($startDate, $endDate, $days, $unitConversions) {
                Log::info("Processing item: nama={$nama}, startDate={$startDate->toDateString()}, endDate={$endDate->toDateString()}");

                // Ambil semua item_id untuk nama ini
                $itemIds = $histories->pluck('item_id')->unique();
                $sampleItem = $histories->first()->item;

                // Gabungkan stockData dari semua item_id
                $stockData = collect();
                foreach ($itemIds as $itemId) {
                    $data = $this->stockService->getAggregatedStockHistory(
                        $itemId,
                        $days,
                        null,
                        true,
                        $startDate,
                        $endDate
                    )->filter(function ($data) use ($startDate, $endDate) {
                        $tanggal = Carbon::parse($data->tanggal);
                        $isBetween = $tanggal->between($startDate->startOfDay(), $endDate->endOfDay());
                        return $isBetween;
                    });
                    $stockData = $stockData->merge($data);
                }

                // Agregasi stockData berdasarkan tanggal
                $stockData = $stockData->groupBy(function ($data) {
                    return Carbon::parse($data->tanggal)->toDateString();
                })->map(function ($dailyData, $tanggal) use ($nama, $unitConversions, $startDate) {
                    // Ambil stok awal dari hari sebelumnya atau import
                    $previousDay = Carbon::parse($tanggal)->subDay()->endOfDay();
                    $stokAwal = StockHistory::whereIn('item_id', $dailyData->pluck('item_id')->unique())
                        ->where('is_archived', true)
                        ->where('tanggal', '<=', $previousDay)
                        ->orderBy('tanggal', 'desc')
                        ->orderBy('created_at', 'desc')
                        ->value('stok') ?? 0;

                    $import = $dailyData->where('is_initial', true)->first();
                    if ($import) {
                        $stokAwal = $import->stok_awal;
                    }

                    $stokIn = $dailyData->sum('in');
                    $stokOut = $dailyData->sum('out');
                    $stok = $stokAwal + $stokIn - $stokOut;

                    // Terapkan konversi satuan
                    $unit = $unitConversions[$nama] ?? 1;
                    $stokAwal = $stokAwal / $unit;
                    $stokIn = $stokIn / $unit;
                    $stokOut = $stokOut / $unit;
                    $stok = $stok / $unit;

                    return (object) [
                        'tanggal' => $tanggal,
                        'tanggal_asli' => $dailyData->first()->tanggal_asli,
                        'created_at' => $dailyData->first()->created_at,
                        'stok_awal' => $stokAwal,
                        'in' => $stokIn,
                        'out' => $stokOut,
                        'stok' => max(0, $stok),
                        'pengirim_penerima' => null,
                        'is_initial' => $import !== null,
                    ];
                })->sortBy('tanggal')->values();

                $item = (object) [
                    'id' => $sampleItem->id,
                    'nama' => $nama,
                    'kode' => $sampleItem->kode,
                    'satuan' => $sampleItem->satuan,
                    'stockData' => $stockData,
                    'stats' => [
                        'stok_awal' => $stockData->isNotEmpty() ? $stockData->first()->stok_awal : 0,
                        'stok_akhir' => $stockData->isNotEmpty() ? $stockData->last()->stok : 0,
                        'total_in' => $stockData->sum('in'),
                        'total_out' => $stockData->sum('out'),
                    ],
                ];

                Log::info("Item stats: nama={$nama}, stok_awal={$item->stats['stok_awal']}, stok_akhir={$item->stats['stok_akhir']}, total_in={$item->stats['total_in']}, total_out={$item->stats['total_out']}");

                return $item;
            })->filter(function ($item) {
                return $item->stockData->isNotEmpty();
            })->sortBy('nama')->values();

        return view('stock_analysis.index', compact('items', 'startDate', 'endDate'));
    }
}