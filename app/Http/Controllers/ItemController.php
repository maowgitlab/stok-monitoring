<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use App\Services\StockService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

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
                $q->where('is_archived', false);
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
            return $item;
        });

        return view('items.index', compact('items'));
    }

    public function archivedIndex()
    {
        $allDates = StockHistory::where('is_archived', true)
            ->pluck('tanggal')
            ->map(function ($tanggal) {
                return Carbon::parse($tanggal)->toDateString();
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
                return Carbon::parse($history->tanggal)->toDateString() === $selectedDate;
            });
        }

        $latestStock = $stockData->isNotEmpty() ? $stockData->last() : null;

        return view('items.show-archived', compact('item', 'stockData', 'latestStock'));
    }

    public function show(Item $item)
    {
        $stockData = $this->stockService->getAggregatedStockHistory($item->id, 30, null, false);
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
        $itemIds = StockHistory::where('is_archived', false)->pluck('item_id')->unique();

        if ($itemIds->isEmpty()) {
            return redirect()->route('items.index')->with('error', 'Tidak ada item aktif untuk dihapus.');
        }

        DB::transaction(function () use ($itemIds) {
            // Hapus histori aktif
            $deletedHistories = StockHistory::whereIn('item_id', $itemIds)
                ->where('is_archived', false)
                ->delete();

            // Hapus item yang tidak punya histori tersisa (aktif atau arsip)
            $itemsDeleted = Item::whereIn('id', $itemIds)
                ->whereDoesntHave('stockHistories')
                ->delete();

            Log::info("Menghapus $deletedHistories histori aktif dan $itemsDeleted item untuk item_ids: " . json_encode($itemIds->all()));
        });

        return redirect()->route('items.index')
            ->with('success', 'Semua item aktif dan histori stoknya berhasil dihapus.');
    }

    public function whatsappReport(Request $request)
    {
        $report = '';
        $tanggal = $request->input('tanggal');

        if ($tanggal) {
            $report = $this->stockService->generateWhatsappReport($tanggal);
        }

        return view('items.whatsapp-report', compact('report', 'tanggal'));
    }

    public function sendToTelegram(Request $request)
    {
        $tanggal = $request->input('tanggal');
        $report = $this->stockService->generateWhatsappReport($tanggal);

        if (empty($report)) {
            return redirect()->route('items.whatsapp-report')
                ->with('error', 'Tidak ada laporan untuk tanggal yang dipilih.');
        }

        $botToken = env('TELEGRAM_BOT_TOKEN', '7933107197:AAHqfBpD2xGdRjmiX9rucPmC9ud78O95rHY');
        $chatId = env('TELEGRAM_CHAT_ID', '-1002544298109');
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        try {
            $response = Http::post($url, [
                'chat_id' => $chatId,
                'text' => $report,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                Log::info("Telegram message sent successfully for date: {$tanggal}");
                return redirect()->route('items.whatsapp-report', ['tanggal' => $tanggal])
                    ->with('success', 'Laporan berhasil dikirim ke Telegram.');
            } else {
                Log::error("Failed to send Telegram message: " . $response->body());
                return redirect()->route('items.whatsapp-report', ['tanggal' => $tanggal])
                    ->with('error', 'Gagal mengirim laporan ke Telegram.');
            }
        } catch (\Exception $e) {
            Log::error("Exception while sending Telegram message: " . $e->getMessage());
            return redirect()->route('items.whatsapp-report', ['tanggal' => $tanggal])
                ->with('error', 'Terjadi kesalahan saat mengirim laporan ke Telegram.');
        }
    }
}