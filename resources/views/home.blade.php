@extends('layouts.guest')

@section('title', 'Beranda')

@section('full_page')
    <main class="container-fluid p-0">
        <div class="row g-0 hero-split-row">
            {{-- Panel kiri: sambutan dengan dekorasi biru Disperindag --}}
            <div class="col-md-6 hero-illustration-panel">
                @include('partials.hero-deco')

                <div class="position-relative text-center text-md-start" style="z-index: 1; max-width: 420px;">
                    <img src="{{ asset('assets/img/logo-disperindag.png') }}"
                         alt="Logo Dinas Perindustrian dan Perdagangan Provinsi Jawa Timur"
                         class="mb-3 mx-auto mx-md-0 d-block" style="height: 64px; width: auto; filter: drop-shadow(0 3px 6px rgba(0,0,0,0.25));">

                    <div class="text-white fw-bold mb-3" style="line-height: 1.35;">
                        Dinas Perindustrian dan Perdagangan<br>Provinsi Jawa Timur
                    </div>

                    <h1 class="hero-welcome-title mb-3">Selamat Datang!</h1>
                    <p class="hero-welcome-subtitle mb-4">
                        Buku tamu digital Dinas Perindustrian dan Perdagangan
                        Provinsi Jawa Timur.
                    </p>

                    @include('partials.disdag-illustration')
                </div>
            </div>

            {{-- Panel kanan: panduan tamu --}}
            <div class="col-md-6 hero-content-panel">
                <div class="hero-content-inner text-center">
                    <x-icon name="qrcode" class="icon icon-lg text-primary mb-3" />

                    <h2 class="h4 home-welcome-title mb-2">Mengisi Buku Tamu</h2>
                    <p class="text-gray-600 mb-4">
                        Pindai <strong>QR Code</strong> yang tersedia di pintu masuk lokasi kunjungan,
                        lalu verifikasi dengan akun Google Anda.
                    </p>

                    <div class="d-grid">
                        <a href="{{ route('login') }}" class="btn btn-primary btn-login-pill">
                            <x-icon name="lock" class="icon icon-xs me-1" /> Masuk sebagai staf
                        </a>
                    </div>

                    <p class="text-muted small mt-4 mb-0">
                        &copy; {{ date('Y') }} {{ config('sitamu.institution_name') }}
                    </p>
                </div>
            </div>
        </div>
    </main>
@endsection
