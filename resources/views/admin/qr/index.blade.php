@extends('layouts.staff')

@section('title', 'QR Code')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    @if ($locations->isEmpty())
        <div class="alert alert-info" role="alert">Belum ada lokasi. Buat lokasi dan Bidang/UPT-nya dulu di menu Lokasi.</div>
    @else
        <form method="POST" action="{{ route('admin.qr.store') }}" class="card card-body border-0 shadow mb-3" data-qr-form>
            @csrf
            <h2 class="h6 mb-3">Buat QR baru</h2>
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-6 col-xl-4">
                    <label for="location_id" class="form-label small mb-1">Lokasi</label>
                    <select name="location_id" id="location_id" class="form-select" required data-qr-location>
                        <option value="">Pilih lokasi</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" data-departments="{{ $departmentCounts->get($location->id, 0) }}" data-categories="{{ $categoryCounts->get($location->id, 0) }}"
                                    @selected((int) old('location_id', $selected?->id) === $location->id)>
                                {{ $location->name }}@unless ($location->is_active) (nonaktif)@endunless
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <label for="label" class="form-label small mb-1">Label (opsional)</label>
                    <input type="text" name="label" id="label" class="form-control" maxlength="100" value="{{ old('label') }}" placeholder="Contoh: Pintu Timur">
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <label for="receptionist_id" class="form-label small mb-1">Akun resepsionis</label>
                    <select name="receptionist_id" id="receptionist_id" class="form-select" required>
                        <option value="">Pilih akun resepsionis</option>
                        @foreach ($receptionists as $receptionist)
                            <option value="{{ $receptionist->id }}" @selected((int) old('receptionist_id') === $receptionist->id)>{{ $receptionist->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-xl-2 d-grid">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
            {{-- Peringatan saja: QR tetap boleh dibuat dan dicetak lebih dulu. --}}
            <div class="alert alert-warning small mt-3 mb-0" role="status" data-qr-location-warning hidden>
                <span data-warning="no-department">Lokasi ini belum punya Bidang/UPT aktif. QR tetap bisa dibuat, tetapi belum dapat dipakai tamu sampai Bidang/UPT ditambahkan di menu Lokasi dan kategori keperluan di menu Pertanyaan.</span>
                <span data-warning="no-category">Lokasi ini belum punya kategori keperluan aktif. QR tetap bisa dibuat, tetapi belum dapat dipakai tamu sampai kategori ditambahkan di menu Pertanyaan.</span>
            </div>
            @if ($receptionists->isEmpty())
                <p class="small text-danger mt-2 mb-0">Belum ada akun resepsionis aktif. Buat dulu di menu Pengguna.</p>
            @endif
        </form>

        <form method="GET" action="{{ route('admin.qr.index') }}" class="card card-body border-0 shadow mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-6">
                    <label for="filter_location" class="form-label small mb-1">Tampilkan QR lokasi</label>
                    <select name="lokasi" id="filter_location" class="form-select">
                        <option value="">Semua lokasi</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @selected($selected?->id === $location->id)>{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <button type="submit" class="btn btn-gray-800">Tampilkan</button>
                </div>
            </div>
        </form>
    @endif

    <div class="card card-body border-0 shadow">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="border-gray-200">Lokasi</th>
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
                            <td>{{ $qr->location->name }}</td>
                            <td class="fw-bold">{{ $qr->label ?? 'QR #'.$qr->id }}</td>
                            <td>{{ $qr->receptionist->name }}@unless ($qr->receptionist->is_active) <span class="badge bg-secondary">nonaktif</span>@endunless</td>
                            <td>
                                {{-- QR aktif yang belum memenuhi syarat halaman tamu (TAM-6) tidak ditampilkan sebagai Aktif. --}}
                                @if ($blockers->get($qr->id))
                                    <span class="badge bg-warning text-dark">Belum siap</span>
                                    <div class="small text-gray-600 mt-1">{{ implode(' ', $blockers->get($qr->id)) }}</div>
                                @else
                                    <span class="badge bg-{{ $qr->status->badge() }}">{{ $qr->status->label() }}</span>
                                @endif
                            </td>
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
                                                data-bs-target="#edit-{{ $qr->id }}" aria-expanded="false" aria-controls="edit-{{ $qr->id }}">
                                            Ganti resepsionis
                                        </button>
                                        <form method="POST" action="{{ route('admin.qr.status', $qr->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $qr->status === \App\Enums\QrStatus::Active ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                    @if ($qr->status === \App\Enums\QrStatus::Active) data-deactivate-message="Nonaktifkan QR {{ $qr->label ?? '#'.$qr->id }}? QR ini tidak akan bisa digunakan oleh tamu sampai diaktifkan kembali." @endif>
                                                {{ $qr->status === \App\Enums\QrStatus::Active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.qr.regenerate', $qr->id) }}"
                                              data-confirm="Regenerate QR {{ $qr->label ?? '#'.$qr->id }}? QR lama yang sudah dicetak tidak akan berfungsi lagi."
                                              data-confirm-title="Regenerate QR" data-confirm-button="Ya, Regenerate" data-confirm-variant="warning">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning">Regenerate</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                        @unless ($revoked)
                            <tr class="collapse" id="edit-{{ $qr->id }}">
                                <td colspan="7">
                                    <div class="row g-2">
                                        <div class="col-12 col-lg-6">
                                            <form method="POST" action="{{ route('admin.qr.receptionist', $qr->id) }}" class="input-group">
                                                @csrf
                                                <label class="input-group-text" for="receptionist-select-{{ $qr->id }}">Resepsionis</label>
                                                <select name="receptionist_id" id="receptionist-select-{{ $qr->id }}" class="form-select" required>
                                                    @foreach ($receptionists as $receptionist)
                                                        <option value="{{ $receptionist->id }}" @selected($receptionist->id === $qr->receptionist_id)>{{ $receptionist->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-primary">Simpan</button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-end">
                                        <button type="button" class="btn btn-sm btn-gray-200" data-cancel-collapse="#edit-{{ $qr->id }}">Batal</button>
                                    </div>
                                </td>
                            </tr>
                        @endunless
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-gray-600 py-4">
                                {{ $selected ? 'Belum ada QR untuk lokasi ini.' : 'Belum ada QR.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $qrCodes->links() }}</div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/qr-form.js') }}?v={{ filemtime(public_path('assets/js/qr-form.js')) }}"></script>
@endpush
