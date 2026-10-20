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

                {{-- Tombol resmi Google --}}
                <button type="button"
                        class="btn-google-auth w-100 d-flex align-items-center justify-content-center"
                        id="googleSignInBtn"
                        data-google-button
                        data-client-id="{{ $clientId }}"
                        data-nonce="{{ $nonce }}"
                        data-verify-url="{{ route('guest.verify', $qr->token) }}">
                    <svg class="google-icon me-2" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <span class="google-btn-text">Lanjutkan dengan Google</span>
                </button>

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
