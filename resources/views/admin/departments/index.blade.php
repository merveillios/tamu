@extends('layouts.staff')

@section('title', 'Departemen')

@section('content')
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb breadcrumb-dark breadcrumb-transparent mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.locations.index') }}">Lokasi</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $location->name }}</li>
        </ol>
    </nav>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.departments.store', $location->id) }}" class="card card-body border-0 shadow mb-3">
        @csrf
        <label for="new_department" class="form-label">Tambah departemen di {{ $location->name }}</label>
        <div class="input-group">
            <input type="text" name="name" id="new_department" class="form-control" maxlength="100" required
                   placeholder="Contoh: Bidang Perdagangan Dalam Negeri">
            <button type="submit" class="btn btn-primary">Tambah</button>
        </div>
    </form>

    <div class="card card-body border-0 shadow table-wrapper table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="border-gray-200">Nama</th>
                    <th class="border-gray-200">Akun PIC</th>
                    <th class="border-gray-200">Petugas aktif</th>
                    <th class="border-gray-200">Status</th>
                    <th class="border-gray-200">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($departments as $department)
                    <tr>
                        <td class="fw-bold">{{ $department->name }}</td>
                        <td class="small">{{ $department->picUsers->first()?->name ?? 'Belum ada akun PIC aktif' }}</td>
                        <td>{{ $department->active_staff_count }}</td>
                        <td>
                            <span class="badge bg-{{ $department->is_active ? 'success' : 'secondary' }}">{{ $department->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse"
                                        data-bs-target="#rename-{{ $department->id }}" aria-expanded="false" aria-controls="rename-{{ $department->id }}">
                                    Ubah nama
                                </button>
                                <form method="POST" action="{{ route('admin.departments.status', $department->id) }}"
                                      @if ($department->is_active) data-confirm="Nonaktifkan departemen {{ $department->name }}? Departemen tidak lagi muncul di pilihan penugasan." @endif>
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $department->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                        {{ $department->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr class="collapse" id="rename-{{ $department->id }}">
                        <td colspan="5">
                            <form method="POST" action="{{ route('admin.departments.update', $department->id) }}" class="input-group">
                                @csrf
                                <label class="visually-hidden" for="rename-input-{{ $department->id }}">Nama baru {{ $department->name }}</label>
                                <input type="text" name="name" id="rename-input-{{ $department->id }}" class="form-control" value="{{ $department->name }}" maxlength="100" required>
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-gray-600 py-4">Belum ada departemen di lokasi ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
