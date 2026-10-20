<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body class="bg-soft">
    @hasSection('full_page')
        @yield('full_page')
    @else
        <main class="guest-shell">
            @sectionMissing('hide_header')
                <header class="text-center mb-4">
                    <img src="{{ asset('assets/img/logo-jatim.png') }}" class="brand-logo mb-2" alt="Logo {{ config('sitamu.institution_name') }}">
                    <div class="fw-bold text-gray-800">{{ config('sitamu.institution_name') }}</div>
                    @isset($location)
                        <span class="badge bg-primary guest-location-badge mt-2">{{ $location->name }}</span>
                    @endisset
                </header>
            @endif

            @yield('content')
        </main>
    @endif

    @include('partials.scripts')
</body>
</html>
