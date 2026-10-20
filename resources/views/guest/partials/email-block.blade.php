{{-- Banner instansi & kartu email terverifikasi yang menyatu --}}
<div class="card border-0 shadow-sm mb-4 overflow-hidden guest-form-header-card">
    @include('guest.partials.banner')

    {{-- Kartu Email terverifikasi --}}
    <div class="card-body py-3 d-flex justify-content-between align-items-center gap-2 bg-white">
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
