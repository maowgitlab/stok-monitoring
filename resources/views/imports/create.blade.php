@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-5 fw-bold text-dark" style="font-family: 'Poppins', sans-serif; color: #2c3e50; text-shadow: 2px 2px 4px rgba(0,0,0,0.1);">Import Data Stok</h1>

    <div class="card shadow-lg" style="border-radius: 15px;">
        <div class="card-header bg-gradient-custom text-white">Form Import</div>
        <div class="card-body">
            <form action="{{ route('imports.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label for="tanggal" class="form-label">Tanggal Data</label>
                    <input type="date" class="form-control shadow-sm @error('tanggal') is-invalid @enderror" id="tanggal" name="tanggal" value="{{ old('tanggal') }}" required>
                    @error('tanggal')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-4">
                    <label for="file" class="form-label">File Excel</label>
                    <input type="file" class="form-control shadow-sm @error('file') is-invalid @enderror" id="file" name="file" required>
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">File harus dalam format Excel (.xlsx, .xls) dengan kolom: No, Kode, Bahan_Baku, Stok, Satuan</div>
                </div>
                <button type="submit" class="btn btn-import w-100" style="border-radius: 25px;">Import Data</button>
            </form>

            <hr class="my-4">

            <form action="{{ route('imports.destroySelected') }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data yang dipilih?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm mb-3" style="border-radius: 20px;">Hapus Terpilih</button>
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="checkAll"></th>
                                <th>Tanggal</th>
                                <th>Nama File</th>
                                <th>Status</th>
                                <th>Jumlah Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentImports as $import)
                                <tr>
                                    <td><input type="checkbox" name="selected_imports[]" value="{{ $import->id }}"></td>
                                    <td>{{ $import->tanggal_import->format('d/m/Y') }}</td>
                                    <td>{{ $import->nama_file }}</td>
                                    <td>
                                        <span class="badge {{ $import->status == 'success' ? 'bg-success' : ($import->status == 'processing' ? 'bg-warning' : 'bg-danger') }}">
                                            {{ ucfirst($import->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $import->jumlah_data }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .bg-gradient-custom { background: linear-gradient(90deg, #2ecc71, #27ae60); }
    .table-custom th, .table-custom td { padding: 1rem; font-family: 'Poppins', sans-serif; }
    .table-custom tr:hover { background: #f0f3f5; }
    .btn-import { background: #1abc9c; border: none; transition: all 0.3s ease; }
    .btn-import:hover { background: #16a085; transform: translateY(-2px); }
</style>
@endsection

@push('scripts')
<script>
    document.getElementById('checkAll').addEventListener('change', function () {
        document.querySelectorAll('input[name="selected_imports[]"]').forEach(cb => cb.checked = this.checked);
    });
</script>
@endpush