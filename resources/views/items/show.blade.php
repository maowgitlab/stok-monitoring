@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold text-dark">
            {{ $item->nama }}
            <span class="badge bg-gradient-primary text-white ms-2">Aktif</span>
        </h1>
        <a href="{{ route('items.index') }}" class="btn btn-outline-dark">
            <i class="fas fa-arrow-left me-2"></i> Kembali
        </a>
    </div>

    <!-- Konten Utama -->
    <div class="row g-4">
        <!-- Info Item -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Informasi Item</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5 fw-semibold">Kode</dt>
                        <dd class="col-7">{{ $item->kode }}</dd>
                        <dt class="col-5 fw-semibold">Nama</dt>
                        <dd class="col-7">{{ $item->nama }}</dd>
                        <dt class="col-5 fw-semibold">Satuan</dt>
                        <dd class="col-7">{{ $item->satuan }}</dd>
                        <dt class="col-5 fw-semibold">Stok Saat Ini</dt>
                        <dd class="col-7">
                            @if($latestStock)
                                <span class="text-{{ $latestStock->stok > 0 ? 'success' : 'danger' }} fw-bold">
                                    {{ number_format($latestStock->stok, 2) }} {{ $item->satuan }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </dd>
                        <dt class="col-5 fw-semibold">Terakhir Diupdate</dt>
                        <dd class="col-7">
                            {{ $latestStock ? \Carbon\Carbon::parse($latestStock->tanggal)->format('d/m/Y H:i') : '-' }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Riwayat Stok -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i> Riwayat Stok Aktif</h5>
                    <span class="badge bg-light text-dark">
                        {{ $stockData->count() }} Transaksi
                    </span>
                </div>
                <div class="card-body p-0">
                    @if($stockData->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Tanggal</th>
                                        <th>Pengirim/Penerima</th> <!-- Kolom baru -->
                                        <th>Stok Awal</th>
                                        <th>IN</th>
                                        <th>OUT</th>
                                        <th>Stok Akhir</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($stockData as $data)
                                    <tr>
                                        <td class="ps-3">
                                            {{ \Carbon\Carbon::parse($data->tanggal)->format('d/m/Y H:i') }}
                                            <small class="text-muted d-block">
                                                {{ \Carbon\Carbon::parse($data->created_at)->diffForHumans() }}
                                            </small>
                                        </td>
                                        <td>
                                            {{ $data->pengirim_penerima ?? '-' }}
                                        </td>
                                        <td>{{ number_format($data->stok_awal, 2) }} {{ $item->satuan }}</td>
                                        <td class="text-{{ $data->in > 0 ? 'success' : 'muted' }}">
                                            {{ $data->in > 0 ? number_format($data->in, 2) : '-' }}
                                        </td>
                                        <td class="text-{{ $data->out > 0 ? 'danger' : 'muted' }}">
                                            {{ $data->out > 0 ? number_format($data->out, 2) : '-' }}
                                        </td>
                                        <td class="fw-semibold">
                                            {{ number_format($data->stok, 2) }} {{ $item->satuan }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Belum ada riwayat stok aktif untuk item ini.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik -->
    @if($stockData->isNotEmpty())
    <div class="row g-4 mt-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body">
                    <h6 class="text-muted">Total IN</h6>
                    <h3 class="text-success fw-bold">
                        {{ number_format($stockData->sum('in'), 2) }} {{ $item->satuan }}
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body">
                    <h6 class="text-muted">Total OUT</h6>
                    <h3 class="text-danger fw-bold">
                        {{ number_format($stockData->sum('out'), 2) }} {{ $item->satuan }}
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body">
                    <h6 class="text-muted">Perubahan Bersih</h6>
                    <h3 class="text-{{ $stockData->sum('in') - $stockData->sum('out') >= 0 ? 'success' : 'danger' }} fw-bold">
                        {{ number_format($stockData->sum('in') - $stockData->sum('out'), 2) }} {{ $item->satuan }}
                    </h3>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
    .bg-gradient-primary {
        background: linear-gradient(90deg, #007bff, #0056b3);
    }
    .card-header {
        border-bottom: 0;
    }
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
</style>
@endsection