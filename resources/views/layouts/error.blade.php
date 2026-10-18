<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body>
    <main>
        <section class="vh-100 d-flex align-items-center justify-content-center bg-soft">
            <div class="container">
                <div class="row">
                    <div class="col-12 col-md-8 col-lg-6 mx-auto text-center">
                        <img src="{{ asset('assets/img/logo.png') }}" class="brand-logo mb-4" alt="">
                        <div class="error-code mb-3">@yield('code')</div>
                        <h1 class="h3 mb-3">@yield('title')</h1>
                        <p class="lead text-gray-600 mb-4">@yield('message')</p>
                        <a href="{{ url('/') }}" class="btn btn-primary d-inline-flex align-items-center">
                            <x-icon name="arrow-left" class="icon icon-xs me-2" />
                            Kembali ke beranda
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
