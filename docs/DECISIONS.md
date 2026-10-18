# Catatan Keputusan SiTamu

Format mengikuti aturan A-8: tanggal, konteks, keputusan, alasan.

## 2026-10-18 — Versi PHP dan Laravel

- **Konteks:** PRD Bab 17 No. 10 meminta Laravel mayor stabil terbaru yang kompatibel dengan PHP server. Mesin pengembangan (Laragon) hanya memiliki PHP 8.1.10, dan pemilik proyek memilih tetap memakai PHP 8.1.
- **Keputusan:** Laravel 10 (`laravel/framework` 10.50.3, skeleton `laravel/laravel` v10.3.3).
- **Alasan:** Laravel 11 ke atas mensyaratkan PHP 8.2+.
- **Risiko yang harus diketahui klien:**
  - Laravel 10 sudah tidak menerima perbaikan keamanan. `composer audit` per 18 Okt 2026 melaporkan 4 advisory pada `laravel/framework` yang hanya diperbaiki di Laravel 12/13:
    - XSS pada halaman debug → dimitigasi dengan `APP_DEBUG=false` di produksi (Bab 12.1).
    - *Temporary signed URL path confusion* → aplikasi tidak memakai signed URL.
    - Dua advisory CRLF injection pada aturan validasi `email` bawaan → email tamu selalu berasal dari Google (TAM-11); email akun staf hanya diinput Super Admin dan disimpan huruf kecil.
  - **Rekomendasi:** naikkan PHP server ke 8.3 dan migrasikan ke Laravel versi terbaru sebelum produksi.

## 2026-10-18 — Penyesuaian struktur Laravel 10

- **Konteks:** PRD ditulis dengan struktur Laravel 11 (`bootstrap/app.php`).
- **Keputusan:** Alias dan grup middleware didaftarkan di `app/Http/Kernel.php`; penanganan exception tetap di `app/Exceptions/Handler.php`. Route hanya dari `routes/web.php`; `routes/api.php` dan registrasinya di `RouteServiceProvider` dihapus.
- **Alasan:** Itulah tempat konfigurasi yang benar di Laravel 10; perilakunya setara.

## 2026-10-18 — Paket bawaan skeleton yang dilepas

- **Konteks:** Skeleton Laravel 10 membawa `laravel/sanctum`, `spatie/laravel-ignition`, dan berkas Vite/npm.
- **Keputusan:** Sanctum (beserta `config/sanctum.php` dan migrasi `personal_access_tokens`), Ignition, `package.json`, `vite.config.js`, dan `resources/js|css` dihapus. `laravel/sail` dibiarkan sebagai paket dev bawaan skeleton karena tidak memengaruhi aplikasi.
- **Alasan:** A-3 melarang Sanctum dan paket `spatie/*`; Bab 4.2 melarang build frontend dengan Node/Vite.

## 2026-10-18 — Key `CACHE_STORE`

- **Konteks:** PRD Bab 5.4 memakai key `CACHE_STORE`, sedangkan Laravel 10 membaca `CACHE_DRIVER`.
- **Keputusan:** `config/cache.php` diubah agar membaca `CACHE_STORE`.
- **Alasan:** Menjaga `.env` tetap sama dengan PRD.

## 2026-10-18 — Template Volt, Bootstrap, dan font

- **Konteks:** Bab 5.6 meminta Volt v1.4.1 dibangun sekali tanpa npm di server, tanpa CDN, dan sesuai CSP `'self'`.
- **Keputusan:**
  - Volt di-*clone* di luar repositori, dipasang dengan `npm ci` (mengikuti `package-lock.json`), lalu `gulp build:dist`. Yang disalin hanya `volt.css`, `bootstrap.bundle.min.js`, dan `LICENSE.md` ke `public/assets/vendor/volt/`.
  - Bootstrap yang dipakai adalah **5.1.3**, versi yang dikunci `package-lock.json` Volt. Ini berbeda dari "Bootstrap 5.3" di Bab 5.1; sesuai Bab 5.6, Bootstrap bawaan Volt yang dipakai dan tidak ada file Bootstrap kedua.
  - Baris `@import` Google Fonts di `src/scss/volt.scss` dihapus sebelum build. Font Inter (latin 300–800, OFL) diambil dari paket `@fontsource/inter` 5.0.20 dan disajikan lokal di `public/assets/vendor/inter/`.
  - `volt.js` dan plugin demo Volt (Chartist, Notyf, SweetAlert2, dan lainnya) tidak dipakai; interaksi memakai JavaScript Bootstrap dan `public/assets/js/`.
- **Alasan:** Semua aset lokal, tanpa CDN dan tanpa Node di server.
- **Cara build ulang:** clone Volt v1.4.1, hapus baris Google Fonts di `src/scss/volt.scss`, jalankan `npm ci --ignore-scripts` lalu `npx gulp build:dist`, dan salin `dist/css/volt.css` serta `node_modules/bootstrap/dist/js/bootstrap.bundle.min.js`.

## 2026-10-18 — CSP untuk gambar

- **Konteks:** Bab 12.1 meminta CSP hanya `'self'`. CSS Bootstrap memakai ikon SVG `data:` untuk select, checkbox, dan validasi form.
- **Keputusan:** `img-src 'self' data:`; sumber lain tetap `'self'`. Ditambah `object-src 'none'`, `base-uri 'self'`, `form-action 'self'`, dan `frame-ancestors 'none'`.
- **Alasan:** Tanpa `data:`, ikon form Bootstrap tidak tampil. `data:` di `img-src` tidak membuka jalan untuk menjalankan skrip.

## 2026-10-18 — Kolom `remember_token`

- **Konteks:** DDL `users` di Bab 9.2 tidak memiliki `remember_token`.
- **Keputusan:** Model `User` memakai `$rememberTokenName = ''`; fitur "ingat saya" tidak disediakan.
- **Alasan:** Mengikuti skema PRD; sesi staf dibatasi `SESSION_LIFETIME`.

## 2026-10-18 — Halaman sementara di M0

- **Konteks:** Definition of Done M0 meminta halaman ber-layout tampil, padahal login baru dibangun di M1 dan dashboard di M6.
- **Keputusan:** M0 menyediakan `/` (beranda informasi), `GET /login` (tampilan form saja), dan `/admin` (beranda staf sementara).
- **Alasan:** Agar layout tamu, login, dan staf dapat diperiksa dan dites sejak M0. Ketiganya diganti atau dilengkapi di milestone terkait.

## 2026-10-18 — Lingkungan tes

- **Konteks:** A-15 mewajibkan tes memakai MySQL/MariaDB terpisah.
- **Keputusan:** Database `sitamu_test` lewat `.env.testing` (contoh: `.env.testing.example`). `BCRYPT_ROUNDS=4` hanya di lingkungan tes agar tes cepat; produksi tetap minimal 12.
- **Alasan:** Tes nomor urut (Bab 9.3) bergantung pada perilaku MySQL.
