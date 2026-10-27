@extends('layouts.print')

@section('title', 'Cetak QR '.$qr->location->name)

@section('content')
    <div class="no-print d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('admin.qr.index', ['lokasi' => $qr->location_id]) }}" class="btn btn-gray-200 btn-sm">Kembali</a>
        <button type="button" class="btn btn-primary btn-sm" data-print>Cetak halaman ini</button>
    </div>

    <article class="qr-print-sheet text-center">
        <div class="qr-print-banner rounded-3 overflow-hidden mb-4">
            @include('guest.partials.banner', ['location' => $qr->location])
        </div>

        @if ($qr->department || $qr->label)
            <div class="qr-print-meta mb-2">
                @if ($qr->department)
                    <p class="h5 fw-bold mb-1">{{ $qr->department->name }}</p>
                @endif
                @if ($qr->label)
                    <p class="text-gray-700 mb-0">{{ $qr->label }}</p>
                @endif
            </div>
        @endif

        <img src="{{ $imageUri }}" class="qr-print-code my-4" alt="QR Code buku tamu {{ $qr->location->name }}">

        <p class="h3 fw-bold mt-2">Pindai untuk mengisi buku tamu</p>
    </article>
@endsection
