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

## 2026-10-18 — Service tambahan di M1

- **Konteks:** Bab 5.2 tidak mencantumkan service untuk login dan akun staf, tetapi Bab 5.3 meminta aturan bisnis berada di Service, bukan di controller.
- **Keputusan:** Menambah `LoginService` (pemeriksaan kredensial dan penguncian akun, AUTH-1) dan `UserAccountService` (USR-1, USR-2, USR-4, USR-5, AUTH-4).
- **Alasan:** Controller tetap tipis dan aturan USR-4/USR-5 dapat dites langsung.

## 2026-10-18 — Pesan login untuk akun nonaktif dan terkunci

- **Konteks:** AUTH-1 meminta pesan gagal selalu "Email atau password salah."
- **Keputusan:** Akun nonaktif dan akun terkunci juga mendapat pesan yang sama; alasan sebenarnya (`unknown_email`, `wrong_password`, `locked`, `inactive`) hanya tercatat di `audit_logs.detail`. Password benar pada akun nonaktif tidak menambah hitungan gagal.
- **Alasan:** Tidak membocorkan keberadaan atau status akun.

## 2026-10-18 — Cakupan Admin pada lokasi dan cache AccessScope

- **Konteks:** Bab 12.2 membatasi QR Admin per lokasi tetapi membebaskan departemen, kategori, dan petugas di semua lokasi.
- **Keputusan:** `Location::forUser()` untuk Admin hanya mengembalikan lokasi yang ditugaskan (dipakai untuk filter pemantauan). Departemen, kategori, dan petugas tanpa filter untuk Admin. Hasil `locationIds()` dan daftar QR resepsionis di-cache di atribut request, bukan di properti service.
- **Alasan:** Cache per request tidak terbawa ke request berikutnya, termasuk di tes yang menjalankan banyak request pada satu instance aplikasi.

## 2026-10-18 — Data contoh lokal

- **Konteks:** CRUD lokasi dan departemen baru dibuat di M2, padahal form akun M1 membutuhkannya.
- **Keputusan:** `php artisan db:seed` menjalankan `LocalSampleSeeder` hanya jika `APP_ENV=local`. Password akun contoh dibuat acak dan ditampilkan sekali di konsol.
- **Alasan:** A-4 melarang password di kode dan A-10 melarang data dummy di produksi.

## 2026-10-18 — Library QR Code

- **Konteks:** Bab 5.1 mengizinkan `endroid/qr-code` tanpa menyebut versi; versi 6 mensyaratkan PHP 8.2.
- **Keputusan:** `endroid/qr-code` 5.1 (turunannya `bacon/bacon-qr-code` dan `dasprid/enum`). Gambar memakai koreksi galat tinggi, sisi 600 px ditambah margin (hasil 648 px). Halaman cetak memakai SVG sebagai data URI sehingga tidak perlu `{!! !!}`.
- **Alasan:** Kompatibel dengan PHP 8.1; koreksi galat tinggi menjaga QR tercetak tetap terbaca walau sedikit rusak.

## 2026-10-18 — Service master data di M2

- **Konteks:** Bab 5.2 hanya mencantumkan `QrService`, sedangkan Bab 5.3 meminta aturan bisnis di Service.
- **Keputusan:** Menambah `LocationService`, `DepartmentService`, `PurposeCategoryService`, dan `StaffMemberService`. Keunikan nama departemen dan petugas dicek di service; collation `utf8mb4_unicode_ci` membuat perbandingan tidak membedakan huruf besar/kecil.
- **Alasan:** Controller tetap tipis dan setiap aksi tercatat di audit log dari satu tempat.

## 2026-10-18 — Lokasi untuk kategori keperluan dan petugas

- **Konteks:** Admin memantau kunjungan hanya di lokasinya, tetapi mengelola kategori dan petugas di semua lokasi (Bab 3, Bab 17 No. 27).
- **Keputusan:** `AccessScope::manageableLocations()` mengembalikan semua lokasi untuk Super Admin dan Admin, dan tidak ada lokasi untuk peran lain. `findManageableLocationOrDeny()` dipakai saat menambah kategori; ID departemen dan petugas tetap dicari lewat `findOrDeny()`.
- **Alasan:** Filter cakupan tetap di satu titik pusat (A-6) tanpa mencampur aturan pemantauan dan pengelolaan.

## 2026-10-18 — Detail perilaku master data

- **Konteks:** Beberapa hal tidak dijawab eksplisit oleh PRD.
- **Keputusan:**
  - Lokasi kategori keperluan dan petugas ditentukan saat dibuat dan tidak dapat dipindah, seperti departemen (DEP-1), karena saran departemen bergantung pada lokasi.
  - QR yang sudah dicabut tetap ditampilkan di bawah daftar sebagai riwayat, tanpa tombol aksi; server menolak perubahan status atau regenerate pada QR itu.
  - Ganti resepsionis hanya untuk QR yang belum dicabut; kunjungan aktif ikut berpindah karena cakupan resepsionis dihitung dari kepemilikan QR.
- **Alasan:** Pilihan paling sederhana dan aman (A-8).

## 2026-10-18 — Detail halaman tamu (M3)

- **Konteks:** Beberapa detail alur tamu tidak dijawab eksplisit oleh PRD.
- **Keputusan:**
  - Hasil verifikasi email, nonce Google, dan token formulir disimpan di session dengan kunci per QR (`guest.{qr_id}.*`), sehingga verifikasi di satu QR tidak berlaku di QR lain.
  - Nonce dipakai sekali (diambil dengan `pull`). Bila verifikasi gagal, server mengirim nonce baru dan tombol Google diinisialisasi ulang tanpa memuat ulang halaman.
  - Token formulir (TAM-14) dikonsumsi sebelum validasi; bila validasi gagal, formulir yang tampil ulang mendapat token baru.
  - Honeypot terisi menampilkan halaman terima kasih generik tanpa nomor urut.
  - Waktu verifikasi Google disimpan sebagai `email_verified_at`.
- **Alasan:** Pilihan paling sederhana yang tetap memenuhi TAM-8, TAM-11, dan TAM-14.

## 2026-10-18 — Uji nomor urut bersamaan

- **Konteks:** DoD M3 meminta dua kiriman bersamaan mendapat nomor berbeda.
- **Keputusan:** Keunikan dijamin oleh `INSERT ... ON DUPLICATE KEY UPDATE ... LAST_INSERT_ID()` di MySQL dan unique key (`location_id`, `visit_date`, `daily_number`). Tes otomatis menguji urutan nomor per lokasi dan per hari; uji kiriman yang benar-benar paralel dilakukan manual.
- **Alasan:** PHPUnit menjalankan request berurutan dalam satu proses sehingga tidak dapat mensimulasikan dua koneksi paralel dengan andal.

## 2026-10-18 — Kode respons aksi kunjungan (M4)

- **Konteks:** Bab 7.1 meminta transisi tidak sah ditolak 422, sedangkan Bab 7.2 meminta 409 bila status sudah diubah petugas lain.
- **Keputusan:** Aksi pada kunjungan yang sudah ditutup (Selesai/Dibatalkan), misalnya penugasan, mendapat 422 `INVALID_TRANSITION`. Aksi pada kunjungan aktif yang statusnya sudah berbeda dari yang diharapkan (misalnya Selesai saat masih Menunggu, Terima kedua kali, atau Batal yang kalah balapan) mendapat 409 `STATUS_CONFLICT`, karena biasanya berasal dari daftar yang belum diperbarui. Batal pada kunjungan yang sudah ditutup juga 409 karena skenario umumnya adalah dua petugas menekan tombol bersamaan.
- **Alasan:** Antarmuka memuat ulang daftar pada 409 sehingga petugas langsung melihat status terbaru.

## 2026-10-18 — Daftar Tamu Aktif dan Penugasan

- **Konteks:** RSP-5 dan RSP-7 meminta aksi tanpa reload dan pembaruan otomatis.
- **Keputusan:** Baris daftar dirender di browser dari JSON polling (`/admin/tamu/data`, `/admin/penugasan/data`) memakai `textContent`, tanpa HTML dari server, sehingga tampilan awal dan hasil polling selalu sama. Bunyi notifikasi PIC (opsional pada PIC-4) belum dibuat.
- **Alasan:** Satu jalur render lebih sederhana dan aman terhadap XSS (A-7).

## 2026-10-18 — Lingkungan tes

- **Konteks:** A-15 mewajibkan tes memakai MySQL/MariaDB terpisah.
- **Keputusan:** Database `sitamu_test` lewat `.env.testing` (contoh: `.env.testing.example`). `BCRYPT_ROUNDS=4` hanya di lingkungan tes agar tes cepat; produksi tetap minimal 12.
- **Alasan:** Tes nomor urut (Bab 9.3) bergantung pada perilaku MySQL.
