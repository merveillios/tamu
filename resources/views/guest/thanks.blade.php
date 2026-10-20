@extends('layouts.guest')

@section('title', 'Terima Kasih')

@section('hide_header', '1')

@section('content')
    <div class="card border-0 shadow overflow-hidden">
        @include('guest.partials.banner')

        <div class="card-body p-4 text-center bg-white">
            <x-icon name="clipboard-check" class="icon icon-lg text-success mb-3" />

            @if ($thanks)
                <div class="guest-number mb-3">No. {{ $thanks['number'] }}</div>
                <p class="mb-4">
                    Terima kasih, {{ $thanks['name'] }}. Kunjungan Anda di {{ $thanks['location'] }} tercatat dengan
                    No. {{ $thanks['number'] }} pada pukul {{ $thanks['time'] }}. Silakan menunggu petugas.
                </p>
            @else
                <p class="mb-4">Terima kasih, data Anda tercatat. Silakan menunggu petugas.</p>
            @endif

            <a href="{{ route('guest.history', $qr->token) }}" class="btn btn-outline-primary w-100">Lihat riwayat kunjungan</a>
        </div>
    </div>
@endsection
