@extends('layouts.staff')

@section('title', $user->exists ? 'Ubah Akun' : 'Tambah Akun')

@php
    use App\Enums\UserRole;

    $selectedRole = old('role', $user->role?->value);
@endphp

@section('content')
    <div class="row">
        <div class="col-12 col-xl-8">
            <div class="card card-body border-0 shadow mb-4">
                <h2 class="h5 mb-4">{{ $user->exists ? 'Ubah akun '.$user->name : 'Akun staf baru' }}</h2>

                @error('role')
                    <div class="alert alert-danger" role="alert">{{ $message }}</div>
                @enderror

                <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user->id) : route('admin.users.store') }}" data-user-form>
                    @csrf

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nama akun</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required maxlength="100"
                                   class="form-control @error('name') is-invalid @enderror" aria-describedby="name_error">
                            @error('name')
                                <div class="invalid-feedback" id="name_error">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Contoh: Resepsionis Pintu Timur, PIC Bidang Perdagangan Dalam Negeri.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required maxlength="150"
                                   class="form-control @error('email') is-invalid @enderror" aria-describedby="email_error">
                            @error('email')
                                <div class="invalid-feedback" id="email_error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="role_select" class="form-label">Peran</label>
                        <select name="role" id="role_select" class="form-select" required data-role-select>
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}" @selected($selectedRole === $role->value)>{{ $role->label() }}</option>
                            @endforeach
                        </select>
                        <div class="form-text" data-role-section="{{ UserRole::Receptionist->value }}">
                            Akun resepsionis dihubungkan ke lokasi lewat QR Code (menu Lokasi → QR).
                        </div>
                        <div class="form-text" data-role-section="{{ UserRole::Admin->value }}">
                            Admin mengelola semua lokasi.
                        </div>
                    </div>

                    <div class="mb-3" data-role-section="{{ UserRole::Pic->value }}">
                        <label for="department_id" class="form-label">Departemen</label>
                        <select name="department_id" id="department_id" class="form-select @error('department_id') is-invalid @enderror"
                                aria-describedby="department_id_error">
                            <option value="">Pilih departemen</option>
                            @foreach ($departments as $locationName => $group)
                                <optgroup label="{{ $locationName }}">
                                    @foreach ($group as $department)
                                        <option value="{{ $department->id }}" @selected((int) old('department_id', $user->department_id) === $department->id)>
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('department_id')
                            <div class="invalid-feedback" id="department_id_error">{{ $message }}</div>
                        @enderror
                    </div>


                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">{{ $user->exists ? 'Password baru (kosongkan bila tidak diganti)' : 'Password' }}</label>
                            <input type="password" name="password" id="password" autocomplete="new-password" minlength="8"
                                   @unless ($user->exists) required @endunless
                                   class="form-control @error('password') is-invalid @enderror" aria-describedby="password_error">
                            @error('password')
                                <div class="invalid-feedback" id="password_error">{{ $message }}</div>
                            @enderror
                            @if ($user->exists)
                                <div class="form-text">Mengganti password mengakhiri semua sesi akun ini.</div>
                            @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label">Ulangi password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password" class="form-control">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-gray-200">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/user-form.js') }}"></script>
@endpush
