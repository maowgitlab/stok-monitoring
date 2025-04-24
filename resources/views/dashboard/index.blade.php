@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-5 fw-bold text-dark" style="font-family: 'Poppins', sans-serif; color: #2c3e50; text-shadow: 2px 2px 4px rgba(0,0,0,0.1);">Dashboard Monitoring Stok</h1>

    <!-- Filter Tanggal dan Tombol PDF -->
    <div class="card shadow-lg mb-5" style="border-radius: 15px;">
        <div class="card-body">
            <form method="GET" action="{{ route('dashboard') }}" class="row g-3 align-items-center">
                <div class="col-md-3">
                    <input type="date" name="date" class="form-control shadow-sm" value="{{ $date }}" onchange="this.form.submit()">
                </div>
                <div class="col-md-3">
                    <a href="{{ route('dashboard.report') }}" class="btn btn-success shadow-sm" style="border-radius: 25px;">
                        <i class="fas fa-file-pdf me-2"></i>Download Laporan PDF
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Alert -->
    @if($noDataMessage)
        <div class="alert alert-warning text-center" role="alert">
            {{ $noDataMessage }}
        </div>
    @endif

    <!-- Ringkasan -->
    <div class="row mb-5">
        <div class="col-md-4">
            <div class="card stat-card shadow-lg" style="background: #1abc9c; color: white; animation: fadeInUp 0.5s ease;">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Item</h5>
                    <h3>{{ $totalItems }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 my-2 my-lg-0">
            <div class="card stat-card shadow-lg" style="background: #3498db; color: white; animation: fadeInUp 0.7s ease;">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Stok</h5>
                    <h3>{{ number_format($totalStock, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card shadow-lg" style="background: #e67e22; color: white; animation: fadeInUp 0.9s ease;">
                <div class="card-body text-center">
                    <h5 class="card-title">Perubahan Harian</h5>
                    <h3>{{ $dailyChange > 0 ? '+' : '' }}{{ number_format($dailyChange, 2) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Grafik Stok -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-lg" style="border-radius: 15px;">
                <div class="card-header bg-gradient-custom text-white">Grafik Tren Stok</div>
                <div class="card-body">
                    @if (empty($stockTrend) || array_sum(array_column($stockTrend, 'total_stock')) == 0)
                        <p class="text-center text-muted">Tidak ada data stok arsip untuk tanggal {{ \Carbon\Carbon::parse($date)->format('d M Y') }}.</p>
                    @else
                        <canvas id="stockTrendChart"></canvas>
                    @endif
                </div>
            </div>
        </div>

        <!-- Stok Rendah -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-lg" style="border-radius: 15px;">
                <div class="card-header bg-gradient-custom text-white">Stok Rendah (10 Terendah)</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama</th>
                                    <th>Stok</th>
                                    <th>Satuan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lowStockItems as $item)
                                    <tr class="{{ $item->stok < 50 ? 'table-danger' : '' }}">
                                        <td>{{ $item->kode }}</td>
                                        <td><a href="{{ route('items.showArchived', $item->id) }}?date={{ $date }}">{{ $item->nama }}</a></td>
                                        <td>{{ number_format($item->stok, 2) }}</td>
                                        <td>{{ $item->satuan }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">Tidak ada data stok rendah untuk tanggal {{ \Carbon\Carbon::parse($date)->format('d M Y') }}.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pergerakan Terbanyak -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-lg" style="border-radius: 15px;">
                <div class="card-header bg-gradient-custom text-white">Item dengan Pergerakan Terbanyak</div>
                <div class="card-body">
                    @if(empty($topMovingItems))
                        <p class="text-center text-muted">Tidak ada pergerakan stok untuk tanggal {{ \Carbon\Carbon::parse($date)->format('d M Y') }}.</p>
                    @else
                        <canvas id="topMovingChart"></canvas>
                    @endif
                </div>
            </div>
        </div>

        <!-- Aksi -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-lg" style="border-radius: 15px;">
                <div class="card-header bg-gradient-custom text-white">Aksi</div>
                <div class="card-body d-grid gap-3">
                    <a href="{{ route('imports.create') }}" class="btn btn-import">Import Data Stok</a>
                    <a href="{{ route('items.index') }}" class="btn btn-archive">Lihat Semua Item</a>
                    <a href="{{ route('stock-analysis.index') }}" class="btn btn-detail">Analisis Stok</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-gradient-custom {
        background: linear-gradient(90deg, #2ecc71, #27ae60);
    }
    .stat-card {
        border-radius: 15px;
        transition: transform 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .table-custom th, .table-custom td {
        padding: 1rem;
        font-family: 'Poppins', sans-serif;
    }
    .table-custom tr:hover {
        background: #f0f3f5;
    }
    .btn-import, .btn-archive, .btn-detail {
        border-radius: 25px;
        padding: 0.75rem;
        transition: all 0.3s ease;
    }
    .btn-import { background: #1abc9c; }
    .btn-archive { background: #34495e; }
    .btn-detail { background: #9b59b6; }
    .btn-import:hover, .btn-archive:hover, .btn-detail:hover {
        transform: translateY(-2px);
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const stockTrendData = @json($stockTrend);
    const topMovingData = @json($topMovingItems);

    if (stockTrendData && stockTrendData.length > 0 && stockTrendData.reduce((sum, item) => sum + item.total_stock, 0) > 0) {
        const stockTrendChart = new Chart(
            document.getElementById('stockTrendChart'),
            {
                type: 'line',
                data: {
                    labels: stockTrendData.map(item => {
                        const date = new Date(item.tanggal);
                        return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
                    }),
                    datasets: [
                        {
                            label: 'Total Stok',
                            data: stockTrendData.map(item => item.total_stock),
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.1,
                            fill: true
                        },
                        {
                            label: 'Total IN',
                            data: stockTrendData.map(item => item.total_in),
                            borderColor: 'rgb(54, 162, 235)',
                            tension: 0.1,
                            fill: false
                        },
                        {
                            label: 'Total OUT',
                            data: stockTrendData.map(item => item.total_out),
                            borderColor: 'rgb(255, 99, 132)',
                            tension: 0.1,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: { display: true, text: 'Tren Stok Arsip', font: { family: 'Poppins' } }
                    },
                    scales: { y: { beginAtZero: true } }
                }
            }
        );
    }

    if (topMovingData && topMovingData.length > 0) {
        const topMovingChart = new Chart(
            document.getElementById('topMovingChart'),
            {
                type: 'bar',
                data: {
                    labels: topMovingData.map(item => item.item.nama),
                    datasets: [{
                        label: 'Jumlah Pergerakan (IN + OUT)',
                        data: topMovingData.map(item => item.movement_count),
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgb(54, 162, 235)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: { display: true, text: 'Item dengan Pergerakan Terbanyak', font: { family: 'Poppins' } }
                    },
                    scales: { y: { beginAtZero: true } }
                }
            }
        );
    }
</script>
@endpush