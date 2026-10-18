<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body>
    {{-- Navbar ponsel: tombol pembuka sidebar (offcanvas) --}}
    <nav class="navbar navbar-dark navbar-theme-primary px-4 col-12 d-lg-none">
        <a class="navbar-brand d-flex align-items-center" href="{{ url('/admin') }}">
            <img src="{{ asset('assets/img/logo.png') }}" class="brand-logo-sm me-2" alt="">
            <span class="fw-bold">{{ config('app.name') }}</span>
        </a>
        <button class="navbar-toggler d-lg-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu"
                aria-controls="sidebarMenu" aria-expanded="false" aria-label="Buka menu">
            <span class="navbar-toggler-icon"></span>
        </button>
    </nav>

    @include('partials.staff.sidebar')

    <main class="content">
        @include('partials.staff.topbar')

        <div class="py-4">
            @yield('content')
        </div>

        <footer class="bg-white rounded shadow p-4 mb-4 mt-4">
            <p class="mb-0 text-center small text-gray-600">
                &copy; {{ now()->year }} {{ config('sitamu.institution_name') }}
            </p>
        </footer>
    </main>

    @include('partials.flash')
    @include('partials.scripts')
</body>
</html>
