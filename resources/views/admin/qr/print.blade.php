@extends('layouts.print')

@section('title', 'Cetak QR '.$qr->location->name)

@section('content')
    <div class="no-print d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('admin.qr.index', ['lokasi' => $qr->location_id]) }}" class="btn btn-gray-200 btn-sm">Kembali</a>
        <button type="button" class="btn btn-primary btn-sm" data-print>Cetak halaman ini</button>
    </div>

    <article class="qr-print-sheet text-center">
        <img src="{{ asset('assets/img/logo.png') }}" class="qr-print-logo mb-3" alt="Logo {{ config('sitamu.institution_name') }}">
        <h1 class="h4 fw-bold mb-1">{{ config('sitamu.institution_name') }}</h1>
        <p class="h5 mb-1">{{ $qr->location->name }}</p>
        @if ($qr->department)
            <p class="h6 mb-1">{{ $qr->department->name }}</p>
        @endif
        @if ($qr->label)
            <p class="mb-4 text-gray-700">{{ $qr->label }}</p>
        @endif

        <img src="{{ $imageUri }}" class="qr-print-code my-4" alt="QR Code buku tamu {{ $qr->location->name }}">

        <p class="h3 fw-bold mt-2">Pindai untuk mengisi buku tamu</p>
    </article>
@endsection
