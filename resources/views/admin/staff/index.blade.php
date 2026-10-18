@extends('layouts.staff')

@section('title', 'Petugas')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    @if ($department === null)
        <div class="alert alert-info" role="alert">Belum ada departemen. Super Admin perlu membuat departemen di menu Lokasi.</div>
    @else
        <div class="card card-body border-0 shadow mb-3">
            <div class="row g-2 align-items-end">
                <form method="GET" action="{{ route('admin.staff.index') }}" class="col-12 col-md-8 d-flex gap-2 align-items-end">
                    <div class="flex-grow-1">
                        <label for="department_id" class="form-label small mb-1">Departemen</label>
                        <select name="department_id" id="department_id" class="form-select">
                            @foreach ($departments as $locationName => $group)
                                <optgroup label="{{ $locationName }}">
                                    @foreach ($group as $option)
                                        <option value="{{ $option->id }}" @selected($option->id === $department->id)>
                                            {{ $option->name }}@unless ($option->is_active) (nonaktif)@endunless
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-gray-800">Tampilkan</button>
                </form>
                <div class="col-12 col-md-4 d-grid">
                    <a href="{{ route('admin.staff.create', ['department_id' => $department->id]) }}" class="btn btn-primary">Tambah Petugas</a>
                </div>
            </div>
        </div>

        <div class="card card-body border-0 shadow table-wrapper table-responsive">
            <p class="small text-gray-600">Petugas adalah orang yang dapat ditemui tamu dan dipilih resepsionis saat menugaskan kunjungan.</p>
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="border-gray-200">Nama</th>
                        <th class="border-gray-200">Status</th>
                        <th class="border-gray-200">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staffMembers as $member)
                        <tr>
                            <td class="fw-bold">{{ $member->name }}</td>
                            <td>
                                <span class="badge bg-{{ $member->is_active ? 'success' : 'secondary' }}">{{ $member->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <a href="{{ route('admin.staff.edit', $member->id) }}" class="btn btn-sm btn-outline-primary">Ubah</a>
                                    <form method="POST" action="{{ route('admin.staff.status', $member->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $member->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                            {{ $member->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-gray-600 py-4">Belum ada petugas di departemen ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
@endsection
