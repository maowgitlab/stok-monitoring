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
            'cache' => $this->getCacheSize('cache'),
            'views' => $this->getCacheSize('views'),
            'compiled' => $this->getCacheSize('compiled'),
            'config' => $this->getCacheSize('config'),
            'routes' => $this->getCacheSize('routes'),
            'events' => $this->getCacheSize('events'),
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
            // Jalankan semua perintah optimize:clear
            $commands = [
                'cache:clear' => 'Cache aplikasi',
                'view:clear' => 'Cache view',
                'config:clear' => 'Cache konfigurasi',
                'route:clear' => 'Cache route',
                'event:clear' => 'Cache event',
            ];

            foreach ($commands as $command => $description) {
                Artisan::call($command);
                Log::info("{$description} cleared successfully");
            }

            return redirect()->route('settings.index')
                ->with('success', 'Semua cache berhasil dihapus (cache, views, config, routes, events).');
        } catch (\Exception $e) {
            Log::error('Failed to clear cache: ' . $e->getMessage());
            return redirect()->route('settings.index')
                ->with('error', 'Gagal menghapus cache: ' . $e->getMessage());
        }
    }

    private function getCacheSize($type)
    {
        $size = 0;

        if ($type === 'cache') {
            $path = storage_path('framework/cache/data');
            if (File::exists($path)) {
                $size = $this->getFolderSize($path);
            }
        } elseif ($type === 'views') {
            $path = storage_path('framework/views');
            if (File::exists($path)) {
                $size = $this->getFolderSize($path);
            }
        } elseif ($type === 'compiled') {
            $path = base_path('bootstrap/cache/services.php');
            if (File::exists($path)) {
                $size = File::size($path);
            }
        } elseif ($type === 'config') {
            $path = base_path('bootstrap/cache/config.php');
            if (File::exists($path)) {
                $size = File::size($path);
            }
        } elseif ($type === 'routes') {
            $path = base_path('bootstrap/cache/routes-v7.php');
            if (File::exists($path)) {
                $size = File::size($path);
            }
        } elseif ($type === 'events') {
            // Events cache biasanya di memory atau ikut cache:clear
            $size = 0;
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