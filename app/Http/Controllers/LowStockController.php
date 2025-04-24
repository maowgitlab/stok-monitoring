<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Services\StockService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LowStockController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index()
    {
        $minimalOrders = $this->stockService->getMinimalOrders();
        $currentDate = Carbon::now()->toDateString();

        $lowStockItems = Item::get()
            ->map(function ($item) use ($minimalOrders, $currentDate) {
                $stockHistory = $this->stockService->getAggregatedStockHistory(
                    $item->id,
                    30,
                    $currentDate,
                    false
                );

                $latestStock = $stockHistory->last();
                $item->latestStock = $latestStock;

                return $item;
            })
            ->filter(function ($item) use ($minimalOrders) {
                if (!$item->latestStock || $item->latestStock->stok == 0) {
                    return false;
                }

                $itemName = strtoupper(trim($item->nama));
                $minimalOrderData = $minimalOrders[$itemName] ?? null;

                if ($minimalOrderData === null) {
                    return false;
                }

                $threshold = $minimalOrderData['threshold'];
                $stok = $item->latestStock->stok;

                return $stok <= $threshold;
            })
            ->map(function ($item) use ($minimalOrders) {
                $itemName = strtoupper(trim($item->nama));
                $minimalOrderData = $minimalOrders[$itemName] ?? null;
                if ($minimalOrderData) {
                    $item->minimal_order = $minimalOrderData['minimal'];
                    $item->unit_besar = $minimalOrderData['unit'];
                    $item->conversion = $minimalOrderData['conversion'];
                    $item->threshold = $minimalOrderData['threshold'];
                } else {
                    $item->minimal_order = 0;
                    $item->unit_besar = $item->satuan;
                    $item->conversion = 1;
                    $item->threshold = 0;
                }
                return $item;
            });

        return view('low-stock.index', compact('lowStockItems'));
    }

    public function whatsappReport(Request $request)
    {
        $tanggal = $request->input('tanggal') ?? Carbon::now()->toDateString();
        $report = $this->stockService->generateLowStockReport($tanggal);
        $message = $report ? '' : 'Tidak ada data stok untuk tanggal yang dipilih.';

        return view('low-stock.whatsapp-report', compact('report', 'tanggal', 'message'));
    }
}