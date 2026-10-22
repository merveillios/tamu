@extends('layouts.guest')

@section('title', 'Beranda')

@section('full_page')
    <main class="hero-page d-flex align-items-center py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="card home-card border-0 shadow-lg mx-auto">
                        <div class="row g-0">
                            {{-- Panel ilustrasi animasi Disperindag --}}
                            <div class="col-md-6 hero-illustration-panel d-flex flex-column align-items-center justify-content-center p-4 p-lg-5">
                                @include('partials.disdag-illustration')

                                <div class="hero-panel-caption text-center mt-4">
                                    <div class="hero-panel-heading mb-1">Buku Tamu Digital</div>
                                    <p class="mb-3">
                                        Sistem pencatatan kunjungan tamu Dinas Perindustrian
                                        dan Perdagangan Provinsi Jawa Timur.
                                    </p>
                                    <div>
                                        <span class="hero-feature-badge">
                                            <x-icon name="qrcode" class="icon icon-xs" /> Scan QR
                                        </span>
                                        <span class="hero-feature-badge">
                                            <x-icon name="mail" class="icon icon-xs" /> Verifikasi Google
                                        </span>
                                        <span class="hero-feature-badge">
                                            <x-icon name="lock" class="icon icon-xs" /> Data Aman
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Panel sambutan --}}
                            <div class="col-md-6 d-flex align-items-center">
                                <div class="card-body p-4 p-lg-5 text-center text-md-start w-100">
                                    <img src="{{ asset('assets/img/logo-disperindag.png') }}"
                                         alt="Logo Dinas Perindustrian dan Perdagangan Provinsi Jawa Timur"
                                         class="mb-3 mx-auto mx-md-0 d-block" style="height: 84px; width: auto;">

                                    <h1 class="h4 home-welcome-title mb-2">
                                        Selamat datang di {{ config('app.name') }}
                                    </h1>
                                    <p class="text-gray-600 mb-3">
                                        {{ config('sitamu.institution_name') }}
                                    </p>

                                    <div class="home-qr-hint mb-4">
                                        <x-icon name="qrcode" class="icon icon-xs" />
                                        <span>Untuk mengisi buku tamu, pindai <strong>QR Code</strong> yang tersedia di pintu masuk lokasi kunjungan.</span>
                                    </div>

                                    <div class="d-grid d-md-block">
                                        <a href="{{ route('login') }}" class="btn btn-primary px-4">
                                            <x-icon name="lock" class="icon icon-xs me-1" /> Masuk sebagai staf
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="text-center text-muted small mt-3 mb-0">
                        &copy; {{ date('Y') }} {{ config('sitamu.institution_name') }}
                    </p>
                </div>
            </div>
        </div>
    </main>
@endsection
