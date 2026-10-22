<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body>
    <main>
        <section class="hero-page min-vh-100 d-flex align-items-center py-4">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-12 col-lg-10">
                        <div class="card home-card border-0 shadow-lg mx-auto">
                            <div class="row g-0">
                                {{-- Panel ilustrasi animasi Disperindag --}}
                                <div class="col-md-6 hero-illustration-panel d-flex flex-column align-items-center justify-content-center p-4 p-lg-5">
                                    @include('partials.disdag-illustration')

                                    <div class="hero-panel-caption text-center mt-4">
                                        <div class="hero-panel-heading mb-1">Buku Tamu Digital</div>
                                        <p class="mb-0">
                                            Pencatatan kunjungan yang cepat, aman, dan terintegrasi
                                            untuk pelayanan yang lebih baik.
                                        </p>
                                    </div>
                                </div>

                                {{-- Panel form login --}}
                                <div class="col-md-6">
                                    <div class="bg-white h-100">
                                        <div class="auth-banner d-flex align-items-center">
                                            <img src="{{ asset('assets/img/logo-disperindag.png') }}" alt="Logo Dinas Perindustrian dan Perdagangan Provinsi Jawa Timur" class="auth-banner-logo-lg me-3">
                                            <div>
                                                <div class="auth-banner-title">Dinas Perindustrian dan Perdagangan</div>
                                                <div class="auth-banner-subtitle">Provinsi Jawa Timur</div>
                                            </div>
                                        </div>

                                        <div class="p-4 p-lg-5 pt-3 pt-lg-4">
                                            @yield('content')
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p class="text-center text-muted small mt-3 mb-0">
                            &copy; {{ date('Y') }} {{ config('sitamu.institution_name') }}
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    @include('partials.flash')
    @include('partials.scripts')
</body>
</html>
