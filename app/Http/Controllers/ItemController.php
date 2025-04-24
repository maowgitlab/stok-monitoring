<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use App\Services\StockService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ItemController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index()
    {
        $query = Item::select('items.*')
            ->whereHas('stockHistories', function ($q) {
                $q->where('is_archived', false); // Cuma item dengan histori aktif
            });

        if (request()->filled('search')) {
            $search = request()->search;
            $query->where(function ($q) use ($search) {
                $q->where('items.nama', 'like', "%{$search}%")
                    ->orWhere('items.kode', 'like', "%{$search}%");
            });
        }

        $items = $query->paginate(15);

        $items->getCollection()->transform(function ($item) {
            $stockData = $this->stockService->getAggregatedStockHistory($item->id, 30, null, false);
            $item->tanggal_stok = $stockData->isNotEmpty() ? $stockData->last()->tanggal : null;
            return $item; // Nggak usah set stok
        });

        return view('items.index', compact('items'));
    }

    public function archivedIndex()
    {
        $allDates = StockHistory::where('is_archived', true)
            ->pluck('tanggal')
            ->map(function ($tanggal) {
                return \Carbon\Carbon::parse($tanggal)->toDateString();
            })
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        Log::info('Tanggal unik di arsip: ' . json_encode($allDates));

        $items = Item::whereHas('stockHistories', function ($q) {
            $q->where('is_archived', true);
        })->get();

        $items->transform(function ($item) {
            $stockData = $this->stockService->getAggregatedStockHistory($item->id, 30, null, true);
            $item->stok = $stockData->isNotEmpty() ? $stockData->last()->stok : 0;
            $item->stockHistories = $stockData;
            return $item;
        });

        return view('items.archived', compact('items', 'allDates'));
    }

    public function showArchived(Item $item)
    {
        $stockData = $this->stockService->getAggregatedStockHistory($item->id, 30, null, true);
        $selectedDate = request()->query('date');

        if ($selectedDate) {
            $stockData = $stockData->filter(function ($history) use ($selectedDate) {
                return \Carbon\Carbon::parse($history->tanggal)->toDateString() === $selectedDate;
            });
        }

        $latestStock = $stockData->isNotEmpty() ? $stockData->last() : null;

        return view('items.show-archived', compact('item', 'stockData', 'latestStock'));
    }

    public function show(Item $item)
    {
        $stockData = $this->stockService->getAggregatedStockHistory($item->id, 30, null, false); // Data aktif
        $latestStock = $stockData->isNotEmpty() ? $stockData->last() : null;

        return view('items.show', compact('item', 'stockData', 'latestStock'));
    }

    public function archive(Request $request)
    {
        $itemIds = StockHistory::where('is_archived', false)->pluck('item_id')->unique();

        if ($itemIds->isEmpty()) {
            return redirect()->route('items.index')->with('error', 'Tidak ada data aktif untuk diarsipkan.');
        }

        
        DB::transaction(function () use ($itemIds) {
            $updated = StockHistory::whereIn('item_id', $itemIds)
            ->where('is_archived', false)
            ->update(['is_archived' => true]);
            Log::info("Mengarsipkan $updated row untuk item: " . json_encode($itemIds->all()));
        });

        return redirect()->route('items.index')->with('success', 'Data berhasil diarsipkan.');
    }

    public function destroyAll(Request $request)
    {
        $tanggal = now()->toDateString();

        $itemIds = StockHistory::whereDate('tanggal', $tanggal)->pluck('item_id')->unique();

        Item::whereIn('id', $itemIds)->each(function ($item) use ($tanggal) {
            $item->stockHistories()->whereDate('tanggal', $tanggal)->delete();

            if ($item->stockHistories()->count() === 0) {
                $item->delete();
            }
        });

        return redirect()->route('items.index')
            ->with('success', 'Data item dan stok hari ini berhasil dihapus.');
    }


    public function whatsappReport(Request $request)
    {
        $report = '';
        $tanggal = $request->input('tanggal');
    
        // Hanya generate report kalau ada tanggal yang dipilih
        if ($tanggal) {
            $report = $this->stockService->generateWhatsappReport($tanggal);
        }
    
        return view('items.whatsapp-report', compact('report'));
    }
}
