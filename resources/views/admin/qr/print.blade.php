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

        {{-- Departemen QR hanya syarat teknis saat membuat QR, jadi tidak dicetak untuk tamu. --}}
        @if ($qr->label)
            <p class="h5 fw-bold text-gray-800 mb-2">{{ $qr->label }}</p>
        @endif

        <img src="{{ $imageUri }}" class="qr-print-code my-4" alt="QR Code buku tamu {{ $qr->location->name }}">

        <p class="h3 fw-bold mt-2">Pindai untuk mengisi buku tamu</p>
    </article>
@endsection
