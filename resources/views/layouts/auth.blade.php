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
                        <div class="bg-white shadow border-0 rounded border-light p-4 p-lg-5 w-100 auth-card">
                            <div class="text-center mb-4">
                                <img src="{{ asset('assets/img/logo.png') }}" class="brand-logo mb-3" alt="">
                                <h1 class="mb-1 h3">{{ config('app.name') }}</h1>
                                <p class="small text-gray-600 mb-0">{{ config('sitamu.institution_name') }}</p>
                            </div>

                            @yield('content')
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
