{{-- Email terverifikasi (read-only) dengan tombol Ganti akun (TAM-11). --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3 d-flex justify-content-between align-items-center gap-2">
        <div class="text-break">
            <div class="small text-gray-600">Email terverifikasi</div>
            <div class="fw-bold">{{ $email }}</div>
        </div>
        <form method="POST" action="{{ route('guest.switch', $qr->token) }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-gray-800 text-nowrap">Ganti akun</button>
        </form>
    </div>
</div>
