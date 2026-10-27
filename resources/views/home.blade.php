@extends('layouts.auth')

@section('title', 'Beranda')

@section('welcome_subtitle', 'Buku tamu digital Dinas Perindustrian dan Perdagangan Provinsi Jawa Timur.')

{{-- Beranda memakai kartu yang sama dengan halaman login; panel kanan berisi panduan tamu. --}}
@section('content')
    <div class="text-center">
        <x-icon name="qrcode" class="icon icon-lg text-primary mb-3" />

        <h2 class="auth-form-title">Mengisi Buku Tamu</h2>
        <p class="auth-form-subtitle">
            Pindai <strong>QR Code</strong> yang tersedia di pintu masuk lokasi kunjungan,
            lalu verifikasi dengan akun Google Anda.
        </p>

        <div class="d-grid">
            <a href="{{ route('login') }}" class="btn btn-primary auth-btn-pill">
                <x-icon name="lock" class="icon icon-xs me-1" /> Masuk sebagai staf
            </a>
        </div>
    </div>
@endsection
