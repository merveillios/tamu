@extends('layouts.staff')

@section('title', 'Kelompok Rentan')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="card card-body border-0 shadow mb-3">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-8">
                <p class="small text-gray-600 mb-0">
                    Pilihan kelompok rentan di formulir tamu, berlaku untuk semua lokasi. Tamu dari kelompok yang punya
                    prioritas antrean tampil paling atas di Tamu Aktif dan Penugasan; angka kecil didahulukan.
                </p>
            </div>
            <div class="col-12 col-md-4 d-grid">
                <a href="{{ route('admin.vulnerable-groups.create') }}" class="btn btn-primary">Tambah Kelompok Rentan</a>
            </div>
        </div>
    </div>

    <div class="card card-body border-0 shadow">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="border-gray-200">Urutan</th>
                        <th class="border-gray-200">Nama</th>
                        <th class="border-gray-200">Prioritas antrean</th>
                        <th class="border-gray-200">Dipakai</th>
                        <th class="border-gray-200">Status</th>
                        <th class="border-gray-200">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($groups as $group)
                        @php($used = (int) ($usage[$group->code] ?? 0))
                        <tr>
                            <td data-label="Urutan">{{ $group->sort_order }}</td>
                            <td data-label="Nama" class="fw-bold">{{ $group->name }}</td>
                            <td data-label="Prioritas antrean">
                                @if ($group->priority !== null)
                                    <span class="badge bg-warning text-dark">Prioritas {{ $group->priority }}</span>
                                @else
                                    <span class="text-gray-600">Tidak didahulukan</span>
                                @endif
                            </td>
                            <td data-label="Dipakai">{{ number_format($used, 0, ',', '.') }} kunjungan</td>
                            <td data-label="Status">
                                <span class="badge bg-{{ $group->is_active ? 'success' : 'secondary' }}">{{ $group->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td data-label="Aksi">
                                <div class="d-flex flex-wrap gap-1">
                                    <a href="{{ route('admin.vulnerable-groups.edit', $group->id) }}" class="btn btn-sm btn-outline-primary">Ubah</a>
                                    <form method="POST" action="{{ route('admin.vulnerable-groups.status', $group->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $group->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                @if ($group->is_active) data-deactivate-message="Nonaktifkan kelompok rentan {{ $group->name }}? Pilihan ini tidak akan muncul di formulir tamu baru sampai diaktifkan kembali. Kunjungan lama tetap menampilkannya." @endif>
                                            {{ $group->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                    @if ($used === 0)
                                        <form method="POST" action="{{ route('admin.vulnerable-groups.destroy', $group->id) }}"
                                              data-confirm="Hapus kelompok rentan {{ $group->name }}? Tindakan ini tidak dapat dibatalkan."
                                              data-confirm-title="Hapus Kelompok Rentan" data-confirm-button="Ya, Hapus">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-gray-600 py-4">Belum ada kelompok rentan. Formulir tamu butuh minimal satu kelompok aktif.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="small text-gray-600 mt-3 mb-0">Kelompok yang sudah dipakai kunjungan tidak bisa dihapus, hanya dinonaktifkan, agar History dan ekspor tetap menampilkan namanya.</p>
    </div>
@endsection
