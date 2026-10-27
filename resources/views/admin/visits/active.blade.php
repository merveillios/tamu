@extends('layouts.staff')

@section('title', 'Tamu Aktif')

@section('content')
    @if ($receptionistWithoutQr)
        <div class="alert alert-warning" role="alert">Akun ini belum dihubungkan ke QR mana pun.</div>
    @endif

    <form method="GET" action="{{ route('admin.visits.active') }}" class="card card-body border-0 shadow mb-3" data-active-filters>
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label for="q" class="form-label small mb-1">Cari nama, nomor telepon, atau instansi</label>
                <input type="search" name="q" id="q" class="form-control" value="{{ $filters['q'] }}">
            </div>
            <div class="col-6 col-md-3">
                <label for="status" class="form-label small mb-1">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">Semua status</option>
                    @foreach (['menunggu' => 'Menunggu', 'menunggu_pic' => 'Menunggu PIC', 'direview' => 'Sedang direview'] as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if ($locations->count() > 1)
                <div class="col-6 col-md-2">
                    <label for="location_id" class="form-label small mb-1">Lokasi</label>
                    <select name="location_id" id="location_id" class="form-select">
                        <option value="">Semua lokasi</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @selected($filters['location_id'] === $location->id)>{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-12 col-md-2 d-grid">
                <button type="submit" class="btn btn-gray-800">Terapkan</button>
            </div>
        </div>
    </form>

    <div class="d-flex flex-wrap gap-2 mb-3 small" aria-live="polite">
        <span class="badge bg-warning text-dark">Menunggu: <span data-count="menunggu">0</span></span>
        <span class="badge bg-info">Menunggu PIC: <span data-count="menunggu_pic">0</span></span>
        <span class="badge bg-primary">Sedang direview: <span data-count="direview">0</span></span>
        <span class="text-gray-500 ms-auto">Diperbarui <span data-server-time>–</span></span>
    </div>

    <div class="card card-body border-0 shadow">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-cards"
                   data-active-visits
                   data-url="{{ route('admin.visits.active.data') }}"
                   data-poll="{{ config('sitamu.poll_interval_seconds') }}"
                   data-show-location="{{ $isReceptionist ? '0' : '1' }}"
                   data-departments-url="{{ url('/admin/kunjungan/__ID__/departemen') }}"
                   data-assign-url="{{ url('/admin/kunjungan/__ID__/tugaskan') }}"
                   data-cancel-url="{{ url('/admin/kunjungan/__ID__/batal') }}">
                <thead>
                    <tr>
                        <th class="border-gray-200">No.</th>
                        <th class="border-gray-200">Nama</th>
                        <th class="border-gray-200">Instansi</th>
                        <th class="border-gray-200">Kelompok rentan</th>
                        <th class="border-gray-200">Keperluan</th>
                        <th class="border-gray-200">Departemen</th>
                        <th class="border-gray-200">Nama PIC</th>
                        @unless ($isReceptionist)
                            <th class="border-gray-200">Lokasi / QR</th>
                        @endunless
                        <th class="border-gray-200">Jam datang</th>
                        <th class="border-gray-200">Status</th>
                        <th class="border-gray-200">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="11" class="text-center text-gray-600 py-4">Memuat data…</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <nav class="d-flex justify-content-between align-items-center mt-3 small" data-pagination hidden>
            <button type="button" class="btn btn-sm btn-outline-gray-800" data-page="prev">Sebelumnya</button>
            <span data-page-info></span>
            <button type="button" class="btn btn-sm btn-outline-gray-800" data-page="next">Berikutnya</button>
        </nav>
    </div>

    @if ($isReceptionist)
        {{-- RSP-10: kunjungan dari QR ini yang selesai atau dibatalkan hari ini; tertutup secara default. --}}
        <section class="card card-body border-0 shadow mt-4" data-closed-today>
            <div class="d-flex justify-content-between align-items-center gap-2">
                <h2 class="h6 mb-0">Ditutup hari ini <span class="badge bg-gray-200 text-gray-800" data-closed-count>0</span></h2>
                <button type="button" class="btn btn-sm btn-outline-gray-800" data-bs-toggle="collapse" data-bs-target="#closedToday"
                        aria-expanded="false" aria-controls="closedToday" data-closed-toggle>Tampilkan</button>
            </div>
            <div class="collapse" id="closedToday">
                <div class="table-responsive mt-3">
                    <table class="table table-hover align-middle mb-0 table-cards">
                        <thead>
                            <tr>
                                <th class="border-gray-200">No.</th>
                                <th class="border-gray-200">Nama</th>
                                <th class="border-gray-200">Instansi</th>
                                <th class="border-gray-200">Keperluan</th>
                                <th class="border-gray-200">Departemen / ditemui</th>
                                <th class="border-gray-200">Status</th>
                                <th class="border-gray-200">Jam ditutup</th>
                                <th class="border-gray-200">Alasan batal</th>
                                <th class="border-gray-200">Aksi</th>
                            </tr>
                        </thead>
                        <tbody data-closed-rows>
                            <tr>
                                <td colspan="9" class="text-center text-gray-600 py-4">Memuat data…</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif

    @if ($isReceptionist)
        <div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignModalTitle" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" data-assign-form>
                    <div class="modal-header">
                        <h2 class="modal-title h6" id="assignModalTitle">Tugaskan kunjungan</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-gray-600" data-assign-guest></p>
                        <div class="mb-3">
                            <label for="assign_department" class="form-label">Departemen tujuan</label>
                            <select id="assign_department" name="department_id" class="form-select" required></select>
                            <div class="form-text" data-assign-suggestion></div>
                        </div>
                        <div class="mb-2">
                            <label for="assign_staff" class="form-label">Orang yang ditemui</label>
                            <select id="assign_staff" name="staff_member_id" class="form-select" required></select>
                        </div>
                        <div class="alert alert-danger small d-none mb-0" role="alert" data-modal-error></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-gray-200" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Tugaskan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @include('admin.visits.partials.cancel-modal', ['cancelReasons' => $cancelReasons])
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/searchable-select.js') }}?v={{ filemtime(public_path('assets/js/searchable-select.js')) }}"></script>
    <script src="{{ asset('assets/js/active-visits.js') }}?v={{ filemtime(public_path('assets/js/active-visits.js')) }}"></script>
@endpush
