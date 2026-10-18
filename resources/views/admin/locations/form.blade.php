@extends('layouts.staff')

@section('title', $location->exists ? 'Ubah Lokasi' : 'Tambah Lokasi')

@section('content')
    <div class="row">
        <div class="col-12 col-xl-6">
            <div class="card card-body border-0 shadow mb-4">
                <h2 class="h5 mb-4">{{ $location->exists ? 'Ubah lokasi '.$location->name : 'Lokasi baru' }}</h2>

                <form method="POST" action="{{ $location->exists ? route('admin.locations.update', $location->id) : route('admin.locations.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Nama lokasi</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $location->name) }}" required maxlength="100"
                               class="form-control @error('name') is-invalid @enderror" aria-describedby="name_error">
                        @error('name')
                            <div class="invalid-feedback" id="name_error">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Contoh: Kantor Dinas Jl. Ahmad Yani.</div>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label">Deskripsi (opsional)</label>
                        <textarea name="description" id="description" rows="2" maxlength="255"
                                  class="form-control @error('description') is-invalid @enderror" aria-describedby="description_error">{{ old('description', $location->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback" id="description_error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="{{ route('admin.locations.index') }}" class="btn btn-gray-200">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
