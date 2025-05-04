<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    public function index()
    {
        // Hitung ukuran cache
        $cacheSizes = [
            'config' => $this->getCacheSize('config'),
            'route' => $this->getCacheSize('route'),
            'view' => $this->getCacheSize('view'),
            'cache' => $this->getCacheSize('cache'),
        ];

        // Total ukuran dalam bytes
        $totalSize = array_sum($cacheSizes);

        // Konversi ke format readable
        $cacheSizesFormatted = array_map([$this, 'formatSize'], $cacheSizes);
        $totalSizeFormatted = $this->formatSize($totalSize);

        return view('settings.index', compact('cacheSizes', 'cacheSizesFormatted', 'totalSizeFormatted'));
    }

    public function clearCache(Request $request)
    {
        try {
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('cache:clear');

            Log::info('Cache cleared successfully');

            return redirect()->route('settings.index')
                ->with('success', 'Cache berhasil dihapus (config, route, view, dan cache).');
        } catch (\Exception $e) {
            Log::error('Failed to clear cache: ' . $e->getMessage());

            return redirect()->route('settings.index')
                ->with('error', 'Gagal menghapus cache: ' . $e->getMessage());
        }
    }

    private function getCacheSize($type)
    {
        $size = 0;

        if ($type === 'config') {
            $file = base_path('bootstrap/cache/config.php');
            if (File::exists($file)) {
                $size = File::size($file);
            }
        } elseif ($type === 'route') {
            $files = File::glob(base_path('bootstrap/cache/routes-*.php'));
            foreach ($files as $file) {
                $size += File::size($file);
            }
        } elseif ($type === 'view') {
            $path = storage_path('framework/views');
            if (File::exists($path)) {
                $size = $this->getFolderSize($path);
            }
        } elseif ($type === 'cache') {
            $path = storage_path('framework/cache/data');
            if (File::exists($path)) {
                $size = $this->getFolderSize($path);
            }
        }

        return $size;
    }

    private function getFolderSize($path)
    {
        $size = 0;
        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }
        foreach (File::directories($path) as $dir) {
            $size += $this->getFolderSize($dir);
        }
        return $size;
    }

    private function formatSize($bytes)
    {
        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}