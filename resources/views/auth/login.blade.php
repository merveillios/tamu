@extends('layouts.auth')

@section('title', 'Login Staf')

@section('content')
    @error('email')
        <div class="alert alert-danger small" role="alert">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ url('/login') }}" class="mt-4">
        @csrf

        <div class="form-group mb-4">
            <label for="email">Email</label>
            <div class="input-group">
                <span class="input-group-text"><x-icon name="mail" class="icon icon-xs text-gray-600" /></span>
                <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}"
                       autocomplete="username" autofocus required>
            </div>
        </div>

        <div class="form-group mb-4">
            <label for="password">Password</label>
            <div class="input-group">
                <span class="input-group-text"><x-icon name="lock" class="icon icon-xs text-gray-600" /></span>
                <input type="password" name="password" id="password" class="form-control" autocomplete="current-password" required>
            </div>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Masuk</button>
        </div>
    </form>
@endsection
