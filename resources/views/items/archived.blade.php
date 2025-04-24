@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h1 class="fw-bold text-dark" style="font-family: 'Poppins', sans-serif; color: #2c3e50; text-shadow: 2px 2px 4px rgba(0,0,0,0.1);">Arsip Item</h1>
        <a href="{{ route('items.index') }}" class="btn btn-archive" style="border-radius: 25px;">
            <i class="fas fa-arrow-left me-2"></i> Kembali ke List Item
        </a>
    </div>

    <div class="card shadow-lg" style="border-radius: 15px;">
        <div class="card-header bg-gradient-custom text-white">
            <h5 class="mb-0" style="font-family: 'Poppins', sans-serif;">Daftar Item Diarsipkan</h5>
        </div>
        <div class="card-body">
            <div class="accordion" id="archivedAccordion">
                @forelse($allDates as $date)
                    <?php
                        $groupedItems = $items->filter(function ($item) use ($date) {
                            return $item->stockHistories->contains(function ($history) use ($date) {
                                return \Carbon\Carbon::parse($history->tanggal)->toDateString() === $date;
                            });
                        });
                    ?>
                    @if($groupedItems->isNotEmpty())
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-{{ md5($date) }}">
                                <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ md5($date) }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="collapse-{{ md5($date) }}">
                                    <i class="fas fa-folder me-2"></i>
                                    {{ \Carbon\Carbon::parse($date)->format('d M Y') }} 
                                    ({{ $groupedItems->count() }} Item)
                                </button>
                            </h2>
                            <div id="collapse-{{ md5($date) }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" aria-labelledby="heading-{{ md5($date) }}" data-bs-parent="#archivedAccordion">
                                <div class="accordion-body">
                                    <div class="table-responsive">
                                        <table class="table table-custom">
                                            <thead>
                                                <tr>
                                                    <th>Kode</th>
                                                    <th>Nama</th>
                                                    <th>Stok Terakhir</th>
                                                    <th>Satuan</th>
                                                    <th>Tanggal Update</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($groupedItems as $item)
                                                    <?php
                                                        $historyForDate = $item->stockHistories->firstWhere(function ($history) use ($date) {
                                                            return \Carbon\Carbon::parse($history->tanggal)->toDateString() === $date;
                                                        });
                                                    ?>
                                                    <tr>
                                                        <td>{{ $item->kode }}</td>
                                                        <td>{{ $item->nama }}</td>
                                                        <td>{{ $historyForDate ? number_format($historyForDate->stok, 2) : '-' }}</td>
                                                        <td>{{ $item->satuan }}</td>
                                                        <td>{{ $historyForDate ? \Carbon\Carbon::parse($historyForDate->tanggal)->format('d/m/Y') : '-' }}</td>
                                                        <td>
                                                            <a href="{{ route('items.showArchived', $item->id) }}?date={{ $date }}" class="btn btn-detail"><i class="fas fa-eye"></i></a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="text-center py-5">
                        <i class="fas fa-folder-open fa-4x text-muted mb-3" style="color: #bdc3c7;"></i>
                        <p class="text-muted" style="font-family: 'Poppins', sans-serif; font-size: 1.2rem;">Tidak ada item diarsipkan.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .bg-gradient-custom { 
        background: linear-gradient(90deg, #2ecc71, #27ae60); 
    }
    .accordion-button {
        font-family: 'Poppins', sans-serif;
        font-size: 1rem;
        background-color: #f8f9fa;
        color: #2c3e50;
    }
    .accordion-button:not(.collapsed) {
        background-color: #e9ecef;
        color: #27ae60;
    }
    .accordion-button:focus {
        box-shadow: none;
    }
    .table-custom th, .table-custom td { 
        padding: 1rem; 
        font-family: 'Poppins', sans-serif; 
        font-size: 0.9rem;
    }
    .table-custom tr:hover { 
        background: #f0f3f5; 
    }
    .btn-archive { 
        background: #34495e; 
        border: none; 
        color: white;
        transition: all 0.3s ease; 
    }
    .btn-archive:hover { 
        background: #2c3e50; 
        color: white;
        transform: translateY(-2px); 
    }
    .btn-detail { 
        background: #9b59b6; 
        border: none; 
        border-radius: 20px; 
        color: white;
        transition: all 0.3s ease; 
    }
    .btn-detail:hover { 
        background: #8e44ad; 
        transform: translateY(-2px); 
    }

    /* Responsive Adjustments */
    @media (max-width: 767px) {
        .btn-archive {
            width: 100%;
            margin: 0.5rem 0;
        }
        .accordion-button {
            font-size: 0.9rem;
        }
        .table-custom th, .table-custom td {
            padding: 0.5rem;
            font-size: 0.85rem;
        }
        .btn-detail {
            padding: 0.2rem 0.6rem;
            font-size: 0.8rem;
        }
    }
</style>
@endpush