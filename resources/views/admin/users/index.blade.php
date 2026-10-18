@extends('layouts.staff')

@section('title', 'Pengguna')

@section('content')
    <div class="d-flex justify-content-between flex-wrap align-items-center mb-3 gap-2">
        <p class="mb-0 text-gray-600">Kelola akun Super Admin, Admin, Resepsionis, dan PIC.</p>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">Tambah Akun</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('admin.users.index') }}" class="card card-body border-0 shadow mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label for="q" class="form-label small mb-1">Cari nama atau email</label>
                <input type="search" name="q" id="q" class="form-control" value="{{ $filters['q'] ?? '' }}">
            </div>
            <div class="col-6 col-md-3">
                <label for="role" class="form-label small mb-1">Peran</label>
                <select name="role" id="role" class="form-select">
                    <option value="">Semua peran</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(($filters['role'] ?? '') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="status" class="form-label small mb-1">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">Semua</option>
                    @foreach (['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif', 'terkunci' => 'Terkunci'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2 d-grid">
                <button type="submit" class="btn btn-gray-800">Terapkan</button>
            </div>
        </div>
    </form>

    <div class="card card-body border-0 shadow table-wrapper table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="border-gray-200">Nama</th>
                    <th class="border-gray-200">Email</th>
                    <th class="border-gray-200">Peran</th>
                    <th class="border-gray-200">Terkait</th>
                    <th class="border-gray-200">Status</th>
                    <th class="border-gray-200">Login terakhir</th>
                    <th class="border-gray-200">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td class="fw-bold">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->role->label() }}</td>
                        <td class="small">
                            @switch($user->role)
                                @case(\App\Enums\UserRole::Admin)
                                    {{ $user->locations->pluck('name')->join(', ') ?: 'Belum ada lokasi' }}
                                    @break
                                @case(\App\Enums\UserRole::Pic)
                                    {{ $user->department?->name }} — {{ $user->department?->location?->name }}
                                    @break
                                @case(\App\Enums\UserRole::Receptionist)
                                    {{ $user->qr_codes_count }} QR
                                    @break
                                @default
                                    Semua lokasi
                            @endswitch
                        </td>
                        <td>
                            <span class="badge bg-{{ $user->is_active ? 'success' : 'secondary' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            @if ($user->locked_until?->isFuture())
                                <span class="badge bg-danger">Terkunci</span>
                            @endif
                        </td>
                        <td class="small">{{ $user->last_login_at?->format('d/m/Y H:i') ?? '–' }}</td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-outline-primary">Ubah</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-gray-600 py-4">Tidak ada akun yang cocok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">{{ $users->links() }}</div>
    </div>
@endsection
