<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body>
    <main class="container-fluid p-0">
        <div class="row g-0 hero-split-row">
            {{-- Panel kiri: sambutan dengan dekorasi biru Disperindag --}}
            <div class="col-md-6 hero-illustration-panel">
                @include('partials.hero-deco')

                {{-- Logo + nama instansi di pojok kiri atas --}}
                <div class="hero-brand d-flex align-items-center">
                    <img src="{{ asset('assets/img/logo-disperindag.png') }}"
                         alt="Logo Dinas Perindustrian dan Perdagangan Provinsi Jawa Timur"
                         class="hero-logo me-3">
                    <div class="hero-brand-text text-start">
                        Dinas Perindustrian dan Perdagangan<br>Provinsi Jawa Timur
                    </div>
                </div>

                <div class="position-relative text-center text-md-start" style="z-index: 1; max-width: 420px;">
                    <h1 class="hero-welcome-title mb-3">Selamat Datang!</h1>
                    <p class="hero-welcome-subtitle mb-4">
                        Masuk untuk mengelola buku tamu digital Dinas Perindustrian
                        dan Perdagangan Provinsi Jawa Timur.
                    </p>

                    @include('partials.disdag-illustration')
                </div>
            </div>

            {{-- Panel kanan: form login --}}
            <div class="col-md-6 hero-content-panel">
                <div class="hero-content-inner">
                    @yield('content')

                    <p class="text-center text-muted small mt-4 mb-0">
                        &copy; {{ date('Y') }} {{ config('sitamu.institution_name') }}
                    </p>
                </div>
            </div>
        </div>
    </main>

    @include('partials.flash')
    @include('partials.scripts')
</body>
</html>
