@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-5 fw-bold text-dark" style="font-family: 'Poppins', sans-serif; color: #2c3e50; text-shadow: 2px 2px 4px rgba(0,0,0,0.1);">Stok Menipis</h1>

    <div class="card shadow-lg" style="border-radius: 15px;">
        <div class="card-header bg-gradient-custom text-white d-flex justify-content-between align-items-center">
            <span>Daftar Stok Rendah</span>
            <a href="{{ route('low-stock.whatsapp-report') }}" class="btn btn-action">Laporan WhatsApp</a>
        </div>
        <div class="card-body">
            @if($lowStockItems->isEmpty())
                <div class="alert alert-success shadow-sm">Tidak ada item aktif dengan stok menipis.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Stok Terakhir</th>
                                <th>Stok (Satuan Dasar)</th>
                                <th>Minimal Order</th>
                                <th>Ambang Batas</th>
                                <th>Satuan</th>
                                <th>Tanggal Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowStockItems as $item)
                                @php
                                    $stok = $item->latestStock->stok;
                                    $conversion = $item->conversion;
                                    $stokInUnits = floor($stok / $conversion);
                                    $stokDalamSatuanDasar = $stok;
                                    $isCritical = $stok <= ($item->threshold * 0.5);
                                    $minimalOrderInBaseUnit = $item->minimal_order * $item->conversion;
                                @endphp
                                <tr class="{{ $isCritical ? 'table-danger' : '' }}">
                                    <td>{{ $item->kode }}</td>
                                    <td>{{ $item->nama }}</td>
                                    <td class="fw-bold">{{ number_format($stokInUnits, 0) }} {{ $item->unit_besar }} {{ $item->latestStock->is_initial ? '(Awal)' : '' }}</td>
                                    <td>{{ number_format($stokDalamSatuanDasar, 2) }} {{ $item->satuan }}</td>
                                    <td>{{ number_format($minimalOrderInBaseUnit, 0) }} {{ $item->satuan }}</td>
                                    <td>{{ number_format($item->threshold, 0) }} {{ $item->satuan }}</td>
                                    <td>{{ $item->satuan }}</td>
                                    <td>{{ Carbon\Carbon::parse($item->latestStock->tanggal_asli)->format('d/m/Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .bg-gradient-custom { background: linear-gradient(90deg, #2ecc71, #27ae60); }
    .table-custom th, .table-custom td { padding: 1rem; font-family: 'Poppins', sans-serif; }
    .table-custom tr:hover { background: #f0f3f5; }
    .table-danger { background-color: #f8d7da !important; }
    .btn-action { background: #f39c12; border: none; border-radius: 25px; transition: all 0.3s ease; color: white; }
    .btn-action:hover { background: #e67e22; transform: translateY(-2px); }
</style>
@endsection