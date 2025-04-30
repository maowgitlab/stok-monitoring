@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-5 fw-bold text-dark" style="font-family: 'Poppins', sans-serif; color: #2c3e50; text-shadow: 2px 2px 4px rgba(0,0,0,0.1);">Komparasi Stok</h1>

    <div class="card shadow-lg" style="border-radius: 15px;">
        <div class="card-header bg-gradient-custom text-white">Form Komparasi</div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success mt-3" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger mt-3" role="alert">
                    {{ session('error') }}
                </div>
            @endif
            @if ($message)
                <div class="alert alert-warning mt-3" role="alert">
                    {{ $message }}
                </div>
            @endif
            <form method="POST" action="{{ route('stocks.compare') }}" class="mb-4">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="tanggal" class="form-label">Pilih Tanggal</label>
                        <input type="date" name="tanggal" id="tanggal" class="form-control shadow-sm" value="{{ $tanggal ?? old('tanggal') }}" required>
                    </div>
                    <div class="col-md-12">
                        <label for="spreadsheet_data" class="form-label">Data Spreadsheet (Paste di sini)</label>
                        <textarea name="spreadsheet_data" id="spreadsheet_data" class="form-control shadow-sm" rows="10" placeholder="Paste data spreadsheet (tab-separated)">{{ $spreadsheetData ?? '' }}</textarea>
                        <div class="form-text">Contoh format: NAMA BAHAN BAKU [tab] SATUAN [tab] STOCK FISIK AWAL [tab] SALDO AWAL [tab] MASUK [tab] KELUAR [tab] SALDO AKHIR</div>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-import">Bandingkan</button>
                    </div>
                </div>
            </form>

            @if (!empty($results))
                <hr class="my-4">
                <h5 class="mb-3">Hasil Komparasi</h5>
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Nama Bahan Baku</th>
                                <th>Satuan</th>
                                <th>Saldo Akhir Sistem</th>
                                <th>Saldo Akhir Spreadsheet</th>
                                <th>Selisih</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($results as $result)
                                <tr>
                                    <td>{{ $result['nama'] }}</td>
                                    <td>{{ $result['satuan'] }}</td>
                                    <td>{{ number_format($result['saldo_sistem'], 0, ',', '.') }}</td>
                                    <td>{{ number_format($result['saldo_spreadsheet'], 0, ',', '.') }}</td>
                                    <td class="{{ $result['selisih'] != 0 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($result['selisih'], 0, ',', '.') }}
                                    </td>
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
    .btn-import { background: #1abc9c; border: none; border-radius: 25px; transition: all 0.3s ease; }
    .btn-import:hover { background: #16a085; transform: translateY(-2px); }
</style>
@endsection