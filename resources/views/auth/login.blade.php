@extends('layouts.auth')

@section('title', 'Login Staf')

@section('content')
    <h1 class="auth-form-title">Masuk</h1>
    <p class="auth-form-subtitle">Gunakan akun staf Anda untuk melanjutkan.</p>

    @if ($errors->any())
        <div class="alert alert-danger small mb-3" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.store') }}">
        @csrf

        <div class="form-group mb-3 text-start">
            <label for="email" class="form-label auth-input-label">Email</label>
            <input type="email" name="email" id="email" class="form-control auth-input-pill" value="{{ old('email') }}"
                   placeholder="Masukkan email Anda" autocomplete="username" autofocus required>
        </div>

        <div class="form-group mb-4 text-start">
            <label for="password" class="form-label auth-input-label">Password</label>
            <input type="password" name="password" id="password" class="form-control auth-input-pill"
                   placeholder="Masukkan password Anda" autocomplete="current-password" required>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary auth-btn-pill">Masuk</button>
        </div>
    </form>
@endsection
