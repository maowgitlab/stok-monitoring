@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h1 class="fw-bold text-dark" style="font-family: 'Poppins', sans-serif; color: #2c3e50; text-shadow: 2px 2px 4px rgba(0,0,0,0.1);">Daftar Item</h1>
        <div class="action-buttons mt-2 mt-md-0">
            <a href="{{ route('imports.create') }}" class="btn btn-import me-2">
                <i class="fas fa-file-import me-1"></i> Impor
            </a>
            <a href="{{ route('stocks.paste') }}" class="btn btn-paste me-2">
                <i class="fas fa-paste me-1"></i> Paste
            </a>
            <a href="{{ route('items.archived') }}" class="btn btn-archive me-2">
                <i class="fas fa-archive me-1"></i> Arsip
            </a>
            <form action="{{ route('items.archive') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-action me-2" onclick="return confirm('Yakin ingin mengarsipkan semua data aktif?')">
                    <i class="fas fa-archive me-1"></i> Arsipkan
                </button>
            </form>
            <form action="{{ route('items.destroyAll') }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-delete" onclick="return confirm('Yakin ingin menghapus semua item aktif dan histori stoknya? Data yang dihapus tidak dapat dikembalikan.')">
                    <i class="fas fa-trash me-1"></i> Hapus Semua
                </button>
            </form>
        </div>
    </div>

    <!-- Form Pencarian -->
    <div class="card shadow-lg mb-4" style="border-radius: 15px; background: white;">
        <div class="card-body">
            <form method="GET" action="{{ route('items.index') }}">
                <div class="input-group">
                    <input type="text" name="search" class="form-control search-input" placeholder="Cari nama/kode item..." value="{{ request('search') }}">
                    <button type="submit" class="btn btn-search">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Item -->
    <div class="card shadow-lg" style="border-radius: 15px;">
        <div class="card-header bg-gradient-custom text-white">
            <h5 class="mb-0" style="font-family: 'Poppins', sans-serif;"><i class="fas fa-list me-2"></i> List Item Aktif</h5>
        </div>
        <div class="card-body p-0">
            @if($items->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Kode</th>
                                <th>Nama</th>
                                <th>Satuan</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                            <tr>
                                <td class="ps-3">{{ $item->kode }}</td>
                                <td>{{ $item->nama }}</td>
                                <td>{{ $item->satuan }}</td>
                                <td>{{ $item->tanggal_stok ? \Carbon\Carbon::parse($item->tanggal_stok)->format('d/m/Y') : '-' }}</td>
                                <td>
                                    <a href="{{ route('items.show', $item->id) }}" class="btn btn-detail btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state text-center py-5">
                    <i class="fas fa-box-open fa-4x text-muted mb-3" style="color: #bdc3c7; animation: float 3s ease-in-out infinite;"></i>
                    <p class="text-muted" style="font-family: 'Poppins', sans-serif; font-size: 1.2rem;">Belum ada item aktif saat ini.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4 d-flex justify-content-center">
        {{ $items->links() }}
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Action Buttons */
    .action-buttons .btn {
        transition: all 0.3s ease;
        font-family: 'Poppins', sans-serif;
        border-radius: 20px;
        padding: 0.4rem 1rem;
        color: white;
        border: none;
        font-size: 0.9rem;
    }
    .btn-import { background: #1abc9c; }
    .btn-import:hover { background: #16a085; transform: translateY(-2px); }
    .btn-paste { background: #3498db; }
    .btn-paste:hover { background: #2980b9; transform: translateY(-2px); }
    .btn-archive { background: #34495e; }
    .btn-archive:hover { background: #2c3e50; transform: translateY(-2px); }
    .btn-action { background: #f39c12; }
    .btn-action:hover { background: #e67e22; transform: translateY(-2px); }
    .btn-delete { background: #e74c3c; }
    .btn-delete:hover { background: #c0392b; transform: translateY(-2px); }

    /* Search Bar */
    .search-input {
        border: none;
        font-family: 'Poppins', sans-serif;
        padding: 0.6rem 1rem;
        border-radius: 20px 0 0 20px;
    }
    .search-input:focus {
        box-shadow: none;
        border: none;
    }
    .btn-search {
        background: #3498db;
        border: none;
        color: white;
        border-radius: 0 20px 20px 0;
        padding: 0.6rem 1rem;
        transition: all 0.3s ease;
    }
    .btn-search:hover {
        background: #2980b9;
    }

    /* Table Card */
    .bg-gradient-custom {
        background: linear-gradient(90deg, #2ecc71, #27ae60);
        padding: 0.8rem;
    }
    .table-custom {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
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
    .table-custom tr {
        transition: all 0.3s ease;
    }
    .table-custom tr:hover {
        background: #f0f3f5;
    }
    .btn-detail {
        background: #9b59b6;
        color: white;
        border: none;
        border-radius: 15px;
        padding: 0.3rem 0.8rem;
        transition: all 0.3s ease;
    }
    .btn-detail:hover {
        background: #8e44ad;
        transform: translateY(-2px);
    }

    /* Pagination */
    .pagination {
        font-family: 'Poppins', sans-serif;
        display: flex;
        justify-content: center;
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .pagination li {
        margin: 0 0.2rem;
    }
    .pagination a, .pagination span {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 10px;
        text-decoration: none;
        color: #3498db;
        border: 1px solid #3498db;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }
    .pagination .active span {
        background-color: #3498db;
        color: white;
        border-color: #3498db;
    }
    .pagination .disabled span {
        color: #bdc3c7;
        border-color: #bdc3c7;
        cursor: not-allowed;
    }
    .pagination a:hover {
        background-color: #2980b9;
        color: white;
        border-color: #2980b9;
        transform: translateY(-2px);
    }

    /* Empty State */
    .empty-state i {
        animation: float 3s ease-in-out infinite;
    }
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }

    /* Responsive Adjustments */
    @media (max-width: 767px) {
        .action-buttons .btn {
            display: block;
            width: 100%;
            margin: 0.5rem 0;
        }
        .table-custom thead th, .table-custom td {
            font-size: 0.8rem;
            padding: 0.5rem;
        }
        .btn-detail {
            padding: 0.2rem 0.6rem;
            font-size: 0.8rem;
        }
        .search-input, .btn-search {
            font-size: 0.9rem;
            padding: 0.5rem;
        }
        .pagination {
            flex-wrap: wrap;
        }
        .pagination a, .pagination span {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }
    }
    @media (min-width: 768px) {
        .action-buttons .btn {
            display: inline-block;
            width: auto;
        }
    }
</style>
@endpush