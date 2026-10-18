{{-- Tombol aksi per baris akun di halaman Pengguna. --}}
@if ($user->locked_until?->isFuture())
    <form method="POST" action="{{ route('admin.users.unlock', $user->id) }}">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-warning">Buka kunci</button>
    </form>
@endif

@if (! $user->is(auth()->user()))
    <form method="POST" action="{{ route('admin.users.status', $user->id) }}"
          data-confirm="{{ $user->is_active ? 'Nonaktifkan akun '.$user->name.'? Akun langsung ditolak pada request berikutnya.' : 'Aktifkan kembali akun '.$user->name.'?' }}">
        @csrf
        <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
            {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
        </button>
    </form>
@endif
