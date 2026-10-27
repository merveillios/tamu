{{-- Tombol aksi per baris akun di halaman Pengguna. --}}
@if ($user->locked_until?->isFuture())
    <form method="POST" action="{{ route('admin.users.unlock', $user->id) }}">
        @csrf
        <button type="submit" class="btn btn-sm btn-warning">Buka kunci</button>
    </form>
@endif

@if (! $user->is(auth()->user()))
    <form method="POST" action="{{ route('admin.users.status', $user->id) }}">
        @csrf
        <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}"
                @if ($user->is_active) data-deactivate-message="Nonaktifkan akun {{ $user->name }}? Akun ini tidak bisa digunakan sampai diaktifkan kembali." @endif>
            {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
        </button>
    </form>
@endif
