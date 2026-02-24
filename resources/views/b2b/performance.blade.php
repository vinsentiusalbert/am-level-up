@extends('layouts.app')

@section('title', 'Daftar Performansi')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h4 class="mb-0">Daftar Performansi</h4>
        <form method="GET" class="d-flex gap-2">
            <input type="month" name="month" value="{{ $monthValue }}" class="form-control">
            <button class="btn btn-outline-primary">Filter</button>
        </form>
    </div>

    <p class="text-muted">Periode: {{ $monthLabel }}</p>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted">Jumlah Klien Under</div>
                    <div class="fs-3 fw-bold">{{ $summary['client_count'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted">Total Topup Klien</div>
                    <div class="fs-3 fw-bold">Rp {{ number_format($summary['total_topup'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted">Total Poin User</div>
                    <div class="fs-3 fw-bold">{{ $summary['points'] }}</div>
                    <small class="text-muted">Poin akhir setelah dikurangi poin redeem.</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted">Poin Redeem</div>
                    <div class="fs-3 fw-bold">{{ $summary['total_redeem_point'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="mb-3">Rincian Topup per Klien</h5>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama PIC</th>
                            <th>Perusahaan</th>
                            <th>Email PIC</th>
                            <th>Akun MyAds</th>
                            <th class="text-end">Topup</th>
                            <th class="text-end">Poin</th>
                            <th class="text-end">Poin Paket</th>
                            <th class="text-end">Poin Sisa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($summary['clients'] as $index => $row)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $row['customer_name'] ?: '-' }}</td>
                                <td>{{ $row['company_name'] }}</td>
                                <td>{{ $row['customer_email'] }}</td>
                                <td>{{ $row['myads_account'] }}</td>
                                <td class="text-end">Rp {{ number_format($row['topup'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($row['point_decimal'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ $row['point_package'] }}</td>
                                <td class="text-end">{{ number_format($row['point_sisa'], 0, ',', '.')  }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">Belum ada data klien.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
