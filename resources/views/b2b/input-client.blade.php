@extends('layouts.app')

@section('title', 'Input Klien B2B')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-info text-white fw-semibold">Form Input Data Pelanggan AM Level UP</div>
        <div class="card-body bg-light">
            <form method="POST" action="{{ route('b2b.clients.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Perusahaan / Instansi <span class="text-danger">*</span></label>
                        <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror" value="{{ old('company_name') }}" placeholder="Masukkan nama perusahaan atau instansi" required>
                        @error('company_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">No HP Pelanggan <span class="text-danger">*</span></label>
                        <input type="text" name="mobile_phone" class="form-control @error('mobile_phone') is-invalid @enderror" value="{{ old('mobile_phone') }}" placeholder="62xxxxxxxxxxx" required>
                        @error('mobile_phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email Pelanggan <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="Masukkan email pelanggan" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Pelanggan</label>
                        <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama') }}" placeholder="Masukkan nama pelanggan">
                        @error('nama')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Sector</label>
                        <select name="sector" class="form-select @error('sector') is-invalid @enderror">
                            <option value="">-- Pilih --</option>
                            <option value="Retail" {{ old('sector') === 'Retail' ? 'selected' : '' }}>Retail</option>
                            <option value="FMCG" {{ old('sector') === 'FMCG' ? 'selected' : '' }}>FMCG</option>
                            <option value="Finance" {{ old('sector') === 'Finance' ? 'selected' : '' }}>Finance</option>
                            <option value="Automotive" {{ old('sector') === 'Automotive' ? 'selected' : '' }}>Automotive</option>
                            <option value="Lainnya" {{ old('sector') === 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                        </select>
                        @error('sector')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Akun MyAds <span class="text-danger">*</span></label>
                        <input type="text" name="myads_account" class="form-control @error('myads_account') is-invalid @enderror" value="{{ old('myads_account') }}" placeholder="Masukkan akun MyAds" required>
                        <small class="text-danger">*) Diisi jika sudah register akun MyAds</small>
                        @error('myads_account')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Remarks</label>
                        <textarea name="remarks" rows="3" class="form-control @error('remarks') is-invalid @enderror" placeholder="Tambahkan catatan jika perlu">{{ old('remarks') }}</textarea>
                        @error('remarks')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="alert alert-info mt-3 mb-0">
                    Pastikan data yang diinput sudah benar sebelum menyimpan.
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Simpan Data</button>
                    <a href="{{ route('b2b.performance') }}" class="btn btn-secondary">Lihat Report</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="mb-3">Daftar Klien Saya</h5>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Perusahaan</th>
                            <th>Nama Pelanggan</th>
                            <th>Email Pelanggan</th>
                            <th>No. HP</th>
                            <th>Sector</th>
                            <th>Akun MyAds</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clients as $index => $client)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $client->company_name }}</td>
                                <td>{{ $client->customer_name ?: '-' }}</td>
                                <td>{{ $client->customer_email }}</td>
                                <td>{{ $client->customer_phone }}</td>
                                <td>{{ $client->sector ?: '-' }}</td>
                                <td>{{ $client->myads_account }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada klien.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
