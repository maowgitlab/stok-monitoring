@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-5 fw-bold text-dark" style="font-family: 'Poppins', sans-serif; color: #2c3e50; text-shadow: 2px 2px 4px rgba(0,0,0,0.1);">Laporan WhatsApp</h1>

    <!-- Tabel Cold Storage -->
    <div class="card shadow-lg mb-4" style="border-radius: 15px;">
        <div class="card-header bg-gradient-custom text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Item Cold Storage</h5>
            <button type="button" class="btn btn-import btn-sm" data-bs-toggle="modal" data-bs-target="#addColdStorageModal">
                <i class="fas fa-plus me-1"></i> Tambah Item
            </button>
        </div>
        <div class="card-body p-0">
            @if($coldStorages->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Nama Item</th>
                                <th>Jumlah Cold Storage</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($coldStorages as $coldStorage)
                            <tr>
                                <td class="ps-3">{{ $coldStorage->item_name }}</td>
                                <td>{{ number_format($coldStorage->cold_storage_qty, 0, ',', '.') }}</td>
                                <td>
                                    <button type="button" class="btn btn-detail btn-sm me-1" data-bs-toggle="modal" data-bs-target="#editColdStorageModal{{ $coldStorage->id }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('cold-storages.destroy', $coldStorage) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-delete btn-sm" onclick="return confirm('Yakin ingin menghapus item {{ $coldStorage->item_name }} dari cold storage?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <!-- Modal Edit -->
                            <div class="modal fade" id="editColdStorageModal{{ $coldStorage->id }}" tabindex="-1" aria-labelledby="editColdStorageModalLabel{{ $coldStorage->id }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editColdStorageModalLabel{{ $coldStorage->id }}">Edit Cold Storage: {{ $coldStorage->item_name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="{{ route('cold-storages.update', $coldStorage) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label for="cold_storage_qty_{{ $coldStorage->id }}" class="form-label">Jumlah Cold Storage</label>
                                                    <input type="number" name="cold_storage_qty" id="cold_storage_qty_{{ $coldStorage->id }}" class="form-control" value="{{ $coldStorage->cold_storage_qty }}" min="0" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-import">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state text-center py-5">
                    <i class="fas fa-box-open fa-4x text-muted mb-3" style="color: #bdc3c7;"></i>
                    <p class="text-muted" style="font-family: 'Poppins', sans-serif; font-size: 1.2rem;">Belum ada item cold storage.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Tambah -->
    <div class="modal fade" id="addColdStorageModal" tabindex="-1" aria-labelledby="addColdStorageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addColdStorageModalLabel">Tambah Item Cold Storage</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('cold-storages.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="item_name" class="form-label">Nama Item</label>
                            <select name="item_name" id="item_name" class="form-control" required>
                                <option value="" disabled selected>Pilih item</option>
                                @foreach($allowedItems as $item)
                                    @if(!$coldStorages->pluck('item_name')->contains($item))
                                        <option value="{{ $item }}">{{ $item }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="cold_storage_qty" class="form-label">Jumlah Cold Storage</label>
                            <input type="number" name="cold_storage_qty" id="cold_storage_qty" class="form-control" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-import">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Form Filter Tanggal -->
    <div class="card shadow-lg" style="border-radius: 15px;">
        <div class="card-header bg-gradient-custom text-white">Filter Tanggal</div>
        <div class="card-body">
            <form method="GET" action="{{ route('items.whatsapp-report') }}" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="tanggal" class="form-label">Pilih Tanggal</label>
                        <input type="date" name="tanggal" id="tanggal" class="form-control shadow-sm" value="{{ request('tanggal') }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-import mx-2">Tampilkan</button>
                        <button type="button" class="btn btn-action" onclick="copyText()">Copy ke Clipboard</button>
                    </div>
                </div>
            </form>
            <div class="row">
                <div class="col mb-3">
                    <form method="POST" action="{{ route('items.send-telegram') }}">
                        @csrf
                        <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                        <button type="submit" class="btn btn-telegram">Kirim ke Telegram</button>
                    </form>
                </div>
            </div>
            <textarea class="form-control shadow-sm" rows="25" readonly style="font-family: 'Poppins', sans-serif; resize: none;" placeholder="Pilih tanggal terlebih dahulu untuk melihat laporan.">{{ $report ?? '' }}</textarea>
        </div>
    </div>
</div>

<style>
    .bg-gradient-custom { background: linear-gradient(90deg, #2ecc71, #27ae60); }
    .btn-import { background: #1abc9c; border: none; border-radius: 25px; transition: all 0.3s ease; color: white; }
    .btn-import:hover { background: #16a085; transform: translateY(-2px); }
    .btn-action { background: #f39c12; border: none; border-radius: 25px; transition: all 0.3s ease; color: white; }
    .btn-action:hover { background: #e67e22; transform: translateY(-2px); }
    .btn-telegram { background: #0088cc; border: none; border-radius: 25px; transition: all 0.3s ease; color: white; }
    .btn-telegram:hover { background: #006699; transform: translateY(-2px); }
    .btn-detail { background: #9b59b6; border: none; border-radius: 15px; transition: all 0.3s ease; color: white; }
    .btn-detail:hover { background: #8e44ad; transform: translateY(-2px); }
    .btn-delete { background: #e74c3c; border: none; border-radius: 15px; transition: all 0.3s ease; color: white; }
    .btn-delete:hover { background: #c0392b; transform: translateY(-2px); }
    .table-custom thead th {
        background: #ecf0f1;
        color: #2c3e50;
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        padding: 0.8rem;
        text-transform: uppercase;
        font-size: 0.85rem;
    }
    .table-custom td {
        padding: 0.8rem;
        vertical-align: middle;
        font-family: 'Poppins', sans-serif;
        color: #34495e;
        font-size: 0.9rem;
    }
    .table-custom tr:hover { background: #f0f3f5; }
    .modal-content { border-radius: 15px; }
    .modal-header { background: #2ecc71; color: white; }
    .form-control, .form-select {
        font-family: 'Poppins', sans-serif;
        border-radius: 10px;
    }
    @media (max-width: 767px) {
        .table-custom thead th, .table-custom td {
            font-size: 0.8rem;
            padding: 0.5rem;
        }
        .btn-detail, .btn-delete {
            padding: 0.2rem 0.6rem;
            font-size: 0.8rem;
        }
    }
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