@extends('layouts.staff')

@section('title', 'Detail Kunjungan')

@php
    $statusLabel = fn (?\App\Enums\VisitStatus $status) => $status?->label() ?? 'Baru';
    $datetime = fn ($value) => $value?->format('d/m/Y H:i') ?? '–';
@endphp

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h2 class="h4 mb-0">No. {{ $visit->daily_number }} · {{ $visit->name }}</h2>
            <span class="small text-gray-600">{{ $visit->visit_date->format('d/m/Y') }} · {{ $visit->location->name }}</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-{{ $visit->display_status['badge'] }} fs-6">{{ $visit->display_status['label'] }}</span>
            @if ($canReopen)
                <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#reopenModal">Buka Kembali</button>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-xl-6">
            <div class="card card-body border-0 shadow mb-4">
                <h3 class="h6 mb-3">Data tamu</h3>
                <dl class="row small mb-0">
                    <dt class="col-5 text-gray-600 fw-normal">Nomor urut</dt><dd class="col-7">No. {{ $visit->daily_number }}</dd>
                    <dt class="col-5 text-gray-600 fw-normal">Email</dt><dd class="col-7 text-break">{{ $visit->email }}</dd>
                    <dt class="col-5 text-gray-600 fw-normal">Kategori keperluan</dt><dd class="col-7">{{ $visit->purpose_category_name }}</dd>
                    <dt class="col-5 text-gray-600 fw-normal">Rincian keperluan</dt><dd class="col-7 text-break">{{ $visit->purpose }}</dd>
                    <dt class="col-5 text-gray-600 fw-normal">Orang yang ditemui</dt><dd class="col-7">{{ $visit->servedBy?->name ?? '–' }}</dd>
                    <dt class="col-5 text-gray-600 fw-normal">Kelompok rentan</dt><dd class="col-7">{{ $visit->vulnerable_group->label() }}</dd>
                    <dt class="col-5 text-gray-600 fw-normal">Nama lengkap</dt><dd class="col-7">{{ $visit->name }}</dd>
                    <dt class="col-5 text-gray-600 fw-normal">Instansi/perusahaan</dt><dd class="col-7">{{ $visit->organization }}</dd>
                    <dt class="col-5 text-gray-600 fw-normal">Telepon/WhatsApp</dt><dd class="col-7">{{ \App\Rules\IndonesianPhone::display($visit->phone) }}</dd>
                    <dt class="col-5 text-gray-600 fw-normal">Alamat</dt><dd class="col-7 text-break">{{ $visit->address }}</dd>
                    <dt class="col-5 text-gray-600 fw-normal">Jam datang</dt><dd class="col-7">{{ $datetime($visit->check_in_at) }}</dd>
                    @if ($canSeeSignature)
                        <dt class="col-5 text-gray-600 fw-normal">Tanda tangan</dt>
                        <dd class="col-7">
                            @if ($hasSignature)
                                <img src="{{ route('admin.visits.signature', $visit->id) }}" alt="Tanda tangan {{ $visit->name }}"
                                     loading="lazy" class="img-fluid border rounded bg-white">
                            @else
                                –
                            @endif
                        </dd>
                    @endif
                </dl>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card card-body border-0 shadow mb-4">
                <h3 class="h6 mb-3">Proses kunjungan</h3>
                <dl class="row small mb-0">
                    <dt class="col-5 text-gray-600 fw-normal">Lokasi</dt><dd class="col-7">{{ $visit->location->name }}</dd>
                    <dt class="col-5 text-gray-600 fw-normal">QR</dt><dd class="col-7">{{ $visit->qrCode->label ?? 'QR #'.$visit->qr_code_id }}</dd>
                    <dt class="col-5 text-gray-600 fw-normal">Saran departemen</dt><dd class="col-7">{{ $visit->suggestedDepartment?->name ?? '–' }}</dd>
                    <dt class="col-5 text-gray-600 fw-normal">Departemen tujuan</dt><dd class="col-7">{{ $visit->assignedDepartment?->name ?? '–' }}</dd>
                    <dt class="col-5 text-gray-600 fw-normal">Diterima PIC</dt>
                    <dd class="col-7">{{ $visit->processedBy ? $visit->processedBy->name.' · '.$datetime($visit->processed_at) : '–' }}</dd>
                    <dt class="col-5 text-gray-600 fw-normal">Selesai</dt><dd class="col-7">{{ $datetime($visit->completed_at) }}</dd>
                    <dt class="col-5 text-gray-600 fw-normal">Dibatalkan</dt>
                    <dd class="col-7">{{ $visit->cancelled_at ? $datetime($visit->cancelled_at).' · '.$visit->cancel_reason : '–' }}</dd>
                    <dt class="col-5 text-gray-600 fw-normal">Ditutup oleh</dt><dd class="col-7">{{ $visit->closedBy?->name ?? '–' }}</dd>
                </dl>
                <div class="mt-3">
                    <div class="small fw-bold mb-1">Hasil</div>
                    <div class="small bg-soft rounded p-3 guest-result">{{ $visit->result ?? 'Belum ada hasil.' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-body border-0 shadow table-wrapper table-responsive mb-4">
        <h3 class="h6 mb-3">Riwayat penugasan</h3>
        <table class="table align-middle mb-0 table-cards small">
            <thead>
                <tr>
                    <th class="border-gray-200">Waktu</th>
                    <th class="border-gray-200">Departemen</th>
                    <th class="border-gray-200">Orang yang ditemui</th>
                    <th class="border-gray-200">Oleh akun</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($visit->assignments as $assignment)
                    <tr>
                        <td data-label="Waktu">{{ $datetime($assignment->assigned_at) }}</td>
                        <td data-label="Departemen">{{ $assignment->department->name }}</td>
                        <td data-label="Ditemui">{{ $assignment->staffMember->name }}</td>
                        <td data-label="Oleh">{{ $assignment->assignedBy?->name ?? '–' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-gray-600">Belum pernah ditugaskan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card card-body border-0 shadow table-wrapper table-responsive mb-4">
        <h3 class="h6 mb-3">Timeline status</h3>
        <table class="table align-middle mb-0 table-cards small">
            <thead>
                <tr>
                    <th class="border-gray-200">Waktu</th>
                    <th class="border-gray-200">Perubahan</th>
                    <th class="border-gray-200">Oleh akun</th>
                    <th class="border-gray-200">Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($visit->statusLogs as $log)
                    <tr>
                        <td data-label="Waktu">{{ $datetime($log->created_at) }}</td>
                        <td data-label="Perubahan">{{ $statusLabel($log->from_status) }} → {{ $statusLabel($log->to_status) }}</td>
                        <td data-label="Oleh">{{ $log->changedBy?->name ?? 'Tamu' }}{{ $log->staffMember ? ' · '.$log->staffMember->name : '' }}</td>
                        <td data-label="Catatan" class="text-break">{{ $log->note ?? '–' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($canReopen)
        <div class="modal fade" id="reopenModal" tabindex="-1" aria-labelledby="reopenModalTitle" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" data-reopen-form data-url="{{ route('admin.visits.reopen', $visit->id) }}">
                    <div class="modal-header">
                        <h2 class="modal-title h6" id="reopenModalTitle">Buka kembali kunjungan</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-gray-600">Kunjungan kembali ke status sebelum ditutup.</p>
                        <label for="reopen_note" class="form-label">Alasan</label>
                        <textarea id="reopen_note" name="note" class="form-control" rows="3" maxlength="255" required></textarea>
                        <div class="alert alert-danger small d-none mt-2 mb-0" role="alert" data-modal-error></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-gray-200" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">Buka Kembali</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    @if ($canReopen)
        <script src="{{ asset('assets/js/visit-detail.js') }}"></script>
    @endif
@endpush
