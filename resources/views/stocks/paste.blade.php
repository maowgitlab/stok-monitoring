@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-5 fw-bold text-dark" style="font-family: 'Poppins', sans-serif; color: #2c3e50; text-shadow: 2px 2px 4px rgba(0,0,0,0.1);">Paste Data Stok</h1>

    <div class="card shadow-lg" style="border-radius: 15px;">
        <div class="card-header bg-gradient-custom text-white">Input Stok Masuk & Keluar</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <form method="POST" action="{{ route('stocks.paste.process') }}">
                        @csrf
                        <div class="form-group mb-3">
                            <label>Paste Data Masuk (IN)</label>
                            <textarea name="pasted_data" rows="10" class="form-control shadow-sm" placeholder="Paste di sini..." required>{{ old('pasted_data') }}</textarea>
                            @error('pasted_data')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-import w-100" style="border-radius: 25px;">Proses Masuk</button>
                    </form>
                </div>
                <div class="col-md-6 mb-4">
                    <form method="POST" action="{{ route('stocks.paste.out') }}">
                        @csrf
                        <div class="form-group mb-3">
                            <label>Paste Data Keluar (OUT)</label>
                            <textarea name="pasted_data" rows="10" class="form-control shadow-sm" placeholder="Paste di sini..." required>{{ old('pasted_data') }}</textarea>
                            @error('pasted_data')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-action w-100" style="border-radius: 25px;">Proses Keluar</button>
                    </form>
                </div>
            </div>
            <hr>
            <p><strong>Format:</strong></p>
            <pre class="bg-light p-3 rounded" style="font-family: 'Poppins', sans-serif;">
07/04/2025    BANJARMASIN - GRIYA PERMATA    KECIL    AYAM ORIGINAL    pcs    180
Tanggal    Penerima    Tipe    Nama    Satuan    Qty
            </pre>
        </div>
    </div>
</div>

<style>
    .bg-gradient-custom { background: linear-gradient(90deg, #2ecc71, #27ae60); }
    .btn-import { background: #1abc9c; border: none; transition: all 0.3s ease; }
    .btn-import:hover { background: #16a085; transform: translateY(-2px); }
    .btn-action { background: #e67e22; border: none; transition: all 0.3s ease; }
    .btn-action:hover { background: #d35400; transform: translateY(-2px); }
</style>
@endsection