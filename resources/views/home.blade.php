@extends('layouts.guest')

@section('title', 'Beranda')

@section('content')
    <div class="card border-0 shadow">
        <div class="card-body p-4 text-center">
            <x-icon name="qrcode" class="icon icon-lg text-primary mb-3" />
            <h1 class="h4 mb-2">Selamat datang di {{ config('app.name') }}</h1>
            <p class="text-gray-600 mb-4">
                Untuk mengisi buku tamu, pindai QR Code yang tersedia di pintu masuk lokasi kunjungan.
            </p>
            <a href="{{ route('login') }}" class="btn btn-outline-primary w-100">Masuk sebagai staf</a>
        </div>
    </div>
@endsection
