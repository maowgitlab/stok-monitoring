<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LowStockController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\PinController;
use App\Http\Controllers\StockAnalysisController;
use App\Http\Controllers\StockComparisonController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/report', [DashboardController::class, 'generateReport'])->name('dashboard.report');

    // Routes untuk item
    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::get('/items/{item}', [ItemController::class, 'show'])->name('items.show');
    Route::delete('/items/destroy-all', [ItemController::class, 'destroyAll'])->name('items.destroyAll');
    Route::get('/laporan/wa', [ItemController::class, 'whatsappReport'])->name('items.whatsapp-report');
    Route::post('/items/whatsapp-report/send-telegram', [ItemController::class, 'sendToTelegram'])->name('items.send-telegram');
    Route::get('/archived', [ItemController::class, 'archivedIndex'])->name('items.archived');
    Route::post('/items/archive', [ItemController::class, 'archive'])->name('items.archive');
    Route::get('/items/{item}/archived', [ItemController::class, 'showArchived'])->name('items.showArchived');

    // Routes untuk import data
    Route::get('/imports/create', [ImportController::class, 'create'])->name('imports.create');
    Route::post('/imports', [ImportController::class, 'store'])->name('imports.store');
    Route::delete('/imports/delete-selected', [ImportController::class, 'destroySelected'])->name('imports.destroySelected');

    // Route analisis
    Route::get('/stock-analysis', [StockAnalysisController::class, 'index'])->name('stock-analysis.index');
    Route::get('/low-stock', [LowStockController::class, 'index'])->name('low-stock.index');
    Route::get('/low-stock/whatsapp-report', [LowStockController::class, 'whatsappReport'])->name('low-stock.whatsapp-report');

    Route::get('/stocks/paste', [StockController::class, 'pasteForm'])->name('stocks.paste');
    Route::post('/stocks/paste', [StockController::class, 'processPaste'])->name('stocks.paste.process');
    Route::post('/stocks/paste-out', [StockController::class, 'processPasteKeluar'])->name('stocks.paste.out');

    // Route komparasi stok
    Route::match(['get', 'post'], '/stocks/compare', [StockComparisonController::class, 'index'])->name('stocks.compare');

    // Route untuk Cold Storage
    Route::post('/cold-storages', [ItemController::class, 'storeColdStorage'])->name('cold-storages.store');
    Route::put('/cold-storages/{coldStorage}', [ItemController::class, 'updateColdStorage'])->name('cold-storages.update');
    Route::delete('/cold-storages/{coldStorage}', [ItemController::class, 'destroyColdStorage'])->name('cold-storages.destroy');

    // Route untuk PIN
    Route::get('/pin/verify', [PinController::class, 'show'])->name('pin.verify');
    Route::post('/pin/verify', [PinController::class, 'verify'])->name('pin.verify');

    // Route untuk Pengaturan
    Route::middleware('pin')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings/clear-cache', [SettingsController::class, 'clearCache'])->name('settings.clear-cache');
    });
});