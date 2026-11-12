# Catatan Keputusan SiTamu

Format mengikuti aturan A-8: tanggal, konteks, keputusan, alasan.

## 2026-11-12 — Kelompok rentan menjadi master data (revisi HRD #12)

- **Konteks:** Pilihan kelompok rentan ditulis di enum `VulnerableGroup`, dan urutan prioritas antrean (Lansia, Disabilitas, Ibu hamil) ditulis langsung di `VisitListService`. HRD meminta halaman tambah, ubah, dan hapus untuk Admin.
- **Keputusan:**
  - Tabel baru `vulnerable_groups` (`code`, `name`, `priority`, `sort_order`, `is_active`) diisi migrasi dengan lima pilihan lama memakai kode yang sama. `guest_visits.vulnerable_group` tetap menyimpan kode, jadi tidak ada migrasi data kunjungan dan tidak ada foreign key. Kode dibuat dari nama saat kelompok ditambah dan tidak bisa diubah. Enum `VulnerableGroup` dihapus; model `App\Models\VulnerableGroup` menggantikannya.
  - Daftarnya satu untuk semua lokasi dan dikelola Admin serta Super Admin, sesuai cakupan Admin yang sudah tidak dibatasi lokasi (ADM-5). Menu baru "Kelompok Rentan".
  - Kolom `priority` (1–99, kosong = tidak didahulukan) menggantikan urutan yang ditulis di kode. Tamu Aktif dan Penugasan mengurutkan berdasarkan prioritas lalu jam datang. Bawaan: Lansia 1, Penyandang disabilitas 2, Ibu hamil 3.
  - "Hapus" mengikuti pola lokasi (LOK-3): hanya untuk kelompok yang belum pernah dipakai kunjungan; yang sudah dipakai hanya bisa dinonaktifkan. Kelompok aktif terakhir tidak bisa dinonaktifkan atau dihapus karena kelompok rentan wajib di formulir tamu.
  - Kelompok nonaktif disembunyikan dari formulir tamu baru dan ditolak server. Tamu lama yang data dirinya terkunci (TAM-16) tetap boleh memakai kelompok nonaktif miliknya, karena ia tidak bisa mengubah pilihan itu.
- **Alasan:** Kode lama dipertahankan agar data lama, tes, dan ekspor tidak berubah. Karena kunjungan menyimpan kode, nama baru kelompok langsung terlihat juga di History dan ekspor kunjungan lama.

## 2026-11-12 — Ekspor hanya PDF, tanpa batas baris (revisi HRD #13)

- **Konteks:** HRD meminta semua ekspor hanya tersedia dalam format PDF. Sebelumnya History menawarkan CSV, Excel, dan PDF, dengan Excel/PDF dibatasi 500 baris (`EXPORT_RICH_MAX_ROWS`). Pemilik proyek memutuskan jumlah baris mengikuti filter.
- **Keputusan:**
  - Tombol Ekspor di History menjadi satu tombol "Ekspor PDF". Kode CSV dan Excel dihapus dari `ExportService`; parameter `format` hanya menerima `pdf` sehingga tautan lama `?format=csv`/`xlsx` ditolak dengan pesan "Ekspor hanya tersedia dalam format PDF." dan tidak tercatat sebagai ekspor.
  - Batas baris dihapus. Karena Dompdf menyusun seluruh tabel di memori, batas memori dan waktu PHP dinaikkan khusus saat membuat PDF lewat `EXPORT_PDF_MEMORY_LIMIT` (bawaan 1024M) dan `EXPORT_PDF_TIME_LIMIT` (bawaan 300 detik). `CSV_DELIMITER` dan `EXPORT_RICH_MAX_ROWS` tidak dipakai lagi.
  - Paket `phpoffice/phpspreadsheet` tidak lagi dipakai kode, tetapi belum dihapus dari `composer.json` karena penghapusan perlu `composer remove` di lingkungan yang bisa mengunduh paket.
  - Bug yang membuat tanda tangan tidak muncul di PDF (revisi HRD #2) ikut diperbaiki: gambar dikumpulkan per batch dengan `flatMap()`, yang mengurutkan ulang kunci `visit_id`, sehingga pencarian tanda tangan per kunjungan selalu kosong. Kini batch digabung dengan `union()`. Tes ekspor memeriksa gambar benar-benar ada di PDF.
- **Alasan:** Satu format yang memuat tanda tangan dan rapi untuk dicetak, sesuai permintaan HRD; ekspor resepsionis (T7) akan memakai jalur PDF yang sama.

## 2026-11-12 — PIC langsung menyelesaikan tanpa Terima (PIC-2, PIC-3)

- **Konteks:** PIC harus menekan Terima lalu Selesai, padahal yang dibutuhkan hanya membaca rincian keperluan dan menulis hasil.
- **Keputusan:**
  - Tombol, route, dan service Terima dihapus. Tombol Selesai tersedia pada setiap kunjungan aktif di Bidang/UPT PIC dan membuka modal berisi Rincian keperluan dan isian Hasil; simpan berarti `waiting` → `completed`. `processed_at` dan `processed_by` diisi saat selesai bila masih kosong.
  - Status `processing` tidak lagi dibuat oleh PIC; tetap didukung untuk kunjungan lama dan buka kembali kunjungan yang dulu selesai dari `processing`.
  - Penugasan ulang tetap lewat resepsionis seperti semula, dan kini berlaku sampai kunjungan diselesaikan PIC. Tidak ada tombol Kembalikan maupun status "ditolak".
- **Alasan:** Alur PIC lebih ringkas tanpa mengubah skema database maupun alur penugasan resepsionis.

## 2026-11-12 — Kategori keperluan dihapus, tamu hanya mengisi Rincian keperluan (revisi HRD #6 dan #7)

- **Konteks:** "Pertanyaan departemen" yang diminta HRD dihapus ternyata adalah kategori keperluan (menu Pertanyaan) yang dikelompokkan per Bidang/UPT di formulir tamu, sehingga revisi #6 dan #7 dikerjakan bersama.
- **Keputusan:**
  - Formulir tamu tidak lagi memilih kategori; Rincian keperluan selalu tampil, wajib, dan menjadi field pertama. Input kategori dari browser diabaikan.
  - Menu, route, controller, request, service, dan halaman kategori keperluan dihapus. Model, tabel `purpose_categories`, dan kolom kategori di `guest_visits` (kini nullable lewat migrasi) dibiarkan agar kunjungan lama tetap utuh; Detail Kunjungan menampilkannya sebagai "Kategori (lama)".
  - Kunjungan baru tidak punya saran Bidang/UPT. Resepsionis memilih Bidang/UPT dan orang yang ditemui sendiri; modal Tugaskan menampilkan Rincian keperluan lengkap. Ini menjawab pertanyaan HRD nomor 8.
  - Tamu Aktif, Penugasan PIC, Dashboard Admin, History, dan riwayat tamu menampilkan Rincian keperluan (dipotong 80 karakter di tabel, teks lengkap lewat tooltip dan Detail Kunjungan). Modal Selesai PIC menampilkan Rincian keperluan lengkap di atas isian Hasil.
  - Filter kategori di History dan kolom Kategori Keperluan di ekspor dihapus.
  - Syarat QR dapat dipakai tamu (TAM-6) diganti dari "minimal satu kategori keperluan aktif" menjadi "minimal satu Bidang/UPT aktif", karena tanpa Bidang/UPT kunjungan tidak dapat ditugaskan.
- **Alasan:** Tamu sering tidak tahu kategori yang tepat; rincian bebas lebih jelas bagi resepsionis yang menentukan tujuan.

## 2026-11-12 — Nomor urut tidak ditampilkan (revisi HRD #5)

- **Konteks:** HRD meminta nomor urut dihapus dari semua tampilan. Pertanyaan apakah kolomnya boleh dihapus dari database belum dijawab.
- **Keputusan:**
  - Nomor urut tidak lagi tampil di halaman terima kasih tamu, Tamu Aktif, Penugasan PIC, Dashboard Admin, History, Detail Kunjungan, dialog Tugaskan/Batal/Selesai, maupun notifikasi; kunjungan dikenali dari nama tamu. Halaman terima kasih menampilkan "Kunjungan terkirim".
  - Kolom pertama ekspor menjadi "No." berisi nomor baris 1, 2, 3 di file tersebut.
  - Kolom `daily_number`, tabel `visit_counters`, pengambilan nomor atomik, dan `ConcurrencyTest` tetap ada, sehingga keputusan menghapus total bisa diambil terpisah tanpa migrasi sekarang.
- **Alasan:** Menyembunyikan dulu tidak berisiko bagi data lama; menghapus kolom dan generator adalah langkah yang tidak bisa dibatalkan.

## 2026-11-12 — Istilah "departemen" menjadi "Bidang/UPT" (revisi HRD #1)

- **Konteks:** Struktur organisasi Disperindag Jatim memakai istilah Bidang dan UPT, bukan departemen.
- **Keputusan:** Semua teks yang terlihat pengguna (menu, judul, label, tombol, pesan validasi, notifikasi, header ekspor) serta README dan PRD memakai satu penulisan: "Bidang/UPT". Nama tabel, model, class, route, URL (`/departemen`), parameter query (`departemen`), kode aksi audit log (`department.*`), dan komentar kode tidak diubah.
- **Alasan:** Mengganti nama teknis berisiko memutus data, tes, dan tautan lama tanpa manfaat bagi pengguna.

## 2026-11-12 — Resepsionis boleh melihat tanda tangan (TAM-9)

- **Konteks:** Commit `3ddb218` membuka route tanda tangan dan gambar di detail kunjungan untuk Resepsionis, sedangkan PRD (matriks peran, TAM-9, Bab 13) masih menyebut hanya Admin dan Super Admin. Audit keamanan 2026-10-29 mencatat ketidaksesuaian ini.
- **Keputusan:** PRD disesuaikan dengan kode. Resepsionis boleh melihat tanda tangan kunjungan dari QR miliknya; Admin dan Super Admin di semua lokasi; PIC tetap tidak. Aturan yang sama nantinya berlaku untuk foto KTP dan selfie tamu.
- **Alasan:** Resepsionis yang memvalidasi tamu di tempat, sehingga perlu mencocokkan tanda tangan (dan nanti KTP serta selfie) dengan orang yang datang.
- **Catatan:** Cakupan Resepsionis mengikuti kepemilikan QR saat ini. Setelah QR dipindahkan (QR-6), resepsionis baru juga dapat melihat tanda tangan kunjungan lama dari QR tersebut.

## 2026-11-05 — QR tidak lagi memilih departemen (QR-2)

- **Konteks:** Form buat QR mewajibkan satu departemen, padahal departemen QR hanya dipakai sebagai cadangan saran departemen bila kategori keperluan tidak punya saran. Sejak kategori wajib punya saran departemen, cadangan itu hampir tidak pernah terpakai, sementara admin bisa salah mengira QR hanya untuk satu departemen.
- **Keputusan:**
  - Form buat QR (Admin dan Super Admin) hanya berisi lokasi, label opsional, dan akun resepsionis, sesuai teks QR-2. Fitur ganti departemen QR dan audit `qr.change_department` dihapus.
  - Saran departemen kunjungan hanya berasal dari kategori keperluan; departemen QR tidak lagi menjadi cadangan.
  - Form menampilkan peringatan, bukan penolakan, bila lokasi terpilih belum punya departemen aktif atau kategori keperluan aktif, karena QR-nya akan tampil "tidak aktif" bagi tamu (TAM-6). Pesan sukses setelah membuat QR juga memuat catatan ini. QR tetap boleh dibuat agar bisa dicetak lebih dulu.
  - Kolom `qr_codes.department_id` tidak dihapus agar data QR lama tetap utuh; kolom itu tidak lagi dibaca atau diisi aplikasi.
  - Daftar QR menampilkan "Belum siap" beserta alasannya untuk QR aktif yang ditolak halaman tamu (lokasi nonaktif atau tanpa kategori keperluan aktif). Syaratnya diambil dari `GuestQrResolver::blockers()`, sumber yang sama dengan halaman tamu. Status di database tidak diubah otomatis, sehingga QR yang sengaja dinonaktifkan admin tetap dapat dibedakan dan QR langsung tampil "Aktif" begitu kategori ditambahkan.
  - Kolom Departemen (jumlah departemen di lokasi) dihapus dari daftar QR karena QR tidak lagi terhubung ke departemen.
- **Alasan:** Mengurangi langkah dan kebingungan saat membuat QR tanpa mengubah alur penugasan, karena departemen tujuan tetap dipilih resepsionis (RSP-2).

## 2026-10-26 — Admin mengelola semua lokasi (PRD v0.11)

- **Konteks:** Klien meminta Super Admin sebisa mungkin tidak dipakai kecuali darurat, sehingga pekerjaan harian Super Admin (lokasi, departemen, QR, dan akun) dipindah ke Admin. Admin juga tidak lagi dibatasi lokasi.
- **Keputusan:**
  - `AccessScope` memperlakukan Admin tanpa batas lokasi, sama seperti Super Admin (ADM-5). Membuka kembali kunjungan tetap hanya untuk Admin (ADM-3).
  - Admin mengelola lokasi, departemen, dan QR Code di semua lokasi dengan aturan yang sama seperti Super Admin.
  - Admin hanya mengelola akun Resepsionis dan PIC (USR-6). Akun Admin dan Super Admin tetap hanya dikelola Super Admin, dan Admin tidak dapat mengubah akunnya sendiri lewat menu Pengguna.
  - Admin dapat melihat audit log (AUD-2) agar ada yang membaca catatan aksi walau Super Admin jarang login.
  - Penugasan lokasi Admin (USR-2) dihapus dari aplikasi. Tabel `user_locations` dan isinya **tidak** dihapus agar bisa dipakai lagi bila klien kembali meminta pembatasan per lokasi; tidak ada migrasi yang menghapus data.
- **Alasan:** Tanpa USR-6, Admin dapat membuat akun Super Admin, me-reset password Super Admin atau Admin lain lalu login atas nama mereka. Itu sama dengan mengambil alih sistem dan membuat audit log tidak dapat dipercaya. Membuat atau mengganti akun Admin jarang terjadi, sehingga tetap sesuai dengan keinginan klien.
- **Risiko yang harus diketahui klien:** kesalahan satu Admin (misalnya menonaktifkan lokasi atau me-regenerate QR yang sudah dicetak) berdampak ke semua lokasi; pengamannya adalah dialog konfirmasi dan audit log. Password akun Super Admin harus kuat dan disimpan pejabat yang bertanggung jawab.

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
- **Keputusan:** Keunikan dijamin oleh `INSERT ... ON DUPLICATE KEY UPDATE ... LAST_INSERT_ID()` di MySQL dan unique key (`location_id`, `visit_date`, `daily_number`). Tes urutan nomor per lokasi dan per hari ada di `GuestFormTest`.
- **Pembaruan 2026-10-19:** Kiriman yang benar-benar paralel kini dites otomatis di `ConcurrencyTest`. Tes ini menjalankan beberapa proses PHP (`tests/Support/concurrent-worker.php`) dengan koneksi MySQL masing-masing, yang mulai pada waktu yang sama: 10 kiriman serentak harus mendapat nomor 1–10 tanpa celah, dan dua klik Terima serentak hanya boleh satu yang berhasil. Tes ini terbukti gagal bila penghitung diganti `max() + 1`. Karena proses anak tidak dapat melihat data di dalam transaksi tes, kelas ini menyimpan data sungguhan lalu mengosongkan tabel setelah tiap tes.
- **Alasan:** Satu proses PHPUnit menjalankan request berurutan sehingga tidak dapat membuktikan keamanan terhadap koneksi paralel.

## 2026-10-19 — Pertanyaan dikelompokkan per departemen (mengubah KAT-1)

- **Konteks:** KAT-1 menampilkan kategori keperluan sebagai daftar polos dan saran departemen tidak ditampilkan kepada tamu. Pemilik proyek meminta dropdown formulir tamu berisi nama departemen, dengan daftar pertanyaan yang disiapkan admin di bawahnya.
- **Keputusan:** Tabel dan aturan kategori tetap; satu kategori = satu pertanyaan, dan saran departemennya menjadi grup. Formulir tamu memakai `<optgroup>` berjudul nama departemen; grup urut nama departemen, pertanyaan di dalam grup urut `sort_order` lalu nama. Pertanyaan tanpa departemen, atau yang departemennya nonaktif, masuk grup "Umum" di paling bawah. Bila tidak ada satu pun pertanyaan yang punya departemen, dropdown tetap polos. Judul grup tidak dapat dipilih; tamu tetap memilih pertanyaan, sehingga saran departemen ke resepsionis (RSP) dan isian `purpose_category_name` tidak berubah. Halaman admin memakai label "Pertanyaan" dan urutan yang sama dengan formulir tamu; URL `/admin/kategori-keperluan` tidak diubah. Panjang nama dinaikkan dari 100 menjadi 150 karakter (validasi Bab 11.3, `purpose_categories.name`, dan `guest_visits.purpose_category_name`) lewat migrasi `ALTER TABLE` karena proyek tidak memakai `doctrine/dbal`.
- **Alasan:** Memakai kolom saran departemen yang sudah ada menghindari tabel baru, dan data kunjungan lama tetap utuh. `<optgroup>` adalah dropdown bawaan browser, sehingga di ponsel tampil sebagai daftar berjudul tanpa JavaScript tambahan.
- **Halaman admin (lanjutan):** Atas permintaan pemilik proyek, halaman Pertanyaan menampilkan tabel departemen lebih dulu (semua departemen aktif, termasuk yang belum punya pertanyaan, lalu "Umum") dengan tombol "Lihat pertanyaan" yang membuka tabel pertanyaan departemen itu. Tombol tambah di tiap departemen mengisi departemen di form, dan kolom departemen ditaruh paling atas. Parameter `departemen` (ID atau `umum`) menentukan departemen yang ditampilkan, sehingga setelah simpan atau ubah status admin kembali ke departemen yang sama. Formulir tamu tetap satu dropdown berkelompok. Departemen kemudian dijadikan wajib untuk pertanyaan baru dan yang diubah: dari halaman departemen, form menampilkan departemen terkunci (teks baca-saja dan input tersembunyi); dropdown hanya ada di form Ubah untuk memindahkan pertanyaan. Baris Umum di daftar departemen hanya muncul bila masih ada pertanyaan lama tanpa departemen aktif, tanpa tombol tambah.

## 2026-10-19 — Tanda tangan cukup sekali (mengubah TAM-9)

- **Konteks:** TAM-9 dan Bab 6.6.1 meminta tanda tangan digambar setiap kunjungan. Pemilik proyek meminta tamu yang sudah pernah tanda tangan tidak perlu tanda tangan lagi.
- **Keputusan:** Formulir hanya menampilkan kotak tanda tangan bila email terverifikasi belum punya tanda tangan di kunjungan mana pun. Untuk tamu yang sudah punya, server menyalin gambar tanda tangan terbaru email itu ke `visit_signatures` kunjungan baru, dan input `signature` dari browser diabaikan (aturan `exclude`). Tamu lama yang belum punya tanda tangan tetap diminta sekali. Persetujuan pemrosesan data (TAM-7) tetap dicentang setiap kunjungan.
- **Alasan:** Menyalin gambar menjaga relasi satu tanda tangan per kunjungan (Bab 9.2), sehingga detail kunjungan dan ekspor Excel/PDF tetap menampilkan tanda tangan di setiap baris tanpa perubahan lain.

## 2026-10-18 — Kode respons aksi kunjungan (M4)

- **Konteks:** Bab 7.1 meminta transisi tidak sah ditolak 422, sedangkan Bab 7.2 meminta 409 bila status sudah diubah petugas lain.
- **Keputusan:** Aksi pada kunjungan yang sudah ditutup (Selesai/Dibatalkan), misalnya penugasan, mendapat 422 `INVALID_TRANSITION`. Aksi pada kunjungan aktif yang statusnya sudah berbeda dari yang diharapkan (misalnya Selesai saat masih Menunggu, Terima kedua kali, atau Batal yang kalah balapan) mendapat 409 `STATUS_CONFLICT`, karena biasanya berasal dari daftar yang belum diperbarui. Batal pada kunjungan yang sudah ditutup juga 409 karena skenario umumnya adalah dua petugas menekan tombol bersamaan.
- **Alasan:** Antarmuka memuat ulang daftar pada 409 sehingga petugas langsung melihat status terbaru.

## 2026-10-18 — Daftar Tamu Aktif dan Penugasan

- **Konteks:** RSP-5 dan RSP-7 meminta aksi tanpa reload dan pembaruan otomatis.
- **Keputusan:** Baris daftar dirender di browser dari JSON polling (`/admin/tamu/data`, `/admin/penugasan/data`) memakai `textContent`, tanpa HTML dari server, sehingga tampilan awal dan hasil polling selalu sama. Bunyi notifikasi PIC (opsional pada PIC-4) belum dibuat.
- **Alasan:** Satu jalur render lebih sederhana dan aman terhadap XSS (A-7).

## 2026-10-18 — Tampilan ponsel (M7)

- **Konteks:** Bab 12.5 mewajibkan tabel Tamu Aktif dan Penugasan berubah menjadi kartu di layar < 768 px, dan semua halaman nyaman di layar 360 px.
- **Keputusan:** Tamu Aktif, Penugasan, History, detail kunjungan, dan dashboard memakai kelas `table-cards` (baris menjadi kartu berlabel). Tabel master data yang jarang dibuka dari ponsel (Pengguna, Lokasi, Departemen, QR, Kategori, Petugas, Audit Log) tetap berupa tabel dengan gulir horizontal di dalam kartu.
- **Alasan:** Halaman operasional harian dipakai dari ponsel; halaman pengaturan umumnya dibuka dari komputer.

## 2026-10-18 — Lingkungan tes

- **Konteks:** A-15 mewajibkan tes memakai MySQL/MariaDB terpisah.
- **Keputusan:** Database `sitamu_test` lewat `.env.testing` (contoh: `.env.testing.example`). `BCRYPT_ROUNDS=4` hanya di lingkungan tes agar tes cepat; produksi tetap minimal 12.
- **Alasan:** Tes nomor urut (Bab 9.3) bergantung pada perilaku MySQL.
