{{-- Toast notifikasi dari flash session 'success' dan 'error'. --}}
<div class="toast-container position-fixed top-0 end-0 p-3">
    @foreach (['success' => 'success', 'error' => 'danger'] as $key => $color)
        @if (session($key))
            <div class="toast align-items-center text-white bg-{{ $color }} border-0" role="alert" aria-live="assertive" aria-atomic="true" data-autoshow>
                <div class="d-flex">
                    <div class="toast-body">{{ session($key) }}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup"></button>
                </div>
            </div>
        @endif
    @endforeach
</div>
