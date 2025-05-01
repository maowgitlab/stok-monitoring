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
                        <textarea name="spreadsheet_data" id="spreadsheet_data" class="form-control shadow-sm" rows="10" placeholder="Paste data spreadsheet (tab-separated)">{{ $spreadsheetData ?? old('spreadsheet_data') }}</textarea>
                        <div class="form-text">Contoh format: NAMA BAHAN BAKU [tab] SATUAN [tab] STOCK FISIK AWAL [tab] SALDO AWAL [tab] MASUK [tab] KELUAR [tab] SALDO AKHIR</div>
                    </div>
                    <div class="col-md-12">
                        <label for="excel_data" class="form-label">Data Excel (Paste di sini)</label>
                        <textarea name="excel_data" id="excel_data" class="form-control shadow-sm" rows="10" placeholder="Paste data Excel (tab-separated)">{{ $excelData ?? old('excel_data') }}</textarea>
                        <div class="form-text">Contoh format: Nama Barang [tab] Satuan [tab] STOCK AWAL [tab] PRODUKSI [tab] LOGISTIK PUSAT [tab] TOTAL IN [tab] Total stock out [tab] Stock Akhir</div>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-import">Bandingkan</button>
                    </div>
                </div>
            </form>

            @if (!empty($results))
                <hr class="my-4">
                <h5 class="mb-3">Hasil Komparasi</h5>
                <button type="button" class="btn btn-import mb-3" onclick="toggleNonZero()">Tampilkan Selisih != 0</button>
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Nama Bahan Baku</th>
                                <th>Satuan Sistem</th>
                                <th>Satuan Excel</th>
                                <th>Saldo Akhir Sistem</th>
                                <th>Saldo Akhir Spreadsheet</th>
                                <th>Selisih Sistem-Spreadsheet</th>
                                <th> controls
                                <th>Selisih Sistem-Excel</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($results as $result)
                                <tr class="{{ $result['selisih_sistem_spreadsheet'] != 0 || $result['selisih_sistem_excel'] != 0 ? 'highlight' : '' }}">
                                    <td>{{ $result['nama'] }}</td>
                                    <td>{{ $result['satuan'] }}</td>
                                    <td>{{ $result['satuan_excel'] }}</td>
                                    <td>{{ number_format($result['saldo_sistem'], ($result['satuan'] === 'pcs' ? 3 : 0), ',', '.') }}</td>
                                    <td>{{ number_format($result['saldo_spreadsheet'], ($result['satuan'] === 'pcs' ? 3 : 0), ',', '.') }}</td>
                                    <td class="{{ $result['selisih_sistem_spreadsheet'] != 0 ? 'text-danger' : 'text-success' }}">
                                        {{ $result['selisih_sistem_spreadsheet'] == 0 ? '0' : number_format($result['selisih_sistem_spreadsheet'], ($result['satuan'] === 'pcs' ? 3 : 0), ',', '.') }}
                                    </td>
                                    <td>{{ number_format($result['saldo_excel'], ($result['satuan'] === 'pcs' ? 3 : 0), ',', '.') }}</td>
                                    <td class="{{ $result['selisih_sistem_excel'] != 0 ? 'text-danger' : 'text-success' }}">
                                        {{ $result['selisih_sistem_excel'] == 0 ? '0' : number_format($result['selisih_sistem_excel'], ($result['satuan'] === 'pcs' ? 3 : 0), ',', '.') }}
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
    .highlight { background-color: #fff3cd; }
</style>

<script>
    function toggleNonZero() {
        document.querySelectorAll('.table-custom tbody tr').forEach(row => {
            const selisihSpreadsheet = parseFloat(row.cells[5].textContent.replace(/[^0-9.-]/g, '')) || 0;
            const selisihExcel = parseFloat(row.cells[7].textContent.replace(/[^0-9.-]/g, '')) || 0;
            row.style.display = (selisihSpreadsheet !== 0 || selisihExcel !== 0) ? '' : 'none';
        });
    }
</script>
@endsection