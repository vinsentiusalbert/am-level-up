@extends('layouts.app')

@section('title', 'Reward')

@section('content')
<style>
    .redeem-btn-enabled {
        background: linear-gradient(135deg, #f59e0b, #f97316);
        border: 0;
        color: #fff;
        font-weight: 700;
        box-shadow: 0 8px 18px rgba(245, 158, 11, 0.35);
    }
    .redeem-btn-enabled:hover {
        filter: brightness(1.05);
        color: #fff;
    }
    .redeem-btn-disabled {
        background: #e5e7eb !important;
        border: 1px solid #d1d5db !important;
        color: #6b7280 !important;
        opacity: 1 !important;
        cursor: not-allowed !important;
        box-shadow: none !important;
    }
    .redeem-status {
        font-size: 12px;
        font-weight: 600;
        border-radius: 999px;
        padding: 4px 10px;
        display: inline-block;
    }
</style>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h4 class="mb-0">Reward</h4>
        <form method="GET" class="d-flex gap-2">
            <input type="month" name="month" value="{{ $monthValue }}" class="form-control">
            <button class="btn btn-outline-primary">Filter</button>
        </form>
    </div>

    <p class="text-muted">Periode poin: {{ $monthLabel }}</p>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted">Jumlah Klien</div>
                    <div class="fs-3 fw-bold">{{ $summary['client_count'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted">Total Topup</div>
                    <div class="fs-3 fw-bold">Rp {{ number_format($summary['total_topup'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted">Poin Tersedia</div>
                    <div class="fs-3 fw-bold">{{ $availablePoints }}</div>
                </div>
            </div>
        </div>
    </div>

    @if($redeem)
        <div class="alert alert-info">
            Anda sudah redeem hadiah. Point terpakai: <strong>{{ $redeem->point_used }}</strong>.
        </div>
    @endif

    <div class="row g-3">
        @forelse ($prizes as $prize)
            @php
                $img = trim((string) ($prize->img ?? ''));
                $isDisabled = ($availablePoints < $prize->point || $prize->stock < 1 || $redeem);

                if ($img === '') {
                    $imageUrl = null;
                } elseif (str_starts_with($img, 'http://') || str_starts_with($img, 'https://') || str_starts_with($img, '/')) {
                    $imageUrl = $img;
                } elseif (str_starts_with($img, 'img/')) {
                    $imageUrl = asset($img);
                } else {
                    $imageUrl = asset('img/' . $img);
                }
            @endphp
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        @if ($imageUrl)
                            <div class="text-center mb-3">
                                <img src="{{ $imageUrl }}"
                                     alt="{{ $prize->name }}"
                                     style="max-height: 130px; width: auto; max-width: 100%; object-fit: contain;">
                            </div>
                        @endif
                        @if ($isDisabled)
                            <span class="redeem-status bg-secondary-subtle text-secondary mb-2">Belum Bisa Redeem</span>
                        @else
                            <span class="redeem-status bg-success-subtle text-success mb-2">Bisa Redeem</span>
                        @endif
                        <h5>{{ $prize->name }}</h5>
                        <p class="mb-2 text-muted">Poin: {{ $prize->point }}</p>
                        <p class="mb-3 text-muted">Stok: {{ $prize->stock }}</p>
                        <form method="POST" action="{{ route('b2b.redeem') }}" class="mt-auto">
                            @csrf
                            <input type="hidden" name="prize_id" value="{{ $prize->id }}">
                            <button type="submit"
                                    class="btn w-100 {{ $isDisabled ? 'redeem-btn-disabled' : 'redeem-btn-enabled' }}"
                                    {{ $isDisabled ? 'disabled' : '' }}>
                                {{ $isDisabled ? 'Tidak Tersedia' : 'Redeem Sekarang' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-secondary mb-0">Belum ada hadiah tersedia.</div>
            </div>
        @endforelse
    </div>
</div>
@endsection
