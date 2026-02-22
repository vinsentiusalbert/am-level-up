@extends('layouts.app')

@section('title', 'AM Level UP')

@section('content')
<div class="container my-5 am-shell">
<section class="am-hero section-card mb-5">
    <p class="am-eyebrow mb-2">Program Reward 2026</p>
    <h1 class="am-title mb-2">AM Level UP</h1>
    <p class="am-subtitle mb-0">Kumpulkan poin, naik badge, dan redeem hadiah terbaikmu.</p>
    @auth('web')
        @php
            $badgeName = 'Rookie';
            $badgeKey = 'poin_0_100';

            if (isset($point) && is_object($point) && isset($point->poin)) {
                if ($point->poin <= 100) {
                    $badgeName = 'Rookie';
                    $badgeKey = 'poin_0_100';
                } elseif ($point->poin <= 200) {
                    $badgeName = 'Rising Star';
                    $badgeKey = 'poin_101_200';
                } else {
                    $badgeName = 'Champion';
                    $badgeKey = 'poin_201_300';
                }
            }

            $badgeRows = collect($data[$badgeKey] ?? []);
            $currentEmail = auth()->user()->email_client ?? null;
            $rankIndex = $badgeRows->search(fn ($row) => ($row['email_client'] ?? null) === $currentEmail);
            $badgeRank = $rankIndex === false ? null : ($rankIndex + 1);
        @endphp
        <div class="am-kpis mt-4">
            <div class="am-kpi">
                <span class="am-kpi-label">Total Poin</span>
                <strong class="am-kpi-value">{{ (isset($point) && is_object($point) && isset($point->poin)) ? $point->poin : 0 }}</strong>
            </div>
            <div class="am-kpi">
                <span class="am-kpi-label">Badge</span>
                <strong class="am-kpi-value">{{ $badgeName }}</strong>
            </div>
            <div class="am-kpi">
                <span class="am-kpi-label">Peringkat di Badge Ini</span>
                <strong class="am-kpi-value">
                    {{ $badgeRank ? ('#' . $badgeRank) : 'Di luar Top 10' }}
                </strong>
            </div>
        </div>
    @endauth
</section>

{{-- ================= LIGA ================= --}}
@if(isset($point) && is_object($point) && isset($point->poin))
<div class="section-card text-center mb-5">
    <h2 class="mb-4">Badges</h2>

    {{-- <div class="row justify-content-center g-4">
        <div class="col-md-4 liga-card">
            <img src="{{ asset('img/rookie.png') }}">
            <h5>Rookie</h5>
            <span class="liga-range">0 – 100 Poin</span>
        </div>
        <div class="col-md-4 liga-card">
            <img src="{{ asset('img/rising_star.png') }}">
            <h5>Rising Star</h5>
            <span class="liga-range">101 – 200 Poin</span>
        </div>
        <div class="col-md-4 liga-card">
            <img src="{{ asset('img/champion.png') }}">
            <h5>Champion</h5>
            <span class="liga-range">201 – 300 Poin</span>
        </div>
    </div> --}}
    
        <div class="mt-4">
            @php
                $percent = min(($point->poin / 300) * 100, 100);
            @endphp
            <div class="row justify-content-center g-4">
                @if($point->poin >= 0 && $point->poin <= 100)
                <div class="col-md-4 liga-card">
                    <img src="{{ asset('img/rookie.png') }}">
                    <h5>Rookie</h5>
                    
                </div>
                @elseif($point->poin >= 101 && $point->poin <= 200)
                <div class="col-md-4 liga-card">
                    <img src="{{ asset('img/rising_star.png') }}">
                    <h5>Rising Star</h5>
                    
                </div>
                @elseif($point->poin >= 201 && $point->poin <= 300)
                <div class="col-md-4 liga-card">
                    <img src="{{ asset('img/champion.png') }}">
                    <h5>Champion</h5>
                    
                </div>
                @endif
            </div>
            <div class="progress">
                <div 
                    class="progress-bar progress-animate"
                    data-percent="{{ $percent }}"
                    style="width: 0%">
                </div>
            </div>

            <small>Total Poin Anda: <b>{{ $point->poin }}</b></small>
        </div>

</div>

    @endif

{{-- ================= TABLE ================= --}}
<div class="section-card mb-5">
    <h4 class="mb-3">TOP 10 Champion</h4>

    <div class="">
        <div class="row">
            <div class="col-md-3 liga-card text-center animate-left scroll-animate my-2">
                <img src="{{ asset('img/champion.png') }}">
                <h5>Champion</h5>
                <span class="liga-range">201 – 300 Poin</span>
            </div>
            <div class="col-md-9">
                <div class="table-glass liga-champion animate-right scroll-animate">
                    
                    <div class="table-scroll-x">
                    <table class="table table-transparent align-middle mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            {{-- <th>ID</th> --}}
                            <th>Nama Akun</th>
                            <th>Nama Pelanggan</th>
                            <th>Canvasser</th>
                            <th>Total Poin</th>
                            <th>Kategori Liga</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data['poin_201_300'] as $index => $row)

                            @php
                                // Tentukan kategori liga
                                if ($row['poin'] <= 100) {
                                    $liga = 'Rookie';
                                } elseif ($row['poin'] <= 200) {
                                    $liga = 'Rising Star';
                                } else {
                                    $liga = 'Champion';
                                }
                            @endphp

                            <tr>
                                @php
                                    $email = $row['email_client'];
                                    [$name, $domain] = explode('@', $email);

                                    $maskedName = substr($name, 0, 2)
                                        . str_repeat('*', max(strlen($name) - 2, 0));

                                    $maskedDomain = substr($domain, 0, 2)
                                        . str_repeat('*', max(strlen($domain) - 2, 0));
                                @endphp
                                <td>{{ $index + 1 }}</td>
                                {{-- <td>{{ $uuid }}</td> --}}
                                <td>{{
                                    substr($row['nama_akun'], 0, 2) . str_repeat('*', strlen($row['nama_akun']) - 2)
                                }}</td>
                                {{-- <td>{{$row['nama_pelanggan']}}</td> --}}
                                <td>{{ $maskedName .'@'. $maskedDomain }}</td>
                                
                                <td>{{ $row['nama_canvasser'] }}</td>
                                <td>
                                    <span class="fw-bold text-warning">
                                        {{ $row['poin'] }}
                                    </span>
                                </td>
                                <td style="text-align: center">
                                    <span class="badge-liga 
                                        {{ $liga == 'Rookie' ? 'bg-secondary' : '' }}
                                        {{ $liga == 'Rising Star' ? 'bg-info' : '' }}
                                        {{ $liga == 'Champion' ? 'bg-success' : '' }}
                                    ">
                                        {{ $liga }}
                                    </span>
                                </td>
                            </tr>

                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                Data belum tersedia
                            </td>
                        </tr>
                    @endforelse
                    </tbody>

                </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="section-card mb-5">
    <h4 class="mb-3">TOP 10 Rising Star</h4>

    <div class="">
        <div class="row">
            <div class="col-md-3 liga-card text-center animate-left scroll-animate my-2">
                <img src="{{ asset('img/rising_star.png') }}">
                <h5>Rising Star</h5>
                <span class="liga-range">101 – 200 Poin</span>
            </div>
            <div class="col-md-9">
                <div class="table-glass liga-rising  animate-right scroll-animate">
                    <div class="table-scroll-x">
                    <table class="table table-transparent align-middle mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            {{-- <th>ID</th> --}}
                            <th>Nama Akun</th>
                            <th>Nama Pelanggan</th>
                            <th>Canvasser</th>
                            <th>Total Poin</th>
                            <th>Kategori Liga</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data['poin_101_200'] as $index => $row)

                            @php
                                // Tentukan kategori liga
                                if ($row['poin'] <= 100) {
                                    $liga = 'Rookie';
                                } elseif ($row['poin'] <= 200) {
                                    $liga = 'Rising Star';
                                } else {
                                    $liga = 'Champion';
                                }
                            @endphp

                            <tr>
                                @php
                                    $email = $row['email_client'];
                                    [$name, $domain] = explode('@', $email);

                                    $maskedName = substr($name, 0, 2)
                                        . str_repeat('*', max(strlen($name) - 2, 0));

                                    $maskedDomain = substr($domain, 0, 2)
                                        . str_repeat('*', max(strlen($domain) - 2, 0));
                                @endphp
                                <td>{{ $index + 1 }}</td>
                                {{-- <td>{{ $uuid }}</td> --}}
                                <td>{{
                                    substr($row['nama_akun'], 0, 2) . str_repeat('*', strlen($row['nama_akun']) - 2)
                                }}</td>
                                {{-- <td>{{$row['nama_pelanggan']}}</td> --}}
                                <td>{{ $maskedName .'@'. $maskedDomain }}</td>
                                
                                <td>{{ $row['nama_canvasser'] }}</td>
                                <td>
                                    <span class="fw-bold text-warning">
                                        {{ $row['poin'] }}
                                    </span>
                                </td>
                                <td style="text-align: center">
                                    <span class="badge-liga 
                                        {{ $liga == 'Rookie' ? 'bg-secondary' : '' }}
                                        {{ $liga == 'Rising Star' ? 'bg-info' : '' }}
                                        {{ $liga == 'Champion' ? 'bg-success' : '' }}
                                    ">
                                        {{ $liga }}
                                    </span>
                                </td>
                            </tr>

                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                Data belum tersedia
                            </td>
                        </tr>
                    @endforelse
                    </tbody>

                </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="section-card mb-5">
    <h4 class="mb-3">TOP 10 Rookie</h4>

    {{-- <div class="table-responsive table-scroll"> --}}
    <div>
        <div class="row">
            <div class="col-md-3 liga-card text-center animate-left scroll-animate my-2">
                <img src="{{ asset('img/rookie.png') }}">
                <h5>Rookie</h5>
                <span class="liga-range">0 – 100 Poin</span>
            </div>
            <div class="col-md-9">
                <div class="table-glass liga-rookie animate-right scroll-animate">
                    <div class="table-scroll-x">
                    <table class="table table-transparent align-middle mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            {{-- <th>ID</th> --}}
                            <th>Nama Akun</th>
                            <th>Nama Pelanggan</th>
                            <th>Canvasser</th>
                            <th>Total Poin</th>
                            <th>Kategori Liga</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data['poin_0_100'] as $index => $row)

                            @php
                                // Tentukan kategori liga
                                if ($row['poin'] <= 100) {
                                    $liga = 'Rookie';
                                } elseif ($row['poin'] <= 200) {
                                    $liga = 'Rising Star';
                                } else {
                                    $liga = 'Champion';
                                }
                            @endphp

                            <tr>
                                @php
                                    $email = $row['email_client'];
                                    [$name, $domain] = explode('@', $email);

                                    $maskedName = substr($name, 0, 2)
                                        . str_repeat('*', max(strlen($name) - 2, 0));

                                    $maskedDomain = substr($domain, 0, 2)
                                        . str_repeat('*', max(strlen($domain) - 2, 0));
                                @endphp
                                <td>{{ $index + 1 }}</td>
                                {{-- <td>{{ $row['uuid'] }}</td> --}}
                                <td>{{
                                    substr($row['nama_akun'], 0, 2) . str_repeat('*', strlen($row['nama_akun']) - 2)
                                }}</td>
                                <td>{{ $maskedName .'@'. $maskedDomain }}</td>
                                
                                <td>{{ $row['nama_canvasser'] }}</td>
                                <td>
                                    <span class="fw-bold text-warning">
                                        {{ $row['poin'] }}
                                    </span>
                                </td>
                                <td style="text-align: center">
                                    <span class="badge-liga 
                                        {{ $liga == 'Rookie' ? 'bg-secondary' : '' }}
                                        {{ $liga == 'Rising Star' ? 'bg-info' : '' }}
                                        {{ $liga == 'Champion' ? 'bg-success' : '' }}
                                    ">
                                        {{ $liga }}
                                    </span>
                                </td>
                            </tr>

                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                Data belum tersedia
                            </td>
                        </tr>
                    @endforelse
                    </tbody>

                </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



{{-- ================= PRIZE ================= --}}
<div class="section-card" id="prizes">
    <h4 class="mb-4">Hadiah yang Bisa Diredeem</h4>

    <div class="row g-4 prize-wrapper">
        @foreach($prizes as $p)
    @php
        $user = auth()->user();

        $notLogin = !auth()->check();
        $notEnoughPoint = !$user || !$point || $point->poin < $p->point;

        $outOfStock = $p->stock <= 0;

        // 🟢 prize ini adalah yang diredeem user
        $isRedeemedItem = $hasRedeemed && $redeemedPrizeId == $p->id;

        // 🔴 disable semua KECUALI item yang diredeem
        $disabled = $notLogin
            || $outOfStock
            || (!$isRedeemedItem && $hasRedeemed)
            || (!$hasRedeemed && $notEnoughPoint) ||$isRedeemedItem;

        // center jika ganjil
        $centerClass = ($loop->last && $loop->count % 2 == 1) ? 'mx-auto' : '';
    @endphp

    <div class="col-md-4 col-lg-3 {{ $centerClass }}">
        <div class="prize-card p-4 text-center
            {{ $hasRedeemed && !$isRedeemedItem ? 'opacity-50' : '' }}
            {{ $isRedeemedItem ? 'border border-success' : '' }}
        ">
            <div>
                <div class="prize-image">
                    <img src="{{ asset('img/'.$p->img) }}" alt="{{ $p->name }}">
                </div>

                <div class="prize-title my-1">
                    {{ $p->name }}
                </div>

                <span class="point-badge">
                    {{ $p->point }} Poin
                </span>

                <div class="prize-meta mt-2">
                    Stok: {{ $p->stock }} Unit
                </div>
            </div>

            <button
                type="button"
                class="btn
                    {{ $isRedeemedItem ? 'btn-success' : 'btn-warning' }}
                    w-100 mt-3 fw-semibold btn-redeem" data-prize-id="{{ $p->id }}"
                {{ $disabled ? 'disabled' : '' }}
            >
                @if ($isRedeemedItem)
                    ✓ Sudah Diredeem
                @elseif ($hasRedeemed)
                    Tidak Tersedia
                @elseif ($outOfStock)
                    Habis
                @elseif ($notEnoughPoint)
                    Redeem
                @else
                    Redeem
                @endif
            </button>
        </div>
    </div>
@endforeach



    </div>

</div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    const cards = document.querySelectorAll(".prize-card");

    const observer = new IntersectionObserver(entries => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                entry.target.style.animationDelay = `${index * 0.15}s`;
                entry.target.classList.add("animate");
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2 });

    cards.forEach(card => observer.observe(card));
    
});
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.progress-animate').forEach(bar => {
        const percent = bar.dataset.percent;
        setTimeout(() => {
            bar.style.width = percent + '%';
        }, 200); // delay dikit biar kelihatan animasinya
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("show");
                    observer.unobserve(entry.target); // animate once
                }
            });
        },
        {
            threshold: 0.2
        }
    );

    document.querySelectorAll(".scroll-animate").forEach(el => {
        observer.observe(el);
    });
});
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-redeem').forEach(button => {
        button.addEventListener('click', function () {
            const prizeId = this.dataset.prizeId;

            Swal.fire({
                title: 'Yakin redeem hadiah ini?',
                text: 'Poin akan langsung dipotong',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Redeem',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#f59e0b',
            }).then((result) => {
                if (result.isConfirmed) {
                    redeemPrize(prizeId);
                }
            });
        });
    });

    function redeemPrize(prizeId) {
        fetch("{{ route('redeem') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                prize_id: prizeId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: data.message,
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: data.message,
                });
            }
        })
        .catch(() => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Terjadi kesalahan sistem',
            });
        });
    }
});


</script>
@endpush
