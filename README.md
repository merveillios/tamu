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

## Instalasi server produksi

Prasyarat: PHP 8.1 dengan ekstensi standar Laravel ditambah `pdo_mysql`, `curl`, `gd`, `zip`, `xmlwriter`, `simplexml`; MySQL 8.0+ atau MariaDB 10.6+; Apache dengan `mod_rewrite` (berkas `public/.htaccess` sudah tersedia); HTTPS aktif.

1. Arahkan **document root** web server ke folder `public/`, bukan ke folder proyek.
2. Pasang dependensi tanpa paket pengembangan:

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. Salin `.env.example` menjadi `.env`, lalu atur minimal: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` (domain HTTPS resmi, dipakai untuk URL di QR), kredensial database, `SESSION_SECURE_COOKIE=true`, `GOOGLE_CLIENT_ID`, dan `TRUSTED_PROXIES` bila server berada di balik reverse proxy atau load balancer.
4. Jalankan:

   ```bash
   php artisan key:generate
   ```

   ```bash
   php artisan migrate --force
   ```

   ```bash
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```

5. Pastikan folder `storage/` dan `bootstrap/cache/` dapat ditulis oleh user web server. **Jangan** menjalankan `php artisan storage:link`; gambar tanda tangan disimpan di database dan hanya disajikan lewat route yang dibatasi peran.
6. Pastikan server dapat menghubungi Google untuk verifikasi email tamu:

   ```bash
   curl -sS -o /dev/null -w "%{http_code}\n" "https://oauth2.googleapis.com/tokeninfo?id_token=uji"
   ```

   Hasil `400` berarti server terhubung ke Google (token uji memang ditolak). Timeout atau `000` berarti akses keluar diblokir firewall.
7. Buat Super Admin pertama dengan `php artisan sitamu:create-superadmin`.

Setiap kali kode diperbarui, ulangi `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, lalu ketiga perintah cache di atas.

## Urutan pengisian data awal

Setelah instalasi, isi data sesuai urutan PRD Bab 8.1:

1. **Super Admin → Lokasi:** buat setiap lokasi atau gedung.
2. **Super Admin → Lokasi → Departemen:** buat departemen di tiap lokasi.
3. **Super Admin → Pengguna:** buat akun Admin (pilih lokasinya), akun Resepsionis per pos jaga, dan satu akun PIC per departemen.
4. **Super Admin → Lokasi → QR:** buat QR untuk tiap titik masuk dan pilih akun resepsionisnya, lalu cetak dan tempel.
5. **Admin atau Super Admin → Kategori Keperluan:** isi kategori tiap lokasi beserta saran departemennya. Halaman tamu baru bisa dibuka setelah lokasi punya minimal satu kategori aktif.
6. **Admin atau Super Admin → Petugas:** isi orang yang dapat ditemui di tiap departemen. Departemen baru muncul di pilihan penugasan bila memiliki akun PIC aktif dan minimal satu petugas aktif.

## Backup dan pemulihan

Seluruh data, termasuk gambar tanda tangan, ada di database. Lakukan backup harian dan simpan salinannya di luar server aplikasi:

```bash
mysqldump --single-transaction --routines --default-character-set=utf8mb4 -u USER -p sitamu > sitamu_$(date +%Y%m%d).sql
```

Pemulihan ke database kosong:

```bash
mysql --default-character-set=utf8mb4 -u USER -p sitamu < sitamu_YYYYMMDD.sql
```

Simpan juga salinan `.env` (terutama `APP_KEY`) di tempat aman yang terpisah. Ukuran database tumbuh sekitar 5–30 KB per kunjungan karena gambar tanda tangan.

## Pemulihan darurat akun Super Admin

Bila semua akun Super Admin lupa password, terkunci, atau nonaktif, buat Super Admin baru langsung dari server:

```bash
php artisan sitamu:create-superadmin
```

Setelah login dengan akun baru, buka **Pengguna** untuk membuka kunci, mengaktifkan kembali, atau mereset password akun lama, lalu nonaktifkan akun darurat bila tidak diperlukan lagi. Semua langkah ini tercatat di Audit Log.

## Kunci dan pengaturan yang dibutuhkan

Semua pengaturan ada di `.env` (contoh lengkap di `.env.example`). File `.env` **tidak boleh** di-commit atau dibagikan.

| Key di `.env` | Wajib | Cara mendapatkan / isi |
| --- | --- | --- |
| `APP_KEY` | Ya | Dibuat otomatis dengan `php artisan key:generate`. Rahasia; simpan cadangannya. |
| `APP_URL` | Ya | Alamat aplikasi yang dibuka pengguna, tanpa garis miring di akhir. Lokal: `http://localhost:8000`. Produksi: domain HTTPS resmi. **QR dibuat dari nilai ini.** |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Ya | Kredensial MySQL/MariaDB. Laragon bawaan: host `127.0.0.1`, port `3306`, user `root`, password kosong. |
| `GOOGLE_CLIENT_ID` | Ya, untuk halaman tamu | OAuth Client ID dari Google Cloud (lihat langkah di bawah), bentuknya `angka-acak.apps.googleusercontent.com`. Nilai ini publik, bukan rahasia. |
| `TRUSTED_PROXIES` | Hanya di balik proxy/tunnel | Kosong bila tidak memakai proxy. `*` saat tes lewat tunnel. Di produksi: IP/CIDR proxy, misalnya `10.0.0.5`. |
| `APP_ENV`, `APP_DEBUG`, `SESSION_SECURE_COOKIE` | Ya | Lokal: `local`, `true`, `false`. Produksi: `production`, `false`, `true`. |
| `INSTITUTION_NAME` | Tidak | Nama instansi yang tampil di halaman dan ekspor. |

**Yang tidak dibutuhkan:**
- **API key Google** (`AIza…`): tidak dipakai sama sekali. Jangan dimasukkan ke `.env`.
- **Client secret Google** (`GOCSPX-…`): tidak dipakai. Login tamu hanya butuh Client ID; server memverifikasi token lewat endpoint publik Google.
- Akun/kunci pihak ketiga lain (email, WhatsApp, SMS): tidak ada.

Bila API key atau client secret pernah terlihat orang lain, hapus API key-nya dan klik **Reset secret** pada Client ID. Aplikasi tetap berjalan karena tidak memakai keduanya.

### Membuat Google Client ID

1. Buka [Google Cloud Console → Google Auth Platform](https://console.cloud.google.com/auth/clients) dan pilih atau buat project milik instansi.
2. Bila diminta, isi **Branding** (nama aplikasi "SiTamu" dan email kontak). Tidak perlu menambah *scope* apa pun; aplikasi hanya membaca email.
3. Buka **Clients → Create client**, pilih jenis **Web application**.
4. Di **Authorized JavaScript origins**, tambahkan setiap alamat yang dipakai, persis tanpa garis miring dan tanpa path:
   - `http://localhost:8000` dan `http://localhost` untuk pengembangan lokal
   - alamat tunnel saat tes dari ponsel, misalnya `https://abc-123.trycloudflare.com`
   - domain produksi, misalnya `https://sitamu.contoh.go.id`

   **Authorized redirect URIs** biarkan kosong.
5. Klik **Create**, salin **Client ID** ke `GOOGLE_CLIENT_ID` di `.env`, lalu jalankan `php artisan config:clear`. Client secret yang muncul tidak perlu disimpan.
6. Buka **Audience**:
   - selama tahap uji, tambahkan email Gmail penguji di **Test users** (maks. 100 email), atau
   - untuk dipakai tamu umum, klik **Publish app** agar statusnya *In production*. Karena hanya meminta email, aplikasi tidak perlu verifikasi Google.
7. Tunggu beberapa menit (kadang sampai sekitar 1 jam) sampai perubahan di Google berlaku.

Pesan error yang sering muncul:
- **"tidak mematuhi kebijakan OAuth 2.0 … daftarkan asal JavaScript"**: alamat di address bar belum ada di Authorized JavaScript origins, atau perubahannya belum berlaku.
- **"Akses diblokir" / "access blocked"**: aplikasi masih *Testing* dan email yang dipakai belum ada di Test users.
- **Tombol Google tidak muncul**: `GOOGLE_CLIENT_ID` kosong atau salah, atau halaman dibuka lewat alamat yang tidak terdaftar (misalnya `127.0.0.1` padahal yang terdaftar `localhost`).

Untuk mencoba halaman tamu secara lokal, buka menu **Lokasi → QR → Cetak** sebagai Super Admin; isi QR mengikuti `APP_URL`.

## Mencoba halaman tamu dari ponsel

Ponsel tidak bisa membuka `localhost` milik laptop, dan Google hanya mengizinkan tombol login di alamat `https://` atau `localhost`. Karena itu, uji dari ponsel memakai *tunnel* HTTPS sementara. Tidak perlu deploy. Contoh di bawah memakai Cloudflare Tunnel (gratis, tanpa akun):

1. Pastikan MySQL menyala. Di Laragon, klik **Start All**. Bila MySQL mati, halaman menampilkan error `SQLSTATE[HY000] [2002] No connection`.
2. Jalankan aplikasi di laptop:

   ```bash
   php artisan serve --host=127.0.0.1 --port=8000
   ```

3. Di terminal lain, buka tunnel lalu catat alamat `https://….trycloudflare.com` yang muncul. Bila `cloudflared` belum terpasang, pasang dulu dengan `winget install Cloudflare.cloudflared`, lalu buka terminal baru.

   ```bash
   cloudflared tunnel --url http://127.0.0.1:8000
   ```

4. Di `.env`, ubah `APP_URL` menjadi alamat tunnel tersebut dan isi `TRUSTED_PROXIES=*`, agar Laravel tahu permintaan aslinya datang lewat HTTPS. Setelah itu muat ulang konfigurasi:

   ```bash
   php artisan config:clear
   ```

5. Tambahkan alamat tunnel ke **Authorized JavaScript origins** pada Client ID di Google Cloud Console (lihat langkah 4 di atas), lalu tunggu beberapa menit.
6. Buka **Lokasi → QR → Cetak** di laptop. Gambar QR dibuat ulang dari `APP_URL` setiap kali dibuka, jadi tidak perlu membuat QR baru. Pindai dengan kamera ponsel.
7. Selesai menguji, hentikan tunnel (`Ctrl+C`), kembalikan `APP_URL=http://localhost:8000` dan kosongkan `TRUSTED_PROXIES`, lalu jalankan lagi `php artisan config:clear`.

Catatan: alamat *quick tunnel* berubah setiap kali dijalankan, sehingga langkah 4–5 perlu diulang tiap kali tes. Selama tunnel aktif, aplikasi lokal bisa dibuka siapa pun yang tahu alamatnya, jadi jangan biarkan tunnel menyala tanpa perlu. Untuk dipakai tamu sungguhan, aplikasi tetap harus dipasang di server dengan domain resmi (lihat **Instalasi server produksi**).

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

`ConcurrencyTest` menjalankan beberapa proses PHP paralel (`tests/Support/concurrent-worker.php`) untuk membuktikan nomor urut tidak bentrok dan satu penugasan tidak bisa diterima dua kali. Tes ini menyimpan data sungguhan di database tes lalu mengosongkannya, jadi pastikan `.env.testing` **selalu** menunjuk database khusus tes (`sitamu_test`), bukan database utama.

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
| M7 — Penyempurnaan | Selesai |
