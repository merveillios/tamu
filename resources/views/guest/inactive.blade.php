@extends('layouts.guest')

@section('title', 'QR Code tidak aktif')

@section('content')
    <div class="card border-0 shadow">
        <div class="card-body p-4 text-center">
            <x-icon name="qrcode" class="icon icon-lg text-gray-400 mb-3" />
            <p class="mb-0">{{ $message }}</p>
        </div>
    </div>
@endsection
