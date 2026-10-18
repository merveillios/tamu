@extends('layouts.staff')

@section('title', 'Lokasi')

@section('content')
    <div class="d-flex justify-content-between flex-wrap align-items-center mb-3 gap-2">
        <p class="mb-0 text-gray-600">Kelola lokasi, departemen, dan QR Code setiap titik masuk.</p>
        <a href="{{ route('admin.locations.create') }}" class="btn btn-primary btn-sm">Tambah Lokasi</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="card card-body border-0 shadow table-wrapper table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="border-gray-200">Nama</th>
                    <th class="border-gray-200">Departemen</th>
                    <th class="border-gray-200">QR aktif</th>
                    <th class="border-gray-200">Status</th>
                    <th class="border-gray-200">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($locations as $location)
                    @php
                        $deactivateMessage = $location->active_visits_count > 0
                            ? "Lokasi {$location->name} masih memiliki {$location->active_visits_count} kunjungan aktif. Kunjungan itu tetap dapat diselesaikan, tetapi semua QR lokasi ini berhenti berfungsi. Lanjutkan?"
                            : "Nonaktifkan lokasi {$location->name}? Semua QR lokasi ini berhenti berfungsi.";
                        $canDelete = $location->departments_count === 0 && $location->qr_codes_count === 0 && $location->guest_visits_count === 0;
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $location->name }}</div>
                            @if ($location->description)
                                <div class="small text-gray-600">{{ $location->description }}</div>
                            @endif
                        </td>
                        <td>{{ $location->departments_count }}</td>
                        <td>{{ $location->active_qr_codes_count }}</td>
                        <td>
                            <span class="badge bg-{{ $location->is_active ? 'success' : 'secondary' }}">{{ $location->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <a href="{{ route('admin.departments.index', $location->id) }}" class="btn btn-sm btn-gray-800">Departemen</a>
                                <a href="{{ route('admin.qr.index', $location->id) }}" class="btn btn-sm btn-gray-800">QR</a>
                                <a href="{{ route('admin.locations.edit', $location->id) }}" class="btn btn-sm btn-outline-primary">Ubah</a>
                                <form method="POST" action="{{ route('admin.locations.status', $location->id) }}"
                                      @if ($location->is_active) data-confirm="{{ $deactivateMessage }}" @endif>
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $location->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                        {{ $location->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                                @if ($canDelete)
                                    <form method="POST" action="{{ route('admin.locations.destroy', $location->id) }}" data-confirm="Hapus lokasi {{ $location->name }}?">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-gray-600 py-4">Belum ada lokasi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">{{ $locations->links() }}</div>
    </div>
@endsection
