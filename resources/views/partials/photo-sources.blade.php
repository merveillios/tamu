{{--
    Sumber foto KTP/selfie: kamera langsung atau galeri, lalu dipotong di perangkat (photo-field.js).
    Parameter: $type ('ktp'|'selfie'), $name (nama input file untuk pengiriman tanpa JavaScript), $id.
    Tanpa JavaScript, tombol-tombol disembunyikan dan input galeri biasa tetap dipakai.
--}}
<div class="d-flex flex-wrap gap-2 mb-2" data-photo-sources>
    <label class="btn btn-sm btn-outline-primary mb-0" data-photo-js hidden>
        Ambil foto
        <input type="file" accept="image/*" capture="{{ $type === 'selfie' ? 'user' : 'environment' }}"
               class="visually-hidden" data-photo-input>
    </label>
    <label for="{{ $id }}" class="btn btn-sm btn-outline-gray-800 mb-0" data-photo-js hidden>Pilih dari galeri</label>
    <input type="file" name="{{ $name }}" id="{{ $id }}" accept="image/jpeg,image/png,image/webp"
           class="form-control form-control-sm {{ $invalid ?? false ? 'is-invalid' : '' }}" data-photo-input data-photo-fallback>
</div>
