@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-5 fw-bold text-dark" style="font-family: 'Poppins', sans-serif; color: #2c3e50; text-shadow: 2px 2px 4px rgba(0,0,0,0.1);">Pengaturan</h1>

    <div class="card shadow-lg" style="border-radius: 15px;">
        <div class="card-header bg-gradient-custom text-white">
            Optimasi Sistem
        </div>
        <div class="card-body">
            <h5 class="mb-3">Ukuran Cache</h5>
            <p class="text-muted mb-4" style="font-family: 'Poppins', sans-serif;">
                Berikut adalah ukuran cache aplikasi saat ini. Menghapus cache dapat meningkatkan performa website.
            </p>

            <!-- Progress Bars -->
            @php
                $maxSize = max($cacheSizes) > 0 ? max($cacheSizes) : 1; // Hindari bagi nol
                $labels = [
                    'config' => 'Cache Konfigurasi',
                    'route' => 'Cache Rute',
                    'view' => 'Cache View',
                    'cache' => 'Cache Aplikasi',
                ];
            @endphp

            @foreach($cacheSizes as $type => $size)
                <div class="mb-3">
                    <label class="fw-bold text-dark" style="font-family: 'Poppins', sans-serif;">
                        {{ $labels[$type] }}: {{ $cacheSizesFormatted[$type] }}
                    </label>
                    <div class="progress" style="height: 20px; border-radius: 10px;">
                        <div class="progress-bar bg-gradient-custom" role="progressbar"
                             style="width: {{ ($size / $maxSize) * 100 }}%;"
                             aria-valuenow="{{ ($size / $maxSize) * 100 }}"
                             aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="mt-4">
                <strong>Total Ukuran Cache: {{ $totalSizeFormatted }}</strong>
            </div>

            <hr class="my-4">

            <h5 class="mb-3">Hapus Cache</h5>
            <p class="text-muted mb-4" style="font-family: 'Poppins', sans-serif;">
                Menghapus cache akan membersihkan konfigurasi, rute, view, dan data cache aplikasi untuk meningkatkan performa. Gunakan fitur ini jika website terasa lambat atau setelah perubahan konfigurasi.
            </p>
            <form action="{{ route('settings.clear-cache') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-import" onclick="return confirm('Apakah Anda yakin ingin menghapus semua cache? Ini akan:\n- Membersihkan cache konfigurasi\n- Membersihkan cache rute\n- Membersihkan cache view\n- Membersihkan cache aplikasi\n\nPerforma website mungkin akan lebih cepat setelah ini.')">
                    <i class="fas fa-trash-alt me-2"></i> Hapus Cache
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    .bg-gradient-custom {
        background: linear-gradient(90deg, #2ecc71, #27ae60);
    }
    .btn-import {
        background: #1abc9c;
        border: none;
        border-radius: 25px;
        transition: all 0.3s ease;
        color: white;
    }
    .btn-import:hover {
        background: #16a085;
        transform: translateY(-2px);
    }
    .progress-bar {
        transition: width 0.6s ease;
    }
</style>
@endsection