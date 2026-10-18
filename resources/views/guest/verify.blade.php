@extends('layouts.guest')

@section('title', 'Isi Buku Tamu')

@section('content')
    <div class="card border-0 shadow">
        <div class="card-body p-4 text-center">
            <h1 class="h4 mb-2">Isi Buku Tamu</h1>
            <p class="text-gray-600 mb-4">Pilih akun Google Anda untuk memverifikasi email sebelum mengisi buku tamu.</p>

            <div class="alert alert-danger small text-start {{ session('error') ? '' : 'd-none' }}" role="alert" data-google-error>
                {{ session('error') }}
            </div>

            <div class="d-flex justify-content-center mb-3"
                 data-google-button
                 data-client-id="{{ $clientId }}"
                 data-nonce="{{ $nonce }}"
                 data-verify-url="{{ route('guest.verify', $qr->token) }}">
            </div>

            <noscript>
                <p class="small text-danger">Aktifkan JavaScript di browser untuk masuk dengan akun Google.</p>
            </noscript>

            <a href="{{ route('guest.help', $qr->token) }}" class="small">Tidak bisa login Google?</a>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/guest-google.js') }}"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
@endpush
