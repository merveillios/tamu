{{-- Kartu angka dashboard bergaya Volt. --}}
<div class="col-6 col-md-4 col-xl mb-4">
    <div class="card border-0 shadow h-100">
        <div class="card-body">
            <div class="small text-gray-600 mb-1">{{ $label }}</div>
            <div class="h3 fw-extrabold mb-0 text-{{ $color ?? 'gray-800' }}">{{ number_format($value, 0, ',', '.') }}</div>
        </div>
    </div>
</div>
