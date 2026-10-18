@extends('layouts.guest')

@section('title', 'Bantuan Login Google')

@section('content')
    <div class="card border-0 shadow">
        <div class="card-body p-4 text-center">
            <h1 class="h5 mb-3">Tidak bisa login Google?</h1>
            <p class="mb-4">Coba buka halaman ini di Chrome atau Safari. Jika masih belum bisa, silakan hubungi petugas.</p>
            <a href="{{ route('guest.show', $qr->token) }}" class="btn btn-primary w-100">Coba lagi</a>
        </div>
    </div>
@endsection
