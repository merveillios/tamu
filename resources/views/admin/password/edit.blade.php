@extends('layouts.staff')

@section('title', 'Ganti Password')

@section('content')
    <div class="row">
        <div class="col-12 col-xl-6">
            <div class="card card-body border-0 shadow mb-4">
                <h2 class="h5 mb-1">Ganti Password</h2>
                <p class="small text-gray-600 mb-4">Setelah password diganti, semua sesi akun ini berakhir dan Anda perlu login kembali.</p>

                <form method="POST" action="{{ route('admin.password.update') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Password lama</label>
                        <input type="password" name="current_password" id="current_password" autocomplete="current-password" required
                               class="form-control @error('current_password') is-invalid @enderror" aria-describedby="current_password_error">
                        @error('current_password')
                            <div class="invalid-feedback" id="current_password_error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password baru</label>
                        <input type="password" name="password" id="password" autocomplete="new-password" required minlength="8"
                               class="form-control @error('password') is-invalid @enderror" aria-describedby="password_error">
                        @error('password')
                            <div class="invalid-feedback" id="password_error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Ulangi password baru</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password" required
                               class="form-control">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-gray-200">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
