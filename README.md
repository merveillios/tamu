# SiTamu — Sistem Informasi Buku Tamu

Buku tamu digital berbasis QR Code untuk Dinas Perindustrian dan Perdagangan Provinsi Jawa Timur. Tamu mengisi formulir lewat ponsel, resepsionis menugaskan kunjungan ke departemen, PIC melayani dan menulis hasil, lalu hasil tampil di riwayat tamu.

Spesifikasi lengkap ada di [docs/PRD.md](docs/PRD.md); keputusan teknis di [docs/DECISIONS.md](docs/DECISIONS.md).

## Teknologi

- PHP 8.1, Laravel 10 (tanpa starter kit), MySQL 8.0+ atau MariaDB 10.6+
- `endroid/qr-code` 5.1 untuk gambar QR Code
- `phpoffice/phpspreadsheet` 5.8 dan `dompdf/dompdf` 3.1 untuk ekspor Excel dan PDF
- Blade, Bootstrap 5.1 (bawaan template [Volt](https://github.com/themesberg/volt-bootstrap-5-dashboard)), JavaScript vanilla
- Tidak butuh Node, cron, atau queue worker di server

> **Catatan keamanan:** Laravel 10 sudah tidak menerima perbaikan keamanan. Lihat [docs/DECISIONS.md](docs/DECISIONS.md) untuk risiko dan rekomendasi upgrade sebelum produksi.

## Instalasi lokal

1. Pasang dependensi:

   ```bash
   composer install
   ```

2. Salin konfigurasi lalu isi kredensial database:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Buat database kosong (`utf8mb4_unicode_ci`), misalnya `sitamu`, lalu jalankan migrasi:

   ```bash
   php artisan migrate
   ```

4. Buat akun Super Admin pertama (tidak ada password default):

   ```bash
   php artisan sitamu:create-superadmin
   ```

   Untuk pengembangan lokal (`APP_ENV=local`) tersedia data contoh: lokasi, departemen, petugas, kategori, QR, dan akun per peran. Password tiap akun dibuat acak dan hanya ditampilkan sekali di konsol:

   ```bash
   php artisan migrate:fresh --seed
   ```

5. Jalankan server pengembangan dan buka `http://127.0.0.1:8000`:

   ```bash
   php artisan serve
   ```

## Verifikasi Google untuk halaman tamu

Halaman tamu memakai Google Identity Services. Isi `GOOGLE_CLIENT_ID` di `.env` dengan OAuth Client ID (jenis *Web application*) milik instansi, lalu di Google Cloud Console → *Credentials* → Client ID tersebut, tambahkan setiap alamat yang dipakai ke **Authorized JavaScript origins**, misalnya:

- `http://localhost:8000` dan `http://127.0.0.1:8000` untuk pengembangan lokal
- domain produksi, misalnya `https://sitamu.contoh.go.id`

Tanpa origin yang terdaftar, tombol "Pilih akun Google" tidak muncul atau menolak login. Client secret tidak dibutuhkan.

Untuk mencoba halaman tamu secara lokal, buka menu **Lokasi → QR → Cetak** sebagai Super Admin; URL di bawah QR mengikuti `APP_URL`.

## Tes dan kerapian kode

Tes memakai database MySQL terpisah, bukan SQLite.

1. Buat database `sitamu_test`, lalu salin `.env.testing.example` menjadi `.env.testing` dan isi `APP_KEY` serta kredensial database.
2. Jalankan:

   ```bash
   php artisan test
   ```

   ```bash
   vendor/bin/pint --test
   ```

## Struktur penting

| Lokasi | Isi |
| --- | --- |
| `app/Enums` | `VisitStatus`, `UserRole`, `QrStatus`, `VulnerableGroup` |
| `app/Models` | Model Eloquent dan trait `Concerns` (termasuk `LimitedByAccess`) |
| `app/Services` | `AccessScope`, `AuditService`, `LoginService`, `UserAccountService`, `LocationService`, `DepartmentService`, `QrService`, `PurposeCategoryService`, `StaffMemberService`, `GuestQrResolver`, `GuestSessionService`, `GoogleTokenVerifier`, `ReturningGuestService`, `SignatureService`, `VisitService`, `VisitListService`, `HistoryService`, `ExportService`, `DashboardService` |
| `app/Http/Middleware` | `SecurityHeaders`, `EnsureUserIsActive` (`active`), `EnsureRole` (`role:`) |
| `config/sitamu.php` | Key aplikasi dari `.env` (PRD Bab 5.4) |
| `resources/views/layouts` | Layout staf, tamu, login, cetak, dan error berbasis Volt |
| `public/assets` | CSS identitas, JavaScript, logo, dan aset vendor (Volt, font Inter, Chart.js) |

Warna identitas instansi diatur di blok `:root` pada `public/assets/css/app.css`. Logo sementara ada di `public/assets/img/logo.png`.

## Status pengembangan

| Milestone | Status |
| --- | --- |
| M0 — Fondasi | Selesai |
| M1 — Autentikasi, Akun & AccessScope | Selesai |
| M2 — Master Data | Selesai |
| M3 — Halaman Tamu | Selesai |
| M4 — Resepsionis & PIC | Selesai |
| M5 — History & Ekspor | Selesai |
| M6 — Dashboard | Selesai |
| M7 — Penyempurnaan | Belum |
