<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body>
    <main>
        <section class="vh-lg-100 mt-5 mt-lg-0 bg-soft d-flex align-items-center">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-12 d-flex align-items-center justify-content-center">
                        <div class="bg-white shadow border-0 rounded border-light overflow-hidden w-100 auth-card">
                            <div class="auth-banner d-flex align-items-center">
                                <img src="{{ asset('assets/img/logo-jatim.png') }}" alt="Logo Provinsi Jawa Timur" class="auth-banner-logo me-3">
                                <div>
                                    <div class="auth-banner-title">Dinas Perindustrian Dan Perdagangan</div>
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
        </section>
    </main>

    @include('partials.flash')
    @include('partials.scripts')
</body>
</html>
