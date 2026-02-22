@extends('layouts.app')

@section('title', 'AM Level UP')

@section('content')
<div class="container my-5 am-shell">
    <section class="section-card text-center">
        <h1 class="am-title mb-2">AM Level UP</h1>
        <p class="am-subtitle mb-4">Halaman input data AM Level UP.</p>
        <a href="{{ route('home') }}" class="btn btn-warning">Kembali ke Dashboard</a>
    </section>
</div>
@endsection
