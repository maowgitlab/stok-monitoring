@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-5 fw-bold" style="font-family: 'Poppins', sans-serif; text-shadow: 2px 2px 4px rgba(0,0,0,0.1);">Pengaturan</h1>

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
                    'cache' => 'Cache Aplikasi',
                    'views' => 'Cache View',
                    'compiled' => 'Cache Compiled',
                    'config' => 'Cache Konfigurasi',
                    'routes' => 'Cache Route',
                    'events' => 'Cache Event',
                ];
            @endphp

            @foreach($cacheSizes as $type => $size)
                <div class="mb-3">
                    <label class="fw-bold" style="font-family: 'Poppins', sans-serif;">
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
                Menghapus cache akan membersihkan cache aplikasi, view, compiled, konfigurasi, route, dan event untuk meningkatkan performa. Gunakan fitur ini jika website terasa lambat atau setelah perubahan konfigurasi.
            </p>
            <form action="{{ route('settings.clear-cache') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-import" onclick="return confirm('Apakah Anda yakin ingin menghapus semua cache? Ini akan:\n- Membersihkan cache aplikasi\n- Membersihkan cache view\n- Membersihkan cache compiled\n- Membersihkan cache konfigurasi\n- Membersihkan cache route\n- Membersihkan cache event\n\nPerforma website mungkin akan lebih cepat setelah ini.')">
                    <i class="fas fa-trash-alt me-2"></i> Hapus Cache
                </button>
            </form>

            <hr class="my-4">

            <h5 class="mb-3">Ganti PIN</h5>
            <p class="text-muted mb-4" style="font-family: 'Poppins', sans-serif;">
                Ubah PIN untuk mengakses menu Pengaturan. PIN harus terdiri dari 4 digit angka.
            </p>
            <form action="{{ route('pin.update') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="new_pin" class="form-label fw-bold" style="font-family: 'Poppins', sans-serif;">PIN Baru (4 digit)</label>
                    <input type="password" name="new_pin" id="new_pin" class="form-control @error('new_pin') is-invalid @enderror"
                           maxlength="4" pattern="\d{4}" required autocomplete="off">
                    @error('new_pin')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-import" onclick="return confirm('Apakah Anda yakin ingin mengubah PIN?')">
                    <i class="fas fa-key me-2"></i> Ubah PIN
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
    .progress {
        background: #e2e8f0;
    }
    .progress-bar {
        transition: width 0.6s ease;
    }
    h1, h5, label.fw-bold, strong {
        color: #2c3e50;
    }
    .text-muted {
        color: #6c757d !important;
    }
    .form-control {
        border-radius: 10px;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        font-family: 'Poppins', sans-serif;
    }
    .invalid-feedback {
        font-size: 0.9rem;
    }
</style>
@endsection