<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body class="auth-page-body">
    {{-- Hiasan background di luar kartu --}}
    <div class="auth-bg-blob-tr" aria-hidden="true"></div>
    <div class="auth-bg-ring-bl" aria-hidden="true"></div>

    {{-- Container utama kartu login (tengah halaman) --}}
    <main class="auth-page-wrapper">
        <div class="auth-card">
            {{-- Panel Kiri: Gradasi Biru SiTamu (±45%) --}}
            <div class="auth-card-left">
                {{-- Logo lambang Jawa Timur di pojok kiri atas --}}
                <div class="auth-card-logo-wrap">
                    <img src="{{ asset('assets/img/logo-jatim.png') }}"
                         alt="Logo Provinsi Jawa Timur"
                         class="auth-card-logo">
                </div>

                {{-- Hiasan titik-titik (dot grid) di kanan atas panel --}}
                <svg class="auth-card-dots auth-card-dots-tr" viewBox="0 0 76 76" width="76" height="76" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <g fill="#ffffff" opacity="0.45">
                        @for ($row = 0; $row < 5; $row++)
                            @for ($col = 0; $col < 5; $col++)
                                <circle cx="{{ 6 + $col * 16 }}" cy="{{ 6 + $row * 16 }}" r="2.2"/>
                            @endfor
                        @endfor
                    </g>
                </svg>

                {{-- Hiasan titik-titik (dot grid) di kiri bawah panel --}}
                <svg class="auth-card-dots auth-card-dots-bl" viewBox="0 0 76 76" width="76" height="76" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <g fill="#ffffff" opacity="0.45">
                        @for ($row = 0; $row < 5; $row++)
                            @for ($col = 0; $col < 5; $col++)
                                <circle cx="{{ 6 + $col * 16 }}" cy="{{ 6 + $row * 16 }}" r="2.2"/>
                            @endfor
                        @endfor
                    </g>
                </svg>

                {{-- Konten tengah panel kiri: Ilustrasi HP + Petugas, Judul, Subjudul --}}
                <div class="auth-card-left-content">
                    <div class="auth-illustration-wrap">
                        <img src="{{ asset('assets/img/ilustrasi-login-petugas.png') }}"
                             alt="Ilustrasi Petugas SiTamu"
                             class="auth-card-illustration">
                    </div>

                    <div class="auth-welcome-wrap">
                        <h1 class="auth-welcome-title">Selamat Datang!</h1>
                        <p class="auth-welcome-subtitle">
                            Masuk untuk mengelola buku tamu digital Dinas Perindustrian dan Perdagangan Provinsi Jawa Timur.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Panel Kanan: Area Form Putih (±55%) --}}
            <div class="auth-card-right">
                <div class="auth-card-right-inner">
                    @yield('content')

                    <p class="auth-copyright">
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
