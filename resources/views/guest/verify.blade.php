@extends('layouts.guest')

@section('title', 'Selamat Datang - SiTamu')

@section('full_page')
    <div class="guest-verify-wrapper min-vh-100 d-flex flex-column align-items-center justify-content-center py-4 px-3">
        <div class="guest-card bg-white shadow border-0 rounded-3 overflow-hidden w-100">
            {{-- Header banner biru navy --}}
            <div class="auth-banner d-flex align-items-center">
                <img src="{{ asset('assets/img/logo-jatim.png') }}" alt="Logo Provinsi Jawa Timur" class="auth-banner-logo me-3">
                <div>
                    <div class="auth-banner-title">Dinas Perindustrian Dan Perdagangan</div>
                    <div class="auth-banner-subtitle">Provinsi Jawa Timur</div>
                </div>
            </div>

            {{-- Nama lokasi QR di bawah banner --}}
            @isset($location)
                <div class="text-center pt-3 pb-0 px-3">
                    <span class="badge bg-primary guest-location-badge px-3 py-2 rounded-pill">{{ $location->name }}</span>
                </div>
            @endisset

            {{-- Konten kartu --}}
            <div class="p-4 pt-3 text-center">
                <h1 class="guest-verify-title mb-2">Selamat Datang</h1>
                <p class="guest-verify-subtitle text-gray-600 mb-4">Silakan verifikasi email Anda menggunakan akun Google untuk mengisi buku tamu.</p>

                <div class="alert alert-danger small text-start {{ session('error') ? '' : 'd-none' }}" role="alert" data-google-error>
                    {{ session('error') }}
                </div>

                {{-- Tombol resmi Google dirender guest-google.js di dalam elemen ini (TAM-11). --}}
                <div class="guest-google-button d-flex justify-content-center"
                     data-google-button
                     data-client-id="{{ $clientId }}"
                     data-nonce="{{ $nonce }}"
                     data-verify-url="{{ route('guest.verify', $qr->token) }}">
                    <span class="small text-gray-500 py-2">Memuat tombol Google…</span>
                </div>

                <noscript>
                    <p class="small text-danger mt-2">Aktifkan JavaScript di browser untuk masuk dengan akun Google.</p>
                </noscript>

                <hr class="guest-verify-divider my-4">

                <div>
                    <a href="{{ route('guest.help', $qr->token) }}" class="guest-help-link">Tidak bisa login Google?</a>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <footer class="guest-verify-footer text-center mt-4">
            <div>© 2026 Dinas Perindustrian dan Perdagangan</div>
            <div>Provinsi Jawa Timur</div>
        </footer>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/guest-google.js') }}"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
@endpush
