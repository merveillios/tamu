@extends('layouts.auth')

@section('title', 'Login Staf')

@section('content')
    <h1 class="h3 fw-bold mb-1" style="color: #123c6e;">Masuk</h1>
    <p class="text-gray-600 small mb-4">Gunakan akun staf Anda untuk melanjutkan.</p>

    @if ($errors->any())
        <div class="alert alert-danger small" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.store') }}">
        @csrf

        <div class="form-group mb-3">
            <label for="email" class="small fw-semibold text-gray-700">Email</label>
            <div class="input-group login-input-group">
                <span class="input-group-text"><x-icon name="mail" class="icon icon-xs text-gray-600" /></span>
                <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}"
                       placeholder="Masukkan email Anda" autocomplete="username" autofocus required>
            </div>
        </div>

        <div class="form-group mb-4">
            <label for="password" class="small fw-semibold text-gray-700">Password</label>
            <div class="input-group login-input-group">
                <span class="input-group-text"><x-icon name="lock" class="icon icon-xs text-gray-600" /></span>
                <input type="password" name="password" id="password" class="form-control"
                       placeholder="Masukkan password Anda" autocomplete="current-password" required>
            </div>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-login-pill">Masuk</button>
        </div>
    </form>
@endsection
