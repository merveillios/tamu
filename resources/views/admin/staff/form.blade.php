@extends('layouts.staff')

@section('title', $staffMember->exists ? 'Ubah Petugas' : 'Tambah Petugas')

@section('content')
    <div class="row">
        <div class="col-12 col-xl-6">
            <div class="card card-body border-0 shadow mb-4">
                <h2 class="h5 mb-1">{{ $staffMember->exists ? 'Ubah petugas '.$staffMember->name : 'Petugas baru' }}</h2>
                <p class="small text-gray-600 mb-4">Departemen: {{ $department->name }}</p>

                <form method="POST" action="{{ $staffMember->exists ? route('admin.staff.update', $staffMember->id) : route('admin.staff.store', ['department_id' => $department->id]) }}">
                    @csrf

                    <div class="mb-4">
                        <label for="name" class="form-label">Nama petugas</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $staffMember->name) }}" required maxlength="100"
                               class="form-control @error('name') is-invalid @enderror" aria-describedby="name_error">
                        @error('name')
                            <div class="invalid-feedback" id="name_error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="{{ route('admin.staff.index', ['department_id' => $department->id]) }}" class="btn btn-gray-200">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
