@extends('layouts.guest')

@section('title', 'Riwayat Kunjungan')

@section('hide_header', '1')

@section('content')
    @include('guest.partials.email-block')

    <a href="{{ route('guest.form', $qr->token) }}" class="btn btn-primary w-100 mb-4">Isi formulir lagi</a>

    <h1 class="h5 mb-3">Riwayat kunjungan Anda</h1>

    @forelse ($visits as $visit)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <div class="fw-bold">{{ $visit->check_in_at->format('d/m/Y H:i') }}</div>
                        <div class="small text-gray-600">{{ $visit->location->name }}</div>
                    </div>
                    <span class="badge bg-{{ $visit->display_status['badge'] }}">{{ $visit->display_status['label'] }}</span>
                </div>

                <dl class="row small mb-0">
                    <dt class="col-5 text-gray-600 fw-normal">Keperluan</dt>
                    <dd class="col-7 mb-1">{{ $visit->purpose_category_name }}</dd>
                    <dt class="col-5 text-gray-600 fw-normal">Rincian</dt>
                    <dd class="col-7 mb-1 text-break">{{ $visit->purpose }}</dd>
                    <dt class="col-5 text-gray-600 fw-normal">Departemen tujuan</dt>
                    <dd class="col-7 mb-1">{{ $visit->assignedDepartment?->name ?? '–' }}</dd>
                    <dt class="col-5 text-gray-600 fw-normal">Orang yang ditemui</dt>
                    <dd class="col-7 mb-1">{{ $visit->servedBy?->name ?? '–' }}</dd>
                </dl>

                @if ($visit->status === \App\Enums\VisitStatus::Completed && $visit->result)
                    <div class="bg-soft rounded p-3 mt-2">
                        <div class="small fw-bold mb-1">Hasil</div>
                        <div class="small text-break guest-result">{{ $visit->result }}</div>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <p class="text-gray-600">Belum ada kunjungan.</p>
    @endforelse

    @if ($visits->hasPages())
        <nav class="d-flex justify-content-between" aria-label="Halaman riwayat">
            @if ($visits->onFirstPage())
                <span></span>
            @else
                <a href="{{ $visits->previousPageUrl() }}" class="btn btn-sm btn-outline-gray-800">Sebelumnya</a>
            @endif
            @if ($visits->hasMorePages())
                <a href="{{ $visits->nextPageUrl() }}" class="btn btn-sm btn-outline-gray-800">Berikutnya</a>
            @endif
        </nav>
    @endif
@endsection
