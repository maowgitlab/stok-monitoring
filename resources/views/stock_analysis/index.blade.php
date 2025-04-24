@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-5 fw-bold text-dark" style="font-family: 'Poppins', sans-serif; color: #2c3e50; text-shadow: 2px 2px 4px rgba(0,0,0,0.1);">Analisis Stok</h1>

    <div class="card shadow-lg mb-5" style="border-radius: 15px;">
        <div class="card-header bg-gradient-custom text-white">Filter Tanggal</div>
        <div class="card-body">
            <form method="GET" action="{{ route('stock-analysis.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Dari Tanggal</label>
                    <input type="date" name="start_date" id="start_date" class="form-control shadow-sm" value="{{ $startDate->toDateString() }}">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date" class="form-control shadow-sm" value="{{ $endDate->toDateString() }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-import w-100" style="border-radius: 25px;">Filter</button>
                </div>
            </form>
        </div>
    </div>

    @if($items->isEmpty())
        <div class="alert alert-info text-center" role="alert">
            Tidak ada data arsip stok dalam rentang tanggal yang dipilih.
        </div>
    @else
        <div class="row">
            @foreach($items as $item)
                <div class="col-md-6 mb-4">
                    <div class="card shadow-lg" style="border-radius: 15px;">
                        <div class="card-header bg-gradient-custom text-white">
                            <strong>{{ $item->nama }} ({{ $item->kode }})</strong>
                        </div>
                        <div class="card-body">
                            @if($item->stockData->isEmpty())
                                <p class="text-muted text-center">Tidak ada data arsip untuk item ini dalam rentang tanggal yang dipilih.</p>
                            @else
                                <canvas id="chart-{{ md5($item->nama) }}" height="200"></canvas>
                                <div class="mt-3">
                                    <table class="table table-custom">
                                        <tr><th>Stok Awal</th><td>{{ number_format($item->stats['stok_awal'], 0, ',', '.') }} {{ $item->satuan }}</td></tr>
                                        <tr><th>Stok Akhir</th><td>{{ number_format($item->stats['stok_akhir'], 0, ',', '.') }} {{ $item->satuan }}</td></tr>
                                        <tr><th>Total IN</th><td>{{ number_format($item->stats['total_in'], 0, ',', '.') }} {{ $item->satuan }}</td></tr>
                                        <tr><th>Total OUT</th><td>{{ number_format($item->stats['total_out'], 0, ',', '.') }} {{ $item->satuan }}</td></tr>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<style>
    .bg-gradient-custom { background: linear-gradient(90deg, #2ecc71, #27ae60); }
    .table-custom th, .table-custom td { padding: 0.75rem; font-family: 'Poppins', sans-serif; }
    .btn-import { background: #1abc9c; border: none; transition: all 0.3s ease; }
    .btn-import:hover { background: #16a085; transform: translateY(-2px); }
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    @foreach($items as $item)
        @if($item->stockData->isNotEmpty())
            new Chart(document.getElementById('chart-{{ md5($item->nama) }}'), {
                type: 'line',
                data: {
                    labels: {!! json_encode($item->stockData->pluck('tanggal')->map(fn($t) => \Carbon\Carbon::parse($t)->format('d M'))->toArray()) !!},
                    datasets: [
                        { label: 'Stok', data: {!! json_encode($item->stockData->pluck('stok')->toArray()) !!}, borderColor: '#1abc9c', backgroundColor: 'rgba(26,188,156,0.2)', tension: 0.3, fill: true },
                        { label: 'IN', data: {!! json_encode($item->stockData->pluck('in')->toArray()) !!}, borderColor: '#3498db', tension: 0.3 },
                        { label: 'OUT', data: {!! json_encode($item->stockData->pluck('out')->toArray()) !!}, borderColor: '#e67e22', tension: 0.3 }
                    ]
                },
                options: {
                    plugins: { title: { display: true, text: 'Stock History - {{ $item->nama }}', font: { family: 'Poppins' } } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        @endif
    @endforeach
</script>
@endpush