{{-- Banner biru instansi dengan logo Jatim, nama instansi, dan badge lokasi --}}
<div class="guest-header-banner text-center text-white">
    <img src="{{ asset('assets/img/logo-jatim.png') }}" class="guest-banner-logo mb-2" alt="Logo {{ config('sitamu.institution_name') }}">
    <div class="guest-banner-title mb-2">{{ config('sitamu.institution_name') }}</div>
    @isset($location)
        <div>
            <span class="badge guest-banner-badge guest-location-badge px-3 py-1.5 rounded-pill">{{ $location->name }}</span>
        </div>
    @endisset
</div>
