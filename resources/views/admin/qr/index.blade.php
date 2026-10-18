@extends('layouts.staff')

@section('title', 'QR Code')

@section('content')
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb breadcrumb-dark breadcrumb-transparent mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.locations.index') }}">Lokasi</a></li>
            <li class="breadcrumb-item active" aria-current="page">QR {{ $location->name }}</li>
        </ol>
    </nav>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.qr.store', $location->id) }}" class="card card-body border-0 shadow mb-3">
        @csrf
        <h2 class="h6 mb-3">Buat QR baru untuk {{ $location->name }}</h2>
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label for="label" class="form-label small mb-1">Label (opsional)</label>
                <input type="text" name="label" id="label" class="form-control" maxlength="100" value="{{ old('label') }}" placeholder="Contoh: Pintu Timur">
            </div>
            <div class="col-12 col-md-5">
                <label for="receptionist_id" class="form-label small mb-1">Akun resepsionis</label>
                <select name="receptionist_id" id="receptionist_id" class="form-select" required>
                    <option value="">Pilih akun resepsionis</option>
                    @foreach ($receptionists as $receptionist)
                        <option value="{{ $receptionist->id }}" @selected((int) old('receptionist_id') === $receptionist->id)>{{ $receptionist->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2 d-grid">
                <button type="submit" class="btn btn-primary">Buat QR</button>
            </div>
        </div>
        @if ($receptionists->isEmpty())
            <p class="small text-danger mt-2 mb-0">Belum ada akun resepsionis aktif. Buat dulu di menu Pengguna.</p>
        @endif
    </form>

    <div class="card card-body border-0 shadow table-wrapper table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="border-gray-200">Label</th>
                    <th class="border-gray-200">Akun resepsionis</th>
                    <th class="border-gray-200">Status</th>
                    <th class="border-gray-200">Dibuat</th>
                    <th class="border-gray-200">Terakhir dipakai</th>
                    <th class="border-gray-200">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($qrCodes as $qr)
                    @php($revoked = $qr->status === \App\Enums\QrStatus::Revoked)
                    <tr @class(['text-gray-500' => $revoked])>
                        <td class="fw-bold">{{ $qr->label ?? 'QR #'.$qr->id }}</td>
                        <td>{{ $qr->receptionist->name }}@unless ($qr->receptionist->is_active) <span class="badge bg-secondary">nonaktif</span>@endunless</td>
                        <td><span class="badge bg-{{ $qr->status->badge() }}">{{ $qr->status->label() }}</span></td>
                        <td class="small">{{ $qr->created_at->format('d/m/Y H:i') }}</td>
                        <td class="small">{{ $qr->last_used_at?->format('d/m/Y H:i') ?? '–' }}</td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                @unless ($revoked)
                                    <a href="{{ route('admin.qr.print', $qr->id) }}" class="btn btn-sm btn-gray-800" target="_blank" rel="noopener">Cetak</a>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.qr.download', ['id' => $qr->id, 'format' => 'png']) }}" class="btn btn-sm btn-outline-gray-800">PNG</a>
                                        <a href="{{ route('admin.qr.download', ['id' => $qr->id, 'format' => 'svg']) }}" class="btn btn-sm btn-outline-gray-800">SVG</a>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse"
                                            data-bs-target="#receptionist-{{ $qr->id }}" aria-expanded="false" aria-controls="receptionist-{{ $qr->id }}">
                                        Ganti resepsionis
                                    </button>
                                    <form method="POST" action="{{ route('admin.qr.status', $qr->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $qr->status === \App\Enums\QrStatus::Active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                            {{ $qr->status === \App\Enums\QrStatus::Active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.qr.regenerate', $qr->id) }}"
                                          data-confirm="QR lama yang sudah dicetak tidak akan berfungsi lagi.">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-warning">Regenerate</button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                    @unless ($revoked)
                        <tr class="collapse" id="receptionist-{{ $qr->id }}">
                            <td colspan="6">
                                <form method="POST" action="{{ route('admin.qr.receptionist', $qr->id) }}" class="input-group">
                                    @csrf
                                    <label class="input-group-text" for="receptionist-select-{{ $qr->id }}">Resepsionis baru</label>
                                    <select name="receptionist_id" id="receptionist-select-{{ $qr->id }}" class="form-select" required>
                                        @foreach ($receptionists as $receptionist)
                                            <option value="{{ $receptionist->id }}" @selected($receptionist->id === $qr->receptionist_id)>{{ $receptionist->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-primary">Simpan</button>
                                </form>
                            </td>
                        </tr>
                    @endunless
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-gray-600 py-4">Belum ada QR untuk lokasi ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
