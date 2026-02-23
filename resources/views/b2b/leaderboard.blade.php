@extends('layouts.app')

@section('title', 'Leader Board')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h4 class="mb-0">Leader Board B2B</h4>
        <form method="GET" class="d-flex gap-2">
            <input type="month" name="month" value="{{ $monthValue }}" class="form-control">
            <button class="btn btn-outline-primary">Filter</button>
        </form>
    </div>

    <p class="text-muted">Periode: {{ $monthLabel }}</p>

    @php
        $rowCollection = collect($rows ?? []);
        $championRows = $rowCollection->filter(fn ($item) => ($item['points'] ?? 0) >= 201)->take(10)->values();
        $risingRows = $rowCollection->filter(fn ($item) => ($item['points'] ?? 0) >= 101 && ($item['points'] ?? 0) <= 200)->take(10)->values();
        $rookieRows = $rowCollection->filter(fn ($item) => ($item['points'] ?? 0) >= 0 && ($item['points'] ?? 0) <= 100)->take(10)->values();
        $categories = [
            ['title' => 'Champion', 'range' => '201+ poin', 'rows' => $championRows, 'badge' => 'img/champion.png'],
            ['title' => 'Rising Star', 'range' => '101-200 poin', 'rows' => $risingRows, 'badge' => 'img/rising_star.png'],
            ['title' => 'Rookie', 'range' => '0-100 poin', 'rows' => $rookieRows, 'badge' => 'img/rookie.png'],
        ];
    @endphp

    @foreach ($categories as $category)
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="{{ asset($category['badge']) }}"
                         alt="Badge {{ $category['title'] }}"
                         style="width: 56px; height: 56px; object-fit: contain;">
                    <div>
                        <h5 class="mb-1">{{ $category['title'] }}</h5>
                        <p class="text-muted mb-0">Top 10 - {{ $category['range'] }}</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Nama AM</th>
                                <th class="text-end">Jumlah Klien</th>
                                <th class="text-end">Total Topup</th>
                                <th class="text-end">Poin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($category['rows'] as $index => $row)
                                <tr>
                                    <td>#{{ $index + 1 }}</td>
                                    <td>{{ $row['name'] }}</td>
                                    <td class="text-end">{{ $row['client_count'] }}</td>
                                    <td class="text-end">Rp {{ number_format($row['total_topup'], 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold">{{ $row['points'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Belum ada data untuk kategori ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
