@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-5 fw-bold text-dark" style="font-family: 'Poppins', sans-serif; color: #2c3e50; text-shadow: 2px 2px 4px rgba(0,0,0,0.1);">Laporan Low Stock WhatsApp</h1>

    <div class="card shadow-lg" style="border-radius: 15px;">
        <div class="card-header bg-gradient-custom text-white">Filter Tanggal</div>
        <div class="card-body">
            <form method="GET" action="{{ route('low-stock.whatsapp-report') }}" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="tanggal" class="form-label">Pilih Tanggal</label>
                        <input type="date" name="tanggal" id="tanggal" class="form-control shadow-sm" value="{{ $tanggal }}">
                    </div>
                    <div class="col-md-4 d-flex">
                        <button type="submit" class="btn btn-import mx-2">Tampilkan</button>
                        <button type="button" class="btn btn-action" onclick="copyText()">Copy ke Clipboard</button>
                    </div>
                </div>
            </form>
            @if($message)
                <div class="alert alert-warning mt-3" role="alert">
                    {{ $message }}
                </div>
            @endif
            <textarea class="form-control shadow-sm" rows="10" readonly style="font-family: 'Poppins', sans-serif; resize: none;" placeholder="Pilih tanggal terlebih dahulu untuk melihat laporan.">{{ $report }}</textarea>
        </div>
    </div>
</div>

<style>
    .bg-gradient-custom { background: linear-gradient(90deg, #2ecc71, #27ae60); }
    .btn-import { background: #1abc9c; border: none; border-radius: 25px; transition: all 0.3s ease; }
    .btn-import:hover { background: #16a085; transform: translateY(-2px); }
    .btn-action { background: #f39c12; border: none; border-radius: 25px; transition: all 0.3s ease; }
    .btn-action:hover { background: #e67e22; transform: translateY(-2px); }
</style>
@endsection

@push('scripts')
<script>
    function copyText() {
        const textarea = document.querySelector('textarea');
        if (!textarea.value.trim()) {
            alert('Tidak ada teks untuk disalin!');
            return;
        }
        textarea.select();
        document.execCommand('copy');
        alert('Teks berhasil disalin ke clipboard!');
    }
</script>
@endpush