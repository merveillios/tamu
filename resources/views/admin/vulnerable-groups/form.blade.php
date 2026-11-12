@extends('layouts.staff')

@section('title', $group->exists ? 'Ubah Kelompok Rentan' : 'Tambah Kelompok Rentan')

@section('content')
    <div class="row">
        <div class="col-12 col-xl-6">
            <div class="card card-body border-0 shadow mb-4">
                <h2 class="h5 mb-4">{{ $group->exists ? 'Ubah kelompok rentan '.$group->name : 'Kelompok rentan baru' }}</h2>

                <form method="POST" action="{{ $group->exists ? route('admin.vulnerable-groups.update', $group->id) : route('admin.vulnerable-groups.store') }}">
                    @csrf

                    <div class="mb-4">
                        <label for="name" class="form-label">Nama</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $group->name) }}" required maxlength="100"
                               class="form-control @error('name') is-invalid @enderror" aria-describedby="name_help name_error">
                        <div class="form-text" id="name_help">Tampil apa adanya di formulir tamu, Tamu Aktif, History, dan ekspor.</div>
                        @error('name')
                            <div class="invalid-feedback" id="name_error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="priority" class="form-label">Prioritas antrean <span class="text-gray-600 fw-normal">(opsional)</span></label>
                        <input type="number" name="priority" id="priority" value="{{ old('priority', $group->priority) }}" min="1" max="99" inputmode="numeric"
                               class="form-control @error('priority') is-invalid @enderror" aria-describedby="priority_help priority_error">
                        <div class="form-text" id="priority_help">Isi 1–99 agar tamu kelompok ini didahulukan di antrean; angka kecil lebih dulu. Kosongkan bila tidak didahulukan.</div>
                        @error('priority')
                            <div class="invalid-feedback" id="priority_error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="sort_order" class="form-label">Urutan tampil</label>
                        <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $group->sort_order ?? 0) }}" min="0" max="999" inputmode="numeric"
                               class="form-control @error('sort_order') is-invalid @enderror" aria-describedby="sort_order_help sort_order_error">
                        <div class="form-text" id="sort_order_help">Urutan pilihan di formulir tamu; angka kecil di atas.</div>
                        @error('sort_order')
                            <div class="invalid-feedback" id="sort_order_error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="{{ route('admin.vulnerable-groups.index') }}" class="btn btn-gray-200">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
