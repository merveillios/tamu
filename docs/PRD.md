# PRD SiTamu v0.11 — Laravel (Model 5 Peran)

Oct 14, 2026 · @ahmad

SiTamu v0.10 mengubah buku tamu menjadi alur layanan lima peran: tamu mengisi formulir, resepsionis menugaskan ke departemen, PIC melayani dan menulis hasil, lalu hasil kembali ke riwayat tamu.

**STATUS: DRAFT v0.11 — disiapkan untuk implementasi oleh AI coding agent (framework Laravel)**

|  |  |
| --- | --- |
| **Nama Produk** | SiTamu (Sistem Informasi Buku Tamu) |
| **Klien** | Dinas Perindustrian dan Perdagangan Provinsi Jawa Timur (Disperindag) |
| **Versi Dokumen** | v0.11 |
| **Disusun oleh** | TBD (Pengembang) |
| **Tanggal** | 14 Oktober 2026 |
| **Dokumen Terkait** | PRD v0.9 (6 Okt 2026), dokumen Use Case & User Flow SiTamu (revisi 13 Okt 2026), hasil pembahasan alur 14 Okt 2026, prototipe HTML antarmuka. Lihat aturan A-9. |

## Riwayat Perubahan

| Versi | Tanggal | Perubahan |
| --- | --- | --- |
| v0.11 | 26 Okt 2026 | Pencarian pada pilihan departemen dan orang yang ditemui di modal Tugaskan (RSP-9). Atas permintaan klien, **Admin menjadi pengelola harian untuk semua lokasi** dan Super Admin dipakai hanya untuk keadaan darurat. Admin tidak lagi dibatasi lokasi (ADM-5): pemantauan, History, ekspor, dan dashboard mencakup semua lokasi. Admin kini mengelola lokasi (LOK-1–3), departemen (DEP-1–3), QR Code (QR-2–7), serta akun Resepsionis dan PIC (USR-1, USR-6, AUTH-4), dan dapat melihat audit log (AUD-2). Akun Admin dan Super Admin tetap hanya dikelola Super Admin (USR-6). **Dihapus**: penugasan lokasi Admin (USR-2); tabel `user_locations` dibiarkan tanpa dipakai. |
| v0.10 | 14 Okt 2026 | **Model peran diubah menjadi 5 peran**: Super Admin, Admin, Resepsionis, PIC, dan Tamu (Bab 3). Ditambah: penugasan kunjungan ke departemen oleh resepsionis (RSP-1–8), penerimaan, penulisan hasil, dan penyelesaian oleh PIC (PIC-1–6), riwayat kunjungan untuk tamu (TAM-17), deteksi tamu lama dan pengisian otomatis data diri (TAM-15, TAM-16), kategori keperluan dengan saran departemen (KAT-1–3), kelompok rentan, master departemen (DEP-1–3), dan daftar petugas departemen (PTG-1–2). Satu QR terhubung ke tepat satu akun resepsionis (QR-6). Akun resepsionis dan PIC adalah akun bersama yang dipakai bergantian. QR hanya dikelola Super Admin. Buka kembali kunjungan dipindah dari Super Admin ke Admin (ADM-3). Instansi/perusahaan menjadi wajib. Orang yang ditemui tidak lagi diisi tamu; nilainya dipilih resepsionis saat menugaskan; PIC adalah satu akun bersama per departemen yang mereview tamu. Ganti password kini mengakhiri semua sesi akun (USR-3). Label Diproses diganti "Sedang direview" (PIC mereview keperluan tamu yang diarahkan resepsionis); History dan ekspor dapat memuat kunjungan Sedang direview. Admin mengelola kategori keperluan dan daftar petugas untuk semua lokasi. Desain antarmuka memakai template Volt Bootstrap 5 Dashboard (Bab 5.6). **Dihapus**: QR Darurat (VIS-11, TAM-13, tabel `emergency_passes`, kolom `email_source` dan `email_authorized_by`), status Draf, dan penolakan penugasan oleh PIC. `LocationScope` diganti `AccessScope` (Bab 12.2). Skema, route, validasi, ekspor, UI, milestone, dan keputusan tertunda disusun ulang. |
| v0.9 | 6 Okt 2026 | Implementasi dipindah dari PHP native ke framework Laravel: migrasi, Eloquent, Form Request, `RateLimiter`, guard `web`, `Http` facade, perintah artisan, aturan A-14 dan A-15. Tidak ada perubahan kebutuhan fungsional. |
| v0.8 | 5 Okt 2026 | Ekspor History tiga format (CSV, Excel, PDF); Excel dan PDF memuat gambar tanda tangan. |
| v0.7 | 5 Okt 2026 | Aturan A-13 (standar kerapian kode). |
| v0.6 | 5 Okt 2026 | Nama produk SiTamu, TAM-14 (anti double-submit), AUTH-4 (buka kunci akun), paginasi, rate limit login di `.env`. |
| v0.5 | 1 Okt 2026 | Aturan commit Git bertahap berbahasa Indonesia (A-11, A-12, Bab 0.5). |
| v0.4 | 1 Okt 2026 | Verifikasi email lewat akun Google (TAM-11), halaman bantuan (TAM-12), QR Darurat. |
| v0.3 | 1 Okt 2026 | Nomor urut harian, alamat, tanda tangan digital. |
| v0.2 | 29 Sep 2026 | Panduan agent, matriks hak akses, skema database, route, validasi, milestone. |
| v0.1 | 29 Sep 2026 | Draft awal. |

# 0. Panduan untuk AI Agent (Baca Sebelum Mulai)

## 0.1 Cara membaca dokumen ini

- **WAJIB / HARUS** berarti tidak boleh dilewati. **JANGAN** berarti dilarang. **BOLEH** berarti opsional.
- Setiap kebutuhan memiliki ID (misalnya `TAM-3`, `RSP-2`). Sebutkan ID tersebut di pesan commit dan di komentar kode yang relevan.
- Label **\[DEFAULT\]** menandai keputusan sementara pengembang karena klien belum memutuskan (daftar lengkap di Bab 17). Implementasikan persis seperti tertulis; jika nilainya berupa angka atau teks, simpan di `.env` atau `config/` agar mudah diubah tanpa mengubah logika.
- Label **\[BARU v0.10\]** menandai kebutuhan yang tidak ada di v0.9. **\[DIUBAH v0.10\]** menandai ketentuan v0.9 yang diubah. **\[DIHAPUS v0.10\]** menandai ketentuan v0.9 yang tidak berlaku lagi dan **JANGAN** diimplementasikan.

## 0.2 Urutan prioritas jika ada konflik

1. Keamanan dan hak akses (Bab 6.12, Bab 12).
2. Integritas data dan aturan status kunjungan (Bab 7).
3. Kebutuhan fungsional (Bab 6).
4. Tampilan dan kenyamanan UI (Bab 13).

## 0.3 Aturan kerja agent

| ID | Aturan |
| --- | --- |
| A-1 | Kerjakan per milestone sesuai Bab 15. Setelah satu milestone selesai, **berhenti** dan laporkan: file yang dibuat/diubah, cara menjalankan, ID kebutuhan yang terpenuhi, hasil `php artisan test` dan `vendor/bin/pint --test`, serta hal yang belum selesai. Jangan lanjut ke milestone berikutnya sebelum diminta. |
| A-2 | **JANGAN** mengimplementasikan fitur di Bab 16 (Fase Lanjutan) atau apa pun yang tercantum di luar lingkup (Bab 4.2), termasuk semua ketentuan bertanda \[DIHAPUS v0.10\]. |
| A-3 | **JANGAN** menambah dependency Composer selain yang tercantum di Bab 5.1 (beserta turunan otomatisnya). Dilarang memasang starter kit, paket autentikasi/otorisasi, wrapper PDF/Excel, dan paket komunitas lain (misalnya Breeze, Jetstream, Fortify, Sanctum, Livewire, Inertia, Telescope, Debugbar, `spatie/*`, `maatwebsite/excel`, `barryvdh/*`). Tidak ada dependency npm. Jika benar-benar perlu, tanyakan dulu beserta alasannya. |
| A-4 | **JANGAN** menulis password, kredensial database, `APP_KEY`, atau secret apa pun di kode. Semua konfigurasi dibaca dari `.env` lewat file `config/`. `env()` **hanya** dipanggil di dalam `config/*.php`; kode lain memakai `config('...')`. `.env.example` memuat semua key Bab 5.4 tanpa nilai rahasia. `.env` masuk `.gitignore`. |
| A-5 | Semua akses database WAJIB memakai Eloquent, Query Builder, atau prepared statement dengan binding parameter. `DB::raw`, `whereRaw`, `selectRaw`, `orderByRaw`, dan `DB::statement` hanya boleh dipakai dengan binding; dilarang menyambung input pengguna ke string SQL. |
| A-6 | \[DIUBAH v0.10\] Semua query staf ke data kunjungan, QR, lokasi, departemen, kategori keperluan, dan petugas **WAJIB** melewati satu titik pusat pembatas akses: service `AccessScope` dan trait model `LimitedByAccess` (local scope `forUser()`), lihat Bab 12.2. Cakupan ditentukan oleh peran: lokasi (Admin), QR (Resepsionis), atau departemen (PIC). Jangan menulis filter `location_id`, `qr_code_id`, atau `assigned_department_id` secara manual di controller atau service lain. Implicit route model binding untuk model yang dibatasi akses dilarang. Satu-satunya pengecualian adalah pembacaan data tamu lama berdasarkan email terverifikasi di `ReturningGuestService` (Bab 9.4). |
| A-7 | Semua output ke HTML **WAJIB** di-escape: pakai `{{ }}` di Blade. `{!! !!}` dilarang, kecuali untuk konten buatan server sendiri yang tepercaya (misalnya SVG QR dari library) dan wajib diberi komentar alasannya. |
| A-8 | Jika menemukan hal ambigu yang tidak dijawab dokumen ini, pilih opsi yang paling aman dan paling sederhana, lalu catat di `docs/DECISIONS.md` (format: tanggal, konteks, keputusan, alasan). |
| A-9 | Dokumen terkait hanya dipakai jika tersedia di repositori pada folder `docs/`. Jika tidak ada, **jangan** mengarang isinya; ikuti dokumen ini. Jika dokumen Use Case & User Flow bertentangan dengan PRD ini, **PRD ini yang berlaku**. |
| A-10 | Jangan membuat data dummy di lingkungan produksi. Seeder data contoh hanya berjalan jika `APP_ENV=local`. |
| A-11 | **WAJIB** meng-commit secara bertahap di Git: satu commit untuk satu perubahan logis yang kecil dan berjalan, dilakukan segera setelah perubahan itu selesai. **JANGAN** menggabungkan satu milestone atau banyak fitur ke dalam satu commit besar. Aturan lengkap di Bab 0.5. |
| A-12 | Seluruh pesan commit **WAJIB** berbahasa Indonesia dengan format `[ID] Kata kerja + ringkasan`. `ID` adalah ID kebutuhan (misalnya `RSP-2`) atau kode milestone (misalnya `M0`). |
| A-13 | Kode **WAJIB** ditulis seperti programmer manusia yang rapi. Setiap class dan method publik memiliki docblock Bahasa Indonesia. Komentar inline hanya untuk hal yang tidak jelas dari kode dan menjelaskan *mengapa*, bukan mengulang *apa*. Ikuti PSR-12 (Laravel Pint, preset `psr12`: `vendor/bin/pint --test`) dan konvensi Bab 0.4. Satu fungsi = satu tanggung jawab, pendek, early return. Hapus kode mati, import/variabel tak terpakai, dan blok kode yang dikomentari. Tanpa abstraksi yang belum dibutuhkan nyata. |
| A-14 | **Utamakan fitur bawaan Laravel.** **JANGAN** menulis ulang router, session, CSRF, validator, hashing, rate limiter, pemuat `.env`, runner migrasi, atau helper escape. Ikuti konvensi resmi Laravel dan baca dokumentasi resmi versi yang dipakai sebelum membuat solusi sendiri. |
| A-15 | \[DIUBAH v0.10\] Tulis tes otomatis (PHPUnit) untuk area berisiko: `AccessScope` untuk keempat peran staf (IDOR antar lokasi, antar QR, dan antar departemen), transisi status dan respons 409, penugasan dan penugasan ulang, penerimaan oleh PIC, nomor urut, email terverifikasi di session, data diri tamu lama yang diambil dari database (bukan dari input), isolasi riwayat tamu per email, token sekali pakai TAM-14, ekspor yang dibatasi cakupan, dan penguncian akun. Tes memakai database MySQL/MariaDB terpisah (`.env.testing`), bukan SQLite. Verifikasi Google dites dengan `Http::fake()`. `php artisan test` harus hijau saat agent berhenti di akhir milestone. |

## 0.4 Konvensi

- **Bahasa:** seluruh teks UI dalam Bahasa Indonesia. Nama tabel, kolom, variabel, fungsi, dan class dalam Bahasa Inggris (`snake_case` untuk database, `camelCase` untuk variabel/method PHP, `PascalCase` untuk class; model tunggal, tabel jamak).
- **Waktu:** semua waktu dibuat di server dengan Carbon (`now()`) pada zona `APP_TIMEZONE` (default `Asia/Jakarta`), disimpan sebagai `DATETIME`; koneksi MySQL memakai opsi `timezone` = `DB_TIMEZONE` (default `+07:00`). Tidak ada waktu yang diambil dari input browser.
- **Format tampilan:** tanggal `dd/mm/yyyy`, jam `HH:mm` (24 jam), contoh `29/09/2026 14:05`.
- **Pemetaan status \[DIUBAH v0.10\]:**

| Nilai di database | Kondisi tambahan | Label UI | Warna badge (Bootstrap) | Kelompok |
| --- | --- | --- | --- | --- |
| `waiting` | `assigned_department_id` kosong | Menunggu | `warning` | Tamu Aktif |
| `waiting` | `assigned_department_id` terisi | Menunggu PIC | `info` | Tamu Aktif |
| `processing` | – | Sedang direview | `primary` | Tamu Aktif |
| `completed` | – | Selesai | `success` | History |
| `cancelled` | – | Dibatalkan | `secondary` | History |

"Menunggu PIC" adalah label tampilan, bukan status database baru. Pemetaan diimplementasikan sebagai *backed enum* `App\Enums\VisitStatus` dengan method `label()`, `badge()`, dan `isActive()`; label "Menunggu PIC" dihitung oleh accessor `displayStatus` di model `GuestVisit`. Enum lain: `UserRole` (`super_admin`, `admin`, `receptionist`, `pic`), `QrStatus`, dan `VulnerableGroup` (Bab 11).

## 0.5 Panduan Commit Git

Tujuan: riwayat Git terbaca sebagai pekerjaan bertahap seorang pengembang, sehingga mudah ditinjau, ditelusuri, dan dibatalkan per perubahan (A-11, A-12).

**Aturan commit**

1. Jika repositori belum ada, buat skeleton Laravel dengan `composer create-project laravel/laravel`, lalu `git init` di awal M0. Commit pertama berisi `.gitignore` yang dipastikan memuat `.env`, `vendor/`, dan `storage/logs/`. Commit kedua berisi skeleton apa adanya sebelum diubah. `composer.lock` ikut di-commit.
2. Satu commit = satu perubahan logis. Pisahkan commit untuk migrasi, model, service, controller dan route, view, JavaScript/CSS, konfigurasi, dokumentasi, dan perbaikan bug.
3. Commit dilakukan segera setelah satu unit kerja selesai dan berjalan. Setiap commit meninggalkan aplikasi dalam keadaan tidak rusak.
4. Saat agent berhenti di akhir milestone, seluruh pekerjaan sudah di-commit dan `git status` bersih. Laporan milestone menyertakan `git log --oneline` milestone tersebut.
5. **JANGAN** meng-commit `.env`, secret, kredensial, isi `vendor/` dan `storage/logs/`, dump database, data uji, atau file sementara.
6. Riwayat tidak ditulis ulang: **JANGAN** memakai `--amend`, `rebase`, `squash`, atau force push, kecuali diminta. Kesalahan diperbaiki dengan commit baru.
7. Agent tidak mengubah konfigurasi Git (nama dan email penulis) dan tidak melakukan `push`, kecuali diminta.

**Format pesan commit**

```
[ID] Kata kerja + ringkasan singkat

Badan pesan (opsional): alasan perubahan, dalam bahasa Indonesia.
```

- Baris pertama maksimal 72 karakter, tanpa titik di akhir. Kata kerja awal: Tambah, Ubah, Perbaiki, Hapus, Rapikan, Pindahkan, atau Dokumentasikan.
- Badan pesan menjelaskan **alasan** perubahan. Wajib untuk perubahan yang tidak sederhana, termasuk keputusan A-8.
- **JANGAN** memakai pesan generik ("update", "fix", "wip") atau pesan berbahasa Inggris.

**Contoh urutan commit yang diharapkan**

| No | Pesan commit |
| --- | --- |
| 1 | `[M0] Tambah .gitignore dan skeleton Laravel` |
| 2 | `[M0] Atur koneksi database, zona waktu, dan locale id` |
| 3 | `[M0] Tambah migrasi tabel departments` |
| 4 | `[M1] Tambah AccessScope dengan cakupan per peran` |
| 5 | `[QR-6] Tambah pilihan akun resepsionis pada QR` |
| 6 | `[TAM-15] Arahkan tamu lama ke halaman riwayat` |
| 7 | `[TAM-16] Ambil data diri tamu lama dari database saat menyimpan` |
| 8 | `[RSP-2] Tambah penugasan kunjungan ke departemen` |
| 9 | `[RSP-2] Tambah pilihan orang yang ditemui saat penugasan` |
| 10 | `[PIC-3] Perbaiki validasi hasil yang lolos saat berisi spasi saja` |

# 1. Ringkasan Produk (Overview)

SiTamu adalah sistem informasi web (Laravel + MySQL/MariaDB) yang mencatat tamu lewat QR Code dan meneruskan setiap kunjungan ke departemen yang tepat sampai hasil pelayanannya tercatat.

Instansi menerima tamu di banyak titik masuk dan gedung. Pencatatan manual menyulitkan pencarian data, tidak menunjukkan tamu mana yang masih menunggu, tidak mencatat ke departemen mana tamu diteruskan, dan tidak menyimpan hasil pelayanan.

Sistem terdiri dari dua bagian:

1. **Halaman tamu** tanpa akun, dibuka lewat QR Code. Tamu memverifikasi email dengan akun Google, mengisi formulir (atau melihat riwayat bila pernah berkunjung), dan menerima nomor urut.
2. **Panel staf** dengan empat peran: Resepsionis menugaskan kunjungan ke departemen, PIC melayani dan menulis hasil, Admin memantau semua lokasi serta mengelola master data dan akun Resepsionis/PIC, Super Admin menjadi akun cadangan darurat.

Setiap QR Code terhubung ke satu lokasi dan satu akun resepsionis, sehingga lokasi kunjungan dan resepsionis yang bertanggung jawab ditentukan otomatis. Siklus yang ditangani: pendaftaran → penugasan → pelayanan dan penulisan hasil → riwayat.

# 2. Tujuan & Sasaran (Goals)

- Mengganti pencatatan tamu manual dengan pencatatan digital yang terpusat dan mudah dicari.
- Memastikan setiap kunjungan tercatat pada lokasi dan titik masuk yang benar, tanpa input lokasi oleh tamu.
- &#91;BARU v0.10\] Meneruskan setiap kunjungan ke departemen yang tepat dan mencatat orang yang ditemui (petugas yang mereview tamu).
- &#91;BARU v0.10\] Mencatat hasil pelayanan dan menampilkannya kembali kepada tamu lewat riwayat kunjungannya.
- &#91;BARU v0.10\] Mempercepat pengisian bagi tamu yang pernah berkunjung dengan data diri yang terisi otomatis.
- Membatasi akses data sehingga setiap staf hanya melihat dan mengelola data dalam cakupan perannya.
- Menyimpan seluruh riwayat kunjungan beserta waktu datang, waktu diterima PIC, dan waktu selesai sebagai dasar analisis.
- Menjaga privasi data pribadi tamu dengan hanya mengumpulkan data yang ditetapkan di Bab 6.6.1, sejalan dengan UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi.

**Indikator keberhasilan MVP:**

- Tamu baru dapat menyelesaikan verifikasi email dan pengisian formulir, termasuk tanda tangan, dari ponsel dalam waktu kurang dari 2 menit.
- Resepsionis dapat menugaskan kunjungan dengan 1 aksi (pilih departemen dan orang yang ditemui, lalu Tugaskan).
- PIC dapat menerima penugasan dengan 1 klik (Terima).
- Tidak ada satu pun data di luar cakupan peran yang dapat diakses staf, walaupun URL, ID, atau parameter dimanipulasi.

# 3. Pengguna & Peran (Users & Roles) \[DIUBAH v0.10\]

Sistem memiliki empat peran staf yang login dan satu peran tamu tanpa akun; cakupan data setiap peran staf berbeda.

- **Super Admin:** \[DIUBAH v0.11\] akun cadangan untuk keadaan darurat. Memiliki semua hak Admin kecuali membuka kembali kunjungan \[DEFAULT\], ditambah satu hak yang tidak dimiliki Admin: mengelola akun Admin dan Super Admin (USR-6). Dalam operasional harian tidak perlu dipakai.
- **Admin:** \[DIUBAH v0.11\] pengelola harian untuk **semua lokasi**, tanpa penugasan lokasi (ADM-5). Memantau tamu aktif, membatalkan dan membuka kembali kunjungan, melihat dan mengekspor History, mengelola lokasi, departemen, QR Code, kategori keperluan, daftar petugas, serta akun Resepsionis dan PIC, dan melihat audit log. Admin tidak menugaskan kunjungan dan tidak mengelola akun Admin maupun Super Admin (USR-6).
- **Resepsionis:** akun bersama per pos penerimaan tamu (misalnya "Resepsionis Pintu Timur"), dipakai bergantian oleh petugas yang sedang jaga. Cakupannya adalah QR yang terhubung ke akunnya. Melihat tamu aktif dari QR tersebut, menugaskan dan menugaskan ulang kunjungan ke departemen sekaligus memilih orang yang ditemui (petugas yang akan mereview tamu), serta membatalkan kunjungan.
- **PIC:** akun bersama per departemen (misalnya "PIC Bidang Perdagangan Dalam Negeri"), satu akun per departemen yang dipakai beberapa orang secara bergantian. Cakupannya adalah kunjungan yang ditugaskan ke departemennya. Menerima penugasan, lalu orang yang ditemui yang dipilih resepsionis mereview keperluan tamu lewat akun ini, menulis hasil, dan menyelesaikan kunjungan.
- **Tamu:** tidak memiliki akun. Memindai QR, memverifikasi email lewat akun Google, mengisi formulir dan membubuhkan tanda tangan, menerima nomor urut, dan melihat riwayat kunjungannya sendiri beserta hasil dari PIC.

## 3.1 Matriks hak akses

| Fitur | Super Admin | Admin | Resepsionis | PIC | Tamu |
| --- | --- | --- | --- | --- | --- |
| Isi formulir buku tamu via QR | – | – | – | – | Ya |
| Lihat riwayat kunjungan sendiri | – | – | – | – | Ya |
| Dashboard | Semua lokasi | Semua lokasi | QR miliknya | Departemennya | – |
| Tamu Aktif: lihat, cari, filter | Semua lokasi | Semua lokasi | QR miliknya | – | – |
| Tugaskan / tugaskan ulang (departemen dan orang yang ditemui) | Tidak | Tidak | QR miliknya | – | – |
| Daftar penugasan: terima, tulis hasil, selesaikan | Tidak | Tidak | Tidak | Departemennya | – |
| Batalkan kunjungan | Semua lokasi | Semua lokasi | QR miliknya | Tidak | – |
| Buka kembali kunjungan Selesai/Dibatalkan | Tidak \[DEFAULT\] | Semua lokasi | Tidak | Tidak | – |
| Detail kunjungan dan riwayat status | Semua lokasi | Semua lokasi | QR miliknya | Departemennya | – |
| Gambar tanda tangan | Semua lokasi | Semua lokasi | Tidak | Tidak | – |
| History: lihat, cari, filter, ekspor CSV/Excel/PDF | Semua lokasi | Semua lokasi | Tidak | Tidak | – |
| Kelola kategori keperluan | Semua lokasi | Semua lokasi | Tidak | Tidak | – |
| Kelola daftar petugas departemen | Semua lokasi | Semua lokasi | Tidak | Tidak | – |
| Kelola lokasi | Ya | Ya | Tidak | Tidak | – |
| Kelola departemen | Ya | Ya | Tidak | Tidak | – |
| Kelola QR Code dan akun resepsionisnya | Ya | Ya | Tidak | Tidak | – |
| Kelola akun Resepsionis dan PIC | Ya | Ya | Tidak | Tidak | – |
| Kelola akun Admin dan Super Admin | Ya | Tidak | Tidak | Tidak | – |
| Buka kunci akun | Ya | Resepsionis dan PIC | Tidak | Tidak | – |
| Lihat audit log | Ya | Ya | Tidak | Tidak | – |
| Ganti password sendiri | Ya | Ya | Ya | Ya | – |

**Catatan:** "QR miliknya" berarti QR yang kolom `receptionist_id`-nya menunjuk akun resepsionis tersebut, apa pun status QR-nya, agar kunjungan aktif dari QR yang baru dinonaktifkan tetap bisa diselesaikan. "Departemennya" berarti kunjungan dengan `assigned_department_id` sama dengan `department_id` akun PIC.

# 4. Ruang Lingkup (Scope)

MVP mencakup seluruh siklus kunjungan dari pemindaian QR sampai hasil tampil di riwayat tamu; fitur di Bab 4.2 tidak dikerjakan.

## 4.1 Termasuk (MVP)

- Manajemen lokasi, departemen, dan QR Code per titik masuk (buat, aktif/nonaktif, regenerate, unduh PNG/SVG, cetak), dengan satu akun resepsionis per QR.
- Halaman tamu mobile-first: verifikasi email Google, deteksi tamu lama, riwayat kunjungan tamu, formulir dengan kategori keperluan dan kelompok rentan, nomor urut otomatis, jam datang otomatis, dan tanda tangan digital.
- Pengisian otomatis data diri tamu lama dari kunjungan terakhirnya.
- Manajemen akun staf empat peran dan pengaitan akun PIC ke departemen.
- Kategori keperluan per lokasi dengan saran departemen, dan daftar petugas per departemen.
- Tamu Aktif untuk Resepsionis (dengan penugasan), Admin, dan Super Admin; daftar Penugasan untuk PIC (terima, tulis hasil, selesaikan).
- Pembatalan kunjungan dengan alasan dan buka kembali oleh Admin.
- History Kunjungan dengan pencarian, filter, dan ekspor CSV, Excel, dan PDF.
- Dashboard sesuai peran, pembatasan akses berbasis peran di sisi server, audit log, dan pencatatan waktu kunjungan.

## 4.2 Di luar lingkup MVP (JANGAN diimplementasikan)

- Semua fitur di Bab 16.
- &#91;DIHAPUS v0.10\] QR Darurat dan jalur pencatatan apa pun bagi tamu yang tidak dapat login Google. Penanganan tamu tersebut berada di luar sistem.
- &#91;DIHAPUS v0.10\] Penolakan penugasan oleh PIC. Kesalahan departemen diperbaiki resepsionis lewat penugasan ulang selama PIC belum menerima.
- &#91;DIHAPUS v0.10\] Status Draf atau status apa pun selain empat status di Bab 0.4.
- Notifikasi WhatsApp, SMS, atau email kepada tamu maupun staf. Jika PIC tidak merespons, resepsionis menghubunginya secara manual di luar sistem.
- Pengiriman kode verifikasi (OTP) lewat SMS/WhatsApp. Riwayat tamu hanya dibuka lewat verifikasi Google.
- Formulir dinamis (pembuat pertanyaan bebas). Yang dapat dikelola Admin hanya daftar kategori keperluan (KAT-1).
- CAPTCHA, API publik, aplikasi mobile native, dan antarmuka multi-bahasa.
- Nomor antrean atau pemanggilan tamu. Nomor urut (TAM-10) hanya nomor pencatatan buku tamu; urutan pelayanan tetap berdasarkan jam datang.
- Fitur Laravel yang tidak dibutuhkan MVP: starter kit autentikasi, Livewire/Inertia, queue worker, scheduler (cron), broadcasting/WebSocket, notifikasi Laravel, API token (Sanctum/Passport), dan build frontend dengan Node/Vite. Pembaruan daftar memakai polling (RSP-7, PIC-4).

# 5. Stack, Arsitektur & Konfigurasi

Stack tetap sama dengan v0.9 (Laravel tanpa starter kit, Blade, Bootstrap, JS vanilla); yang berubah adalah struktur model, service, dan key konfigurasi.

## 5.1 Stack teknologi

| Komponen | Pilihan | Catatan |
| --- | --- | --- |
| Bahasa | PHP sesuai persyaratan versi Laravel yang dipakai (Bab 17 No. 10) | Ekstensi wajib: persyaratan standar Laravel ditambah `pdo_mysql`, `curl`, `gd`, `zip`, `xmlwriter`, `simplexml`. |
| Framework | Laravel, versi mayor stabil terbaru yang kompatibel dengan PHP server | Tanpa starter kit, tanpa Livewire/Inertia, tanpa Sanctum/Passport (A-14). |
| Database | MySQL 8.0+ atau MariaDB 10.6+ | InnoDB, `utf8mb4`, `utf8mb4_unicode_ci`. Skema lewat migrasi Laravel (Bab 9.2). |
| Dependency Composer | `laravel/framework` beserta paket bawaan skeleton, `endroid/qr-code`, `phpoffice/phpspreadsheet`, `dompdf/dompdf` | Di luar skeleton hanya tiga paket ini. Paket dev bawaan skeleton (PHPUnit, Pint, Faker, Mockery) boleh dipakai. |
| Frontend | Blade, CSS, JavaScript vanilla (`fetch`), Bootstrap 5.3 | Tampilan memakai template Volt (Bab 5.6). Tanpa jQuery, framework JS, dan Node/Vite/npm di server. Aset statis di `public/assets/`. Tanda tangan dengan `<canvas>` dan Pointer Events. |
| Grafik dashboard | Chart.js 4 | Disimpan lokal di `public/assets/vendor/`, bukan dari CDN. |
| Arsitektur | MVC Laravel dengan lapisan Service | Controller tipis, Form Request untuk validasi, Service untuk aturan bisnis, model Eloquent untuk data, tanpa lapisan Repository. |
| Autentikasi | Guard `web` bawaan Laravel (session) | Penguncian akun, peran, dan batas sesi dibangun di atas guard ini. |
| Rate limiting | `RateLimiter` bawaan Laravel, cache store `file` | Limiter bernama didefinisikan di `AppServiceProvider`. |
| Verifikasi email tamu | Google Identity Services dan verifikasi ID token di server lewat `Http` facade | Skrip klien dimuat dari Google hanya di Langkah 1 halaman tamu. Hanya `GOOGLE_CLIENT_ID` yang dibutuhkan. |
| Pembaruan daftar staf | Polling `fetch` setiap `POLL_INTERVAL_SECONDS` | Tanpa WebSocket atau broadcasting. |

## 5.2 Struktur folder \[DIUBAH v0.10\]

```
sitamu/
├── app/
│   ├── Console/Commands/    # CreateSuperAdmin (php artisan sitamu:create-superadmin)
│   ├── Enums/               # VisitStatus, UserRole, QrStatus, VulnerableGroup
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Guest/       # GuestEntry, GuestForm, GuestHistory, GoogleVerification
│   │   │   ├── Auth/        # Login, Logout
│   │   │   └── Admin/       # Dashboard, ActiveVisit, Assignment, Visit, History, Location, Department,
│   │   │                    # Qr, PurposeCategory, StaffMember, User, AuditLog, Password
│   │   ├── Middleware/      # SecurityHeaders, EnsureUserIsActive, EnsureRole
│   │   └── Requests/        # GuestVisitRequest, AssignVisitRequest, AcceptVisitRequest, CompleteVisitRequest, dst.
│   ├── Models/              # User, Location, Department, QrCode, PurposeCategory, StaffMember, GuestVisit,
│   │   │                    # VisitAssignment, VisitStatusLog, VisitSignature, AuditLog
│   │   └── Concerns/        # LimitedByAccess (local scope forUser)
│   ├── Providers/           # AppServiceProvider (rate limiter bernama)
│   ├── Rules/               # IndonesianPhone, ValidSignatureImage
│   └── Services/            # VisitService, AccessScope, ReturningGuestService, QrService, AuditService,
│                            # ExportService, SignatureService, GoogleTokenVerifier
├── bootstrap/app.php        # alias dan grup middleware, penanganan exception
├── config/                  # bawaan Laravel, sitamu.php (key aplikasi), consent.php (teks persetujuan)
├── database/
│   ├── migrations/          # satu migrasi per tabel, urut sesuai dependensi foreign key
│   └── seeders/             # data contoh, hanya jika APP_ENV=local
├── public/
│   ├── index.php, .htaccess # bawaan Laravel
│   └── assets/              # css, js (signature-pad.js, guest-google.js, active-visits.js, assignments.js),
│                            # img (logo), vendor (volt, chart.js)
├── resources/views/         # layouts/, guest/, admin/, admin/export/, errors/
├── routes/web.php           # seluruh route (Bab 10); tanpa routes/api.php
├── storage/logs/
├── tests/                   # Feature/ dan Unit/ (A-15)
├── docs/                    # PRD.md, DECISIONS.md, dokumen terkait
├── .env.example
├── composer.json
└── README.md
```

## 5.3 Alur request

`public/index.php` → `bootstrap/app.php` → middleware global bawaan (`TrimStrings`, `ConvertEmptyStringsToNull`) → grup `web` (session, CSRF) + `SecurityHeaders` → untuk route staf: `auth` → `active` → `role:<peran>` → Controller → Form Request → Service → model Eloquent (lewat `AccessScope`) → database.

Controller tidak berisi query maupun aturan bisnis. Aturan bisnis (transisi status, penugasan, pembatasan akses) hanya berada di Service. Query yang dipakai berulang ditaruh di *local scope* model. Query laporan (dashboard, History, ekspor) boleh memakai Query Builder di dalam Service dan tetap melewati `AccessScope`.

## 5.4 Konfigurasi `.env`

Key bawaan Laravel dibaca framework langsung. Key aplikasi dipetakan di `config/sitamu.php` karena `env()` hanya boleh dipanggil di folder `config/` (A-4).

**Key bawaan Laravel**

| Key | Contoh nilai | Keterangan |
| --- | --- | --- |
| `APP_NAME` | `SiTamu` | Judul halaman. |
| `APP_ENV` | `local` / `production` |  |
| `APP_KEY` | dibuat `php artisan key:generate` | Rahasia; **JANGAN** di-commit. |
| `APP_DEBUG` | `true` (local) / `false` (production) | Wajib `false` di produksi. |
| `APP_URL` | `https://sitamu.contoh.go.id` | Dipakai untuk URL di QR Code. |
| `APP_TIMEZONE` | `Asia/Jakarta` | \[DEFAULT\] WIB. |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE` | `id` | Pesan validasi ditulis eksplisit di Form Request. |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | `mysql`, … | Kredensial database. |
| `DB_CHARSET`, `DB_COLLATION` | `utf8mb4`, `utf8mb4_unicode_ci` |  |
| `DB_TIMEZONE` | `+07:00` | \[DEFAULT\] Opsi `timezone` koneksi MySQL. |
| `SESSION_DRIVER` | `file` | \[DEFAULT\] Satu server aplikasi. |
| `SESSION_LIFETIME` | `120` | \[DEFAULT\] Menit tanpa aktivitas sebelum sesi staf berakhir. |
| `SESSION_SECURE_COOKIE` | `true` (production) | Cookie sesi hanya lewat HTTPS. |
| `SESSION_SAME_SITE` | `lax` |  |
| `CACHE_STORE` | `file` | \[DEFAULT\] Dipakai rate limiter. |
| `QUEUE_CONNECTION` | `sync` | Tidak ada queue worker. |
| `LOG_CHANNEL`, `LOG_LEVEL` | `daily`, `error` |  |
| `BCRYPT_ROUNDS` | `12` | Minimal 12. |

**Key aplikasi (`config/sitamu.php`)**

| Key | Contoh nilai | Keterangan |
| --- | --- | --- |
| `LOGIN_MAX_ATTEMPTS` | `5` | \[DEFAULT\] Gagal login sebelum akun dikunci. |
| `LOGIN_LOCK_MINUTES` | `15` | \[DEFAULT\] Lama akun dikunci. |
| `LOGIN_RATE_LIMIT_MAX` | `20` | \[DEFAULT\] Percobaan login per IP per jendela waktu. |
| `LOGIN_RATE_LIMIT_WINDOW_MINUTES` | `15` | \[DEFAULT\] |
| `GUEST_RATE_LIMIT_MAX` | `5` | \[DEFAULT\] Kiriman formulir per IP per QR per jendela waktu. |
| `GUEST_RATE_LIMIT_WINDOW_MINUTES` | `10` | \[DEFAULT\] |
| `GOOGLE_VERIFY_RATE_LIMIT_MAX` | `10` | \[DEFAULT\] Percobaan verifikasi Google per IP per QR dalam jendela `GUEST_RATE_LIMIT_WINDOW_MINUTES`. |
| `GOOGLE_CLIENT_ID` | `1234567890-xxxx.apps.googleusercontent.com` | OAuth Client ID (Web application) milik instansi. Tanpa client secret. |
| `EMAIL_VERIFY_TTL_MINUTES` | `30` | \[DEFAULT\] Masa berlaku hasil verifikasi email di session tamu. |
| `POLL_INTERVAL_SECONDS` | `30` | \[DEFAULT\] Interval polling Tamu Aktif dan Penugasan. |
| `WAITING_ALERT_MINUTES` | `15` | \[DEFAULT\] Batas menunggu sebelum kunjungan ditandai. |
| `SIGNATURE_MAX_KB` | `200` | \[DEFAULT\] Ukuran maksimal gambar tanda tangan setelah di-decode. |
| `GUEST_HISTORY_PER_PAGE` | `10` | \[DEFAULT\] \[BARU v0.10\] Jumlah kunjungan per halaman riwayat tamu. |
| `CSV_DELIMITER` | `;` | \[DEFAULT\] |
| `EXPORT_RICH_MAX_ROWS` | `500` | \[DEFAULT\] Batas baris ekspor Excel dan PDF. |
| `INSTITUTION_NAME` | `Dinas Perindustrian dan Perdagangan Provinsi Jawa Timur (Disperindag)` | \[DEFAULT\] |

&#91;DIHAPUS v0.10\] `EMERGENCY_PASS_TTL_MINUTES`.

Tidak ada password default. Super Admin pertama dibuat lewat `php artisan sitamu:create-superadmin` yang meminta nama, email, dan password secara interaktif (input password disembunyikan).

## 5.5 Asumsi & batasan

- Sistem tidak memverifikasi keberadaan fisik tamu di lokasi. Penyalahgunaan dikurangi dengan rate limiting dan honeypot.
- Satu baris `guest_visits` mewakili satu kunjungan; tamu yang sama dicatat ulang pada setiap kunjungan.
- Data isian tamu, nomor urut, jam datang, dan tanda tangan **tidak dapat diedit atau dihapus** lewat aplikasi. Staf hanya mengubah status, penugasan, dan hasil sesuai Bab 7.
- &#91;BARU v0.10\] Setiap QR terhubung ke tepat satu akun resepsionis. Satu akun resepsionis boleh terhubung ke lebih dari satu QR \[DEFAULT\].
- &#91;BARU v0.10\] Akun resepsionis dan PIC adalah akun bersama yang dipakai bergantian. Audit log mencatat akun; orang yang ditemui dicatat saat resepsionis menugaskan (RSP-2).
- &#91;BARU v0.10\] Setiap departemen memiliki tepat satu akun PIC aktif \[DEFAULT\]. Departemen tanpa akun PIC aktif tidak muncul di pilihan penugasan.
- &#91;BARU v0.10\] Tamu lama dikenali dari email Google terverifikasi yang sama, lintas semua lokasi \[DEFAULT\]. Data diri tamu lama diambil dari kunjungan terakhirnya.
- &#91;BARU v0.10\] Seluruh tamu yang tercatat memiliki email terverifikasi Google. Tamu yang tidak dapat login Google tidak tercatat di sistem.
- Tanda tangan adalah gambar goresan tangan sebagai bukti kehadiran, **bukan** Tanda Tangan Elektronik tersertifikasi.
- File Excel dan PDF hasil ekspor berada di luar kendali sistem setelah diunduh.
- QR Code tercetak dapat difoto siapa pun. Token berfungsi sebagai penanda lokasi yang tidak dapat ditebak, **bukan** sebagai rahasia.
- URL di QR Code dibentuk dari `APP_URL`. Jika domain berubah setelah QR dicetak, QR harus dicetak ulang.
- HTTPS wajib di produksi. Web server mengarahkan *document root* ke folder `public/`.
- Jika server berada di belakang reverse proxy, *trusted proxies* WAJIB dikonfigurasi agar IP klien dan skema HTTPS terbaca benar.
- Verifikasi Google membutuhkan koneksi internet dari ponsel tamu dan dari server ke Google.
- Aplikasi tidak membutuhkan cron, queue worker, maupun Node di server produksi.

## 5.6 Desain antarmuka: template Volt \[BARU v0.10\]

Panel staf memakai template [Volt Bootstrap 5 Dashboard](https://github.com/themesberg/volt-bootstrap-5-dashboard) dari Themesberg sebagai acuan desain dan sumber CSS/JS: lisensi MIT, berbasis Bootstrap 5, JavaScript vanilla tanpa jQuery.

**Cara memasang (tanpa npm di server)**

- Versi acuan: rilis terbaru di GitHub (v1.4.1) \[DEFAULT\]. Simpan file `LICENSE.md` Volt di `public/assets/vendor/volt/`.
- Build sekali di mesin pengembang dengan `gulp build:dist`, lalu salin **hanya** CSS, JS, font, dan ikon hasil build ke `public/assets/vendor/volt/` dan commit. File sumber Volt (SCSS, `gulpfile.js`, `package.json`, `node_modules/`) **JANGAN** masuk repositori. Server produksi tetap tanpa Node (Bab 4.2). Volt adalah aset statis, bukan dependency Composer/npm proyek (A-3).
- Bootstrap yang dipakai adalah Bootstrap yang sudah dibundel di CSS Volt. **JANGAN** memuat file Bootstrap kedua. Versi Bootstrap hasil build dicatat di `docs/DECISIONS.md`.
- Semua aset (CSS, JS, font, ikon, gambar) disajikan lokal dari `public/assets/`, tanpa CDN, agar sesuai CSP `'self'` (Bab 12.1). Partial analitik bawaan demo Volt (`_analytics.html`) dan skrip pelacak apa pun **JANGAN** disertakan.
- Elemen demo yang tidak relevan dihapus: banner "Upgrade to Pro", tautan Themesberg, data contoh, dan halaman yang tidak dipakai.

**Pemetaan halaman Volt ke SiTamu**

| Halaman/komponen Volt | Dipakai untuk (Bab 13) |
| --- | --- |
| Sign in | Login staf |
| Dashboard (overview) | Dashboard Admin dan Super Admin: kartu angka, grafik, tabel ringkas |
| Transactions (tabel, filter, paginasi) | Tamu Aktif, Penugasan PIC, History, Audit log, Pengguna |
| Settings (form) | Form master data (lokasi, departemen, QR, kategori keperluan, petugas, pengguna) dan Ganti Password |
| Sidebar dan top navbar | Layout staf dengan menu per peran (Bab 13.2) |
| 404 dan 500 | Halaman error kustom berbahasa Indonesia (Bab 10.9) |
| Komponen modal, notifikasi/toast, badge, dropdown | Modal Tugaskan, Terima, Selesai, Batal, Buka Kembali; toast setelah aksi; badge status; dropdown Ekspor |

**Aturan tambahan**

- Halaman tamu (Bab 13.1) **tidak** memakai layout dashboard Volt. Halaman tamu memakai CSS Volt (warna, tipografi, tombol, form) dalam layout satu kolom mobile-first tanpa sidebar.
- Grafik dashboard tetap Chart.js (Bab 5.1). Plugin grafik bawaan Volt tidak dipakai. Plugin JS Volt lain hanya dimuat di halaman yang memakainya.
- Warna utama Volt diganti warna identitas instansi lewat variabel CSS di satu file (Bab 13).
- Tampilan harus tetap memenuhi Bab 12.5: tabel Tamu Aktif dan Penugasan berubah menjadi kartu di layar < 768 px, dan semua halaman nyaman di layar 360 px.

# 6. Kebutuhan Fungsional (Functional Requirements)

Kolom **Kriteria Penerimaan** adalah syarat agar kebutuhan dianggap selesai dan menjadi acuan pengujian. \[DIHAPUS v0.10\] ID `VIS-1` sampai `VIS-11` dan `TAM-13` tidak dipakai lagi; fungsinya digantikan RSP, PIC, dan ADM.

## 6.1 Admin dan Super Admin — Manajemen Lokasi \[DIUBAH v0.11\]

\[DIUBAH v0.11\] Setiap "Super Admin" pada tabel di bawah berarti **Admin dan Super Admin**, untuk semua lokasi.

| ID | Kebutuhan | Prioritas | Kriteria Penerimaan |
| --- | --- | --- | --- |
| LOK-1 | Super Admin dapat menambah dan mengubah lokasi (nama, deskripsi). | Wajib | Nama wajib, 2–100 karakter, unik (tidak peka huruf besar/kecil, termasuk yang sudah di-soft delete). Duplikat ditolak: "Nama lokasi sudah digunakan". Tercatat di audit log. |
| LOK-2 | Super Admin dapat menonaktifkan dan mengaktifkan kembali lokasi. | Wajib | Saat lokasi nonaktif, semua QR-nya menampilkan halaman TAM-6. Kunjungan aktif lokasi itu tetap dapat diselesaikan. Jika lokasi masih punya kunjungan aktif, sistem meminta konfirmasi. Tercatat di audit log. |
| LOK-3 | Lokasi yang sudah memiliki data (kunjungan, departemen, atau QR) tidak dapat dihapus, hanya dinonaktifkan. | Wajib | Tombol Hapus hanya muncul jika lokasi belum punya data tersebut. Hapus = soft delete. Server tetap menolak walau request dikirim manual. |

## 6.2 Admin dan Super Admin — Manajemen Departemen \[DIUBAH v0.11\]

\[DIUBAH v0.11\] Setiap "Super Admin" pada tabel di bawah berarti **Admin dan Super Admin**, untuk semua lokasi.

| ID | Kebutuhan | Prioritas | Kriteria Penerimaan |
| --- | --- | --- | --- |
| DEP-1 | Super Admin dapat menambah dan mengubah departemen per lokasi. | Wajib | Nama wajib, 2–100 karakter, unik per lokasi. Lokasi departemen tidak dapat diubah setelah dibuat. Tercatat di audit log. |
| DEP-2 | Super Admin dapat menonaktifkan dan mengaktifkan kembali departemen. | Wajib | Departemen nonaktif tidak muncul di pilihan penugasan, saran departemen kategori, maupun pilihan departemen akun PIC. Penugasan yang sudah ada tetap berjalan. |
| DEP-3 | Departemen tidak dapat dihapus. | Wajib | Tidak ada tombol Hapus; hanya aktif/nonaktif. |

## 6.3 Admin dan Super Admin — Manajemen QR Code \[DIUBAH v0.11\]

\[DIUBAH v0.11\] Setiap "Super Admin" pada tabel di bawah berarti **Admin dan Super Admin**, untuk semua lokasi.

| ID | Kebutuhan | Prioritas | Kriteria Penerimaan |
| --- | --- | --- | --- |
| QR-1 | Sistem membuat QR Code dengan token acak unik dan URL `{APP_URL}/tamu/{token}`. | Wajib | Token = `bin2hex(random_bytes(32))` (64 karakter hex). Token tidak diturunkan dari ID apa pun. |
| QR-2 | \[DIUBAH v0.10\] Satu lokasi dapat memiliki satu atau lebih QR Code; setiap QR wajib terhubung ke tepat satu akun resepsionis. | Wajib | Form buat QR berisi label opsional (misalnya "Pintu Timur") dan pilihan akun resepsionis aktif (wajib). |
| QR-3 | Super Admin dapat mengaktifkan dan menonaktifkan QR Code. | Wajib | QR `inactive` menampilkan halaman TAM-6. QR `revoked` tidak bisa diaktifkan kembali (tombol tidak muncul dan server menolak). |
| QR-4 | \[DIUBAH v0.10\] Super Admin dapat me-regenerate QR Code. | Wajib | Regenerate = membuat baris QR baru (token baru, lokasi, label, dan akun resepsionis sama, status `active`) dan mengubah QR lama menjadi `revoked` dengan `revoked_at` terisi, dalam satu transaksi. Wajib konfirmasi: "QR lama yang sudah dicetak tidak akan berfungsi lagi." |
| QR-5 | Super Admin dapat mengunduh QR (PNG/SVG) dan membuka halaman cetak. | Wajib | PNG minimal 600×600 px. Halaman cetak A4 berisi logo, nama instansi, nama lokasi, label QR, QR, dan instruksi "Pindai untuk mengisi buku tamu". Unduhan tercatat di audit log. |
| QR-6 | \[BARU v0.10\] Super Admin dapat mengganti akun resepsionis sebuah QR. | Wajib | Hanya akun resepsionis aktif yang dapat dipilih. Setelah diganti, kunjungan aktif dari QR tersebut langsung tampil di Tamu Aktif resepsionis baru dan hilang dari resepsionis lama. Tercatat di audit log (`qr.change_receptionist`, detail: akun lama dan baru). |

## 6.4 Admin — Kategori Keperluan \[BARU v0.10\]

| ID | Kebutuhan | Prioritas | Kriteria Penerimaan |
| --- | --- | --- | --- |
| KAT-1 | Admin dan Super Admin (semua lokasi) dapat menambah dan mengubah kategori keperluan per lokasi. | Wajib | Field: nama (2–100 karakter, unik per lokasi), saran departemen (opsional, hanya departemen aktif di lokasi yang sama), dan urutan tampil (angka). Kategori tampil di formulir tamu sesuai urutan, lalu nama. Tercatat di audit log. |
| KAT-2 | Admin dan Super Admin dapat menonaktifkan dan mengaktifkan kembali kategori. | Wajib | Kategori nonaktif tidak tampil di formulir. Kunjungan lama tetap menampilkan nama kategori saat kunjungan dibuat (disimpan sebagai salinan di `guest_visits.purpose_category_name`). |
| KAT-3 | Kategori tidak dapat dihapus. | Wajib | Hanya aktif/nonaktif. |

## 6.5 Admin — Daftar Petugas Departemen \[BARU v0.10\]

| ID | Kebutuhan | Prioritas | Kriteria Penerimaan |
| --- | --- | --- | --- |
| PTG-1 | Admin dan Super Admin (semua lokasi) dapat menambah, mengubah, menonaktifkan, dan mengaktifkan kembali petugas per departemen, yaitu orang yang dapat ditemui tamu. | Wajib | Field: nama petugas (2–100 karakter, unik per departemen). Petugas nonaktif tidak muncul di pilihan orang yang ditemui (RSP-2). Tercatat di audit log. |
| PTG-2 | Petugas tidak dapat dihapus. | Wajib | Hanya aktif/nonaktif, agar nama petugas pada kunjungan lama tetap terbaca. |

## 6.6 Tamu — Buku Tamu dan Riwayat

| ID | Kebutuhan | Prioritas | Kriteria Penerimaan |
| --- | --- | --- | --- |
| TAM-1 | \[DIUBAH v0.10\] Tamu membuka `/tamu/{token}` tanpa login; sistem memvalidasi token, status QR, status lokasi, dan ketersediaan kategori. | Wajib | Halaman hanya tampil jika token ada, QR `active`, lokasi aktif dan belum dihapus, serta lokasi memiliki minimal satu kategori keperluan aktif. Selain itu halaman TAM-6. |
| TAM-2 | Nama lokasi tampil sebagai informasi read-only; tidak ada input lokasi. | Wajib | Tidak ada field lokasi (termasuk hidden field). Lokasi dan QR saat menyimpan selalu diambil dari token di URL. |
| TAM-3 | \[DIUBAH v0.10\] Tamu mengisi Kategori keperluan, Rincian keperluan, Kelompok rentan, Nama lengkap, Instansi/perusahaan, Nomor telepon/WhatsApp, Alamat, dan Tanda tangan (wajib). Orang yang ditemui tidak diisi tamu; nilainya dipilih resepsionis saat menugaskan (RSP-2). Email berasal dari verifikasi Google dan tampil read-only di bagian atas. | Wajib | Urutan field mengikuti Bab 11. Field Rincian keperluan baru muncul setelah kategori dipilih. Saran departemen dari kategori **tidak** ditampilkan kepada tamu. Jika validasi gagal, formulir tampil ulang dengan isian teks sebelumnya, pesan per field, dan kotak tanda tangan kosong disertai pesan "Silakan bubuhkan tanda tangan kembali." |
| TAM-4 | \[DIUBAH v0.10\] Sistem menyimpan kunjungan dengan status `waiting`, `check_in_at` dari server, nomor urut, dan tanda tangan. | Wajib | Satu transaksi, berurutan: (1) ambil nomor urut (TAM-10), (2) insert `guest_visits` termasuk `email`, `email_verified_at`, salinan nama kategori, dan saran departemen dari kategori, (3) insert `visit_signatures`, (4) insert `visit_status_logs` (`from_status` NULL → `waiting`, `changed_by` NULL), (5) update `qr_codes.last_used_at`. Jika satu langkah gagal, seluruh transaksi dibatalkan dan nomor urut tidak terpakai. Token QR, email di session, dan kategori divalidasi ulang saat POST. |
| TAM-5 | Tamu menerima halaman konfirmasi setelah data tersimpan. | Wajib | Post/Redirect/Get: redirect 303 ke `/tamu/{token}/terima-kasih`. Halaman menampilkan nama tamu, lokasi, nomor urut, dan jam datang dari flash session; tanpa flash session, redirect ke `/tamu/{token}`. Refresh tidak menyimpan data ganda. Tersedia tautan "Lihat riwayat kunjungan". |
| TAM-6 | Token tidak valid, dicabut, nonaktif, lokasi nonaktif, atau lokasi tanpa kategori aktif menampilkan pesan seragam. | Wajib | HTTP 404 dengan teks identik: "QR Code ini tidak aktif. Silakan hubungi petugas di lokasi." |
| TAM-7 | Formulir memuat pernyataan persetujuan pemrosesan data pribadi. | Wajib | Checkbox wajib dicentang setiap kunjungan, termasuk tamu lama. Teks dari `config/consent.php`. Waktu persetujuan disimpan di `consent_at`. |
| TAM-8 | Sistem membatasi pengiriman berulang dan menyediakan honeypot anti-bot. | Wajib | Kiriman melebihi `GUEST_RATE_LIMIT_MAX` dari IP yang sama ke QR yang sama dalam `GUEST_RATE_LIMIT_WINDOW_MINUTES` ditolak HTTP 429 dengan pesan ramah (`RateLimiter` Laravel). Honeypot `website` disembunyikan dengan CSS; jika terisi, tampil halaman sukses palsu tanpa menyimpan data. |
| TAM-9 | Tamu membubuhkan tanda tangan pada kotak tanda tangan di formulir, setiap kunjungan. | Wajib | `<canvas>` dapat digambar dengan jari, stylus, atau mouse; ada tombol "Hapus Tanda Tangan". Gambar dikirim sebagai data URL PNG di field tersembunyi `signature`, divalidasi sesuai Bab 11, lalu disimpan hasil re-encode GD di `visit_signatures`. Gambar hanya dapat dilihat Admin dan Super Admin melalui route Bab 10.6. |
| TAM-10 | Sistem memberi nomor urut otomatis pada setiap kunjungan. | Wajib | \[DEFAULT\] Mulai dari 1 setiap hari per lokasi (tanggal `check_in_at` di zona `APP_TIMEZONE`). Diambil atomik lewat `visit_counters` (Bab 9.3). Kombinasi (`location_id`, `visit_date`, `daily_number`) unik. Nomor tidak berubah dan tidak dipakai ulang. Kiriman yang ditolak tidak memakai nomor. Tampilan `No. {n}`. |
| TAM-11 | \[DIUBAH v0.10\] Tamu memverifikasi email lewat akun Google sebelum formulir atau riwayat tampil. | Wajib | (1) Selama session belum memuat email terverifikasi untuk QR tersebut, `/tamu/{token}` menampilkan **Langkah 1**: judul, badge lokasi, tombol "Pilih akun Google", dan tautan "Tidak bisa login Google?". (2) Tombol memakai Google Identity Services tanpa One Tap dan tanpa auto-select; inisialisasi dari `guest-google.js`, bukan inline. (3) Server membuat `nonce` acak per percobaan di session; browser mengirim ID token ke `POST /tamu/{token}/verifikasi-google` (CSRF, rate limit `GOOGLE_VERIFY_RATE_LIMIT_MAX`). (4) Server memverifikasi token lewat `Http` facade (timeout 5 detik) ke endpoint `tokeninfo` Google: `aud` = `GOOGLE_CLIENT_ID`, `iss` Google, `exp` belum lewat, `email_verified` true, `nonce` cocok. Hanya `email` dan `email_verified` yang dibaca. (5) Jika berhasil, email (huruf kecil) disimpan di session terikat pada QR selama `EMAIL_VERIFY_TTL_MINUTES`, lalu tamu diarahkan sesuai TAM-15. Tombol "Ganti akun" (`POST /tamu/{token}/ganti-akun`) menghapus hasil verifikasi. (6) Saat POST formulir, server memakai email dari session dan **tidak pernah** membaca email dari input browser. Jika tidak ada atau kedaluwarsa, tamu kembali ke Langkah 1 dengan pesan "Verifikasi email kedaluwarsa. Silakan pilih akun Google lagi." (7) Verifikasi gagal atau Google tidak dapat dihubungi: "Verifikasi akun Google gagal. Silakan coba lagi." Verifikasi tidak membuat baris di tabel `users`. |
| TAM-12 | \[DIUBAH v0.10\] Tamu yang gagal login Google dapat membuka halaman bantuan. | Penting | Tautan "Tidak bisa login Google?" selalu tampil di Langkah 1. `/tamu/{token}/bantuan` menampilkan "Coba buka halaman ini di Chrome atau Safari. Jika masih belum bisa, silakan hubungi petugas." dan tombol "Coba lagi". Halaman ini tidak menyediakan formulir maupun cara melewati verifikasi. |
| TAM-13 | \[DIHAPUS v0.10\] Formulir via QR Darurat. | – | Tidak diimplementasikan. |
| TAM-14 | Sistem mencegah penyimpanan ganda akibat kiriman berulang. | Wajib | Setiap render formulir menyertakan token sekali pakai di session (terpisah dari CSRF, acak per render, dihapus saat dipakai dengan `session()->pull()`). Kiriman dengan token terpakai ditolak tanpa menyimpan data dan diarahkan ke halaman konfirmasi. |
| TAM-15 | \[BARU v0.10\] Setelah verifikasi email, sistem otomatis menentukan tamu baru atau tamu lama. | Wajib | Pengecekan dilakukan server, bukan pilihan tamu: tamu lama = ada minimal satu `guest_visits` dengan `email` yang sama, di lokasi mana pun \[DEFAULT\]. Tamu baru diarahkan ke `/tamu/{token}/formulir` (formulir kosong). Tamu lama diarahkan ke `/tamu/{token}/riwayat` yang memuat tombol "Isi formulir lagi". |
| TAM-16 | \[BARU v0.10\] Formulir tamu lama menampilkan data diri yang terisi otomatis dan terkunci. | Wajib | Kelompok rentan, Nama lengkap, Instansi/perusahaan, Nomor telepon/WhatsApp, dan Alamat diisi dari kunjungan terakhir dengan email yang sama (`check_in_at` terbaru) dan tampil read-only. Saat POST, server mengambil kelima nilai itu dari database lewat `ReturningGuestService`, bukan dari input browser. Kategori, Rincian keperluan, Tanda tangan, dan persetujuan diisi ulang. Kategori yang pernah dilaporkan tetap dapat dipilih \[DEFAULT\]. |
| TAM-17 | \[BARU v0.10\] Tamu dapat melihat riwayat kunjungannya sendiri. | Wajib | `/tamu/{token}/riwayat` hanya dapat dibuka dengan email terverifikasi di session dan hanya menampilkan kunjungan dengan `email` yang sama, urut terbaru, paginasi `GUEST_HISTORY_PER_PAGE`. Per kunjungan tampil: tanggal dan jam datang, lokasi, kategori dan rincian keperluan, label status, departemen tujuan, orang yang ditemui, dan hasil (hanya bila Selesai). Nomor telepon, alamat, tanda tangan, dan alasan batal tidak ditampilkan. Tersedia tombol "Isi formulir lagi" dan "Ganti akun". |

### 6.6.1 Data yang dicatat per kunjungan \[DIUBAH v0.10\]

| No | Data | Sumber | Wajib | Kolom database |
| --- | --- | --- | --- | --- |
| 1 | Nomor urut | Otomatis (TAM-10) | – | `daily_number` (+ `visit_date`) |
| 2 | Email | Akun Google terverifikasi (TAM-11) | Ya | `email`, `email_verified_at` |
| 3 | Kategori keperluan | Dipilih tamu dari daftar KAT-1 | Ya | `purpose_category_id`, `purpose_category_name` |
| 4 | Rincian keperluan | Diisi tamu | Ya | `purpose` |
| 5 | Orang yang ditemui | Dipilih resepsionis dari daftar petugas saat menugaskan (RSP-2); tidak diisi tamu | – | `served_by_staff_id` |
| 6 | Kelompok rentan | Dipilih tamu (tamu lama: dari kunjungan terakhir) | Ya | `vulnerable_group` |
| 7 | Nama lengkap | Diisi tamu (tamu lama: dari kunjungan terakhir) | Ya | `name` |
| 8 | Instansi/perusahaan | Diisi tamu (tamu lama: dari kunjungan terakhir) | Ya \[DIUBAH v0.10\] | `organization` |
| 9 | Nomor telepon/WhatsApp | Diisi tamu (tamu lama: dari kunjungan terakhir) | Ya | `phone` |
| 10 | Alamat | Diisi tamu (tamu lama: dari kunjungan terakhir) | Ya | `address` |
| 11 | Jam datang | Otomatis dari waktu server | – | `check_in_at` |
| 12 | Tanda tangan | Digambar tamu setiap kunjungan (TAM-9) | Ya | `visit_signatures.image` |

Selain data di atas, sistem menyimpan data proses yang tidak diisi tamu: lokasi dan QR (dari token), saran departemen (dari kategori), departemen tujuan dan riwayat penugasan, akun PIC yang menerima, hasil, status dan waktu-waktu perubahannya, waktu persetujuan, dan alamat IP. Data lain **JANGAN** ditambahkan ke formulir.

## 6.7 Resepsionis — Tamu Aktif dan Penugasan \[BARU v0.10\]

| ID | Kebutuhan | Prioritas | Kriteria Penerimaan |
| --- | --- | --- | --- |
| RSP-1 | `/admin/tamu` untuk Resepsionis menampilkan kunjungan `waiting` dan `processing` dari QR miliknya, urut dari yang datang paling awal. | Wajib | Urutan `check_in_at ASC`. Kunjungan dari hari sebelumnya tetap tampil. Paginasi 25 baris. Kolom: nomor urut, nama, instansi, kelompok rentan, kategori keperluan, saran departemen, departemen dan orang yang ditemui, jam datang, lama menunggu, label status, tombol aksi. Akun tanpa QR melihat pesan "Akun ini belum dihubungkan ke QR mana pun." |
| RSP-2 | Resepsionis menugaskan kunjungan ke departemen dan memilih orang yang ditemui. | Wajib | Tombol "Tugaskan" pada kunjungan `waiting` membuka modal pilihan departemen: hanya departemen aktif di lokasi kunjungan yang memiliki akun PIC aktif; saran departemen dari kategori terpilih otomatis bila memenuhi syarat. Setelah departemen dipilih, resepsionis wajib memilih orang yang ditemui dari daftar petugas aktif departemen tersebut (PTG-1); departemen tanpa petugas aktif tidak dapat dipilih. Menyimpan `assigned_department_id`, `assigned_at`, `assigned_by`, served\_by\_staff\_id (orang yang ditemui), dan satu baris `visit_assignments`. Status tetap `waiting`, label menjadi "Menunggu PIC". |
| RSP-3 | Resepsionis dapat menugaskan ulang selama PIC belum menerima. | Wajib | Tombol berubah menjadi "Tugaskan ulang" untuk kunjungan "Menunggu PIC". Penugasan ulang dapat mengganti departemen, orang yang ditemui, atau keduanya. Penugasan lama tetap tercatat di `visit_assignments`. Jika kunjungan sudah diterima PIC: HTTP 409 "Kunjungan ini sudah diterima PIC. Daftar akan dimuat ulang." |
| RSP-4 | Resepsionis membatalkan kunjungan dengan alasan. | Wajib | Untuk status `waiting` dan `processing`. Alasan wajib dipilih: "Tamu tidak hadir/pergi", "Salah lokasi", "Data ganda", atau "Lainnya" (wajib diisi teks, maks. 255 karakter). Mengisi `cancelled_at`, `cancel_reason`, `closed_by`. |
| RSP-5 | Aksi dilakukan langsung dari daftar tanpa pindah halaman. | Wajib | Tombol memanggil endpoint AJAX (Bab 10); baris diperbarui tanpa reload; tombol dinonaktifkan selama request berjalan. |
| RSP-6 | Resepsionis dapat mencari dan memfilter. | Wajib | Pencarian pada nama, nomor telepon, dan instansi. Filter label status: Menunggu, Menunggu PIC, Sedang direview. |
| RSP-7 | Daftar diperbarui otomatis. | Penting | Polling setiap `POLL_INTERVAL_SECONDS` dengan filter aktif. Berhenti saat tab tidak terlihat dan langsung berjalan saat tab aktif lagi. Kunjungan baru disorot sebentar. |
| RSP-8 | Kunjungan yang menunggu lama ditandai. | Penting | Kunjungan `waiting` (dengan atau tanpa penugasan) yang menunggu > `WAITING_ALERT_MINUTES` diberi highlight dan badge "Menunggu lama". Setiap baris menampilkan lama menunggu, misalnya "12 mnt". |
| RSP-9 | \[BARU v0.11\] Pilihan departemen tujuan dan orang yang ditemui di modal Tugaskan dapat dicari. | Penting | Kedua pilihan berupa dropdown yang bisa diketik: tanpa ketikan menampilkan semua pilihan, dengan ketikan menyaring nama yang memuat teks tersebut (tidak peka huruf besar/kecil). Tidak ada hasil → "Tidak ada hasil". Saran departemen tetap terpilih otomatis (RSP-2); mengganti departemen mengosongkan orang yang ditemui. Dapat dipakai dengan keyboard (↑ ↓ Enter Esc) dan layar sentuh. Tanpa library tambahan (A-3). |

## 6.8 PIC — Penugasan dan Hasil \[BARU v0.10\]

| ID | Kebutuhan | Prioritas | Kriteria Penerimaan |
| --- | --- | --- | --- |
| PIC-1 | `/admin/penugasan` menampilkan kunjungan yang ditugaskan ke departemen akun PIC dengan status `waiting` dan `processing`. | Wajib | Urutan `check_in_at ASC`. Dua kelompok: "Penugasan baru" (`waiting`) dan "Sedang direview" (`processing`). Kolom: nomor urut, nama, instansi, kelompok rentan, kategori dan rincian keperluan, orang yang ditemui, lokasi, jam datang, lama menunggu, tombol aksi. Akun tanpa departemen melihat pesan "Akun ini belum dihubungkan ke departemen." |
| PIC-2 | PIC menerima penugasan. | Wajib | Tombol "Terima" langsung menerima penugasan, tanpa memilih petugas karena orang yang ditemui sudah dipilih resepsionis. Transisi `waiting` → `processing`; mengisi `processed_at`, `processed_by` (akun)`.` Jika kunjungan sudah dialihkan ke departemen lain: HTTP 409 "Kunjungan ini sudah dialihkan. Daftar akan dimuat ulang." |
| PIC-3 | PIC menulis hasil dan menyelesaikan kunjungan. | Wajib | Tombol "Selesai" pada kunjungan `processing` membuka modal isian Hasil (wajib, 5–2000 karakter, dengan keterangan "Hasil ini akan terlihat oleh tamu."). Transisi `processing` → `completed`; mengisi `result`, `completed_at`, `closed_by`. Hasil langsung tampil di riwayat tamu (TAM-17). |
| PIC-4 | PIC mengetahui penugasan baru tanpa memuat ulang halaman. | Wajib | Polling setiap `POLL_INTERVAL_SECONDS`. Penugasan baru disorot, jumlahnya tampil di badge menu dan judul tab browser, misalnya "(2) Penugasan". BOLEH: bunyi notifikasi singkat setelah PIC mengaktifkannya lewat tombol di halaman. |
| PIC-5 | PIC melihat detail kunjungan dan riwayat statusnya. | Wajib | Sesuai Bab 13, tanpa gambar tanda tangan. |
| PIC-6 | PIC tidak dapat membatalkan, menolak, atau menugaskan ulang kunjungan. | Wajib | Tidak ada tombol dan server menolak dengan HTTP 403. |

## 6.9 Admin dan Super Admin — Pemantauan, Pembatalan, Buka Kembali \[DIUBAH v0.10\]

| ID | Kebutuhan | Prioritas | Kriteria Penerimaan |
| --- | --- | --- | --- |
| ADM-1 | `/admin/tamu` untuk Admin dan Super Admin menampilkan kunjungan `waiting` dan `processing` di cakupannya. | Wajib | \[DIUBAH v0.11\] Admin dan Super Admin: semua lokasi. Kolom sama dengan RSP-1 ditambah lokasi dan QR. Filter lokasi tampil bila ada lebih dari satu lokasi. Polling dan penanda menunggu lama seperti RSP-7 dan RSP-8. Tidak ada tombol Tugaskan. |
| ADM-2 | Admin dan Super Admin dapat membatalkan kunjungan. | Wajib | Aturan sama dengan RSP-4. |
| ADM-3 | \[DIUBAH v0.11\] Hanya Admin yang dapat membuka kembali kunjungan Selesai atau Dibatalkan, di lokasi mana pun. | Penting | Tombol "Buka Kembali" di detail kunjungan History. Alasan wajib (maks. 255 karakter). Aturan di Bab 7.3. Tercatat di `visit_status_logs` dan audit log (`visit.reopen`). Super Admin tidak memiliki tombol ini \[DEFAULT\]. |
| ADM-5 | \[BARU v0.11\] Admin tidak dibatasi lokasi. | Wajib | `AccessScope` memperlakukan Admin seperti Super Admin untuk data kunjungan dan master data (Bab 12.2). Tidak ada penugasan lokasi Admin (USR-2 dihapus). |
| ADM-4 | Admin dan Super Admin melihat detail lengkap kunjungan. | Wajib | 12 data Bab 6.6.1 termasuk gambar tanda tangan, lokasi, QR, saran departemen, departemen tujuan, orang yang ditemui, hasil, riwayat penugasan, dan timeline status. |

## 6.10 Admin dan Super Admin — History Kunjungan

| ID | Kebutuhan | Prioritas | Kriteria Penerimaan |
| --- | --- | --- | --- |
| HIS-1 | `/admin/history` menampilkan kunjungan `completed` dan `cancelled` di cakupannya. | Wajib | Urutan default `check_in_at DESC`. Paginasi 25 baris. \[DIUBAH v0.10\] Filter status berisi Selesai, Dibatalkan, dan Sedang direview; pilihan Sedang direview menampilkan kunjungan processing (yang tetap tampil juga di Tamu Aktif) agar dapat dicari dan diekspor. |
| HIS-2 | \[DIUBAH v0.10\] Admin dapat mencari dan memfilter. | Wajib | Pencarian teks pada nama, instansi, dan rincian keperluan. Filter: rentang tanggal `check_in_at`, preset periode (Hari ini, 7 hari terakhir, Bulan ini), status, lokasi, kategori keperluan, departemen tujuan, dan kelompok rentan. Filter tersimpan di query string. |
| HIS-3 | \[DIUBAH v0.10\] Filter kategori keperluan memakai dropdown kategori di lokasi dalam cakupan, termasuk kategori nonaktif. | Wajib | Menggantikan pencarian `LIKE` pada keperluan di v0.9. |
| HIS-4 | Admin dapat melihat detail kunjungan dan mengekspor data sesuai cakupan. | Wajib | Ekspor mengikuti filter aktif dan cakupan. Spesifikasi di Bab 12.4. Setiap ekspor tercatat di audit log beserta format, filter, dan jumlah baris. |
| HIS-5 | Tombol **Ekspor** berupa dropdown: CSV, Excel (.xlsx), dan PDF. | Wajib | Parameter `format` hanya menerima `csv`, `xlsx`, `pdf`. Excel dan PDF ditolak bila baris melebihi `EXPORT_RICH_MAX_ROWS`; CSV tidak dibatasi. |

## 6.11 Dashboard \[DIUBAH v0.10\]

| ID | Kebutuhan | Prioritas | Kriteria Penerimaan |
| --- | --- | --- | --- |
| DSH-1 | \[DIUBAH v0.11\] Dashboard Admin menampilkan 5 kartu angka untuk semua lokasi. | Wajib | **Belum Ditugaskan** = `waiting` tanpa departemen; **Menunggu PIC** = `waiting` dengan departemen; **Sedang Direview** = `processing`; **Selesai Hari Ini** = `completed_at` hari ini; **Total Kunjungan Hari Ini** = `check_in_at` hari ini. Hitungan `waiting` mencakup semua tanggal. |
| DSH-2 | Dashboard Admin menampilkan daftar Tamu Aktif ringkas. | Wajib | Maksimal 10 baris teratas dengan tombol Batal seperti ADM-2 dan tautan "Lihat semua". |
| DSH-3 | Dashboard Super Admin menampilkan ringkasan seluruh lokasi. | Wajib | Kartu: Total Kunjungan (semua waktu), Lokasi Aktif, Kunjungan Hari Ini, Tamu Menunggu. Tabel per lokasi: belum ditugaskan, menunggu PIC, sedang direview, selesai hari ini, total hari ini. Grafik batang kunjungan per hari 14 hari terakhir. Daftar 10 kunjungan terbaru. |
| DSH-4 | Seluruh angka bersumber dari database dan mengikuti cakupan peran. | Wajib | Semua query dashboard melewati `AccessScope` (A-6). |
| DSH-5 | \[BARU v0.10\] Resepsionis dan PIC langsung diarahkan ke halaman kerjanya. | Wajib | `/admin` mengarahkan Resepsionis ke `/admin/tamu` dan PIC ke `/admin/penugasan`. |

## 6.12 Pengguna, Autentikasi, dan Audit \[DIUBAH v0.11\]

| ID | Kebutuhan | Prioritas | Kriteria Penerimaan |
| --- | --- | --- | --- |
| USR-1 | \[DIUBAH v0.11\] Super Admin dapat membuat, mengubah, dan menonaktifkan akun staf empat peran, termasuk mengatur ulang password. Admin dapat melakukan hal yang sama untuk akun Resepsionis dan PIC (USR-6). | Wajib | Reset password mengakhiri semua sesi akun tersebut. Field umum: nama akun, email (unik, huruf kecil), peran, password (minimal 8 karakter). Field per peran: Admin → tanpa lokasi (ADM-5); PIC → satu departemen (wajib); Resepsionis → tanpa lokasi (dihubungkan lewat QR-2/QR-6). Akun nonaktif langsung ditolak pada request berikutnya (`EnsureUserIsActive`). |
| USR-2 | \[DIHAPUS v0.11\] Penugasan lokasi Admin. | – | Tidak diimplementasikan lagi; Admin mencakup semua lokasi (ADM-5). |
| USR-3 | Setiap staf dapat mengganti password sendiri. | Penting | Wajib memasukkan password lama. \[DIUBAH v0.10\] Setelah berhasil, semua sesi akun tersebut diakhiri, termasuk sesi yang sedang dipakai; pengguna diarahkan ke halaman login dengan pesan "Password berhasil diganti. Silakan login kembali." Di perangkat lain, sesi akun itu ditolak pada request berikutnya dan diarahkan ke login. |
| USR-4 | Sistem mencegah hilangnya akses Super Admin. | Wajib | Super Admin tidak dapat menonaktifkan atau menurunkan peran dirinya sendiri; sistem menolak perubahan yang membuat jumlah Super Admin aktif menjadi nol. |
| USR-5 | \[BARU v0.10\] Sistem menjaga keterhubungan akun resepsionis dan PIC. | Wajib | Menonaktifkan atau mengubah peran akun resepsionis yang masih terhubung ke QR berstatus `active` atau `inactive` ditolak: "Pindahkan QR ke akun resepsionis lain terlebih dahulu." Satu departemen hanya boleh memiliki satu akun PIC aktif \[DEFAULT\]; akun PIC kedua yang aktif untuk departemen yang sama ditolak: "Departemen ini sudah memiliki akun PIC aktif." |
| USR-6 | \[BARU v0.11\] Admin hanya mengelola akun Resepsionis dan PIC. | Wajib | Pilihan peran di form akun Admin hanya Resepsionis dan PIC; server menolak peran lain (422). Ubah, aktif/nonaktif, reset password, dan buka kunci pada akun Admin atau Super Admin oleh Admin ditolak HTTP 403; akun itu tetap tampil di daftar tanpa tombol aksi. Admin tidak dapat mengubah akunnya sendiri lewat menu Pengguna (ganti password sendiri tetap lewat USR-3). |
| AUTH-1 | Login dengan email dan password; akun dikunci sementara setelah beberapa kali gagal. | Wajib | Setelah `LOGIN_MAX_ATTEMPTS` gagal berturut-turut, `locked_until` = sekarang + `LOGIN_LOCK_MINUTES`. Pesan gagal selalu "Email atau password salah." Login sukses mereset `failed_logins` dan meregenerasi ID sesi. |
| AUTH-2 | \[DIUBAH v0.10\] Setiap request staf divalidasi di server berdasarkan cakupan perannya. | Wajib | Lihat Bab 12.2. Berlaku untuk halaman, AJAX, gambar tanda tangan, dan ekspor. |
| AUTH-3 | Logout dan batas waktu sesi. | Wajib | Logout lewat POST dengan CSRF. Sesi berakhir setelah `SESSION_LIFETIME` menit tanpa aktivitas. |
| AUTH-4 | \[DIUBAH v0.11\] Super Admin dapat membuka kunci akun yang terkunci; Admin hanya untuk akun Resepsionis dan PIC (USR-6). | Wajib | Tombol "Buka kunci" pada akun yang `locked_until`-nya masih berlaku; mereset `failed_logins` dan mengosongkan `locked_until`. Tercatat di audit log (`user.unlock`). |
| AUD-1 | Sistem mencatat audit log untuk aksi penting. | Wajib | Daftar aksi di Bab 12.3. Audit log tidak dapat diubah atau dihapus lewat aplikasi. |
| AUD-2 | \[DIUBAH v0.11\] Admin dan Super Admin dapat melihat audit log. | Wajib | Filter tanggal, akun, dan jenis aksi; paginasi 50 baris; urut terbaru. |

# 7. Aturan Status Kunjungan (State Machine) \[DIUBAH v0.10\]

Empat status database tetap sama dengan v0.9; penugasan ke departemen dicatat sebagai data tambahan pada status `waiting`, bukan status baru.

## 7.1 Transisi yang diizinkan

| Dari | Ke | Aksi | Siapa | Field yang diisi |
| --- | --- | --- | --- | --- |
| (baru) | `waiting` | Kirim formulir | Tamu | `check_in_at`, `visit_date`, `daily_number`, `consent_at`, `email`, `email_verified_at` |
| `waiting` | `waiting` (penugasan) | Tugaskan / Tugaskan ulang | Resepsionis pemilik QR | `assigned_department_id`, `assigned_at`, `assigned_by`, served\_by\_staff\_id (orang yang ditemui), + satu baris `visit_assignments`. Bukan perubahan status: tidak dicatat di `visit_status_logs`. |
| `waiting` (sudah ditugaskan) | `processing` | Terima | PIC departemen yang ditugaskan | `processed_at`, `processed_by` |
| `processing` | `completed` | Selesai (hasil wajib) | PIC departemen yang ditugaskan | `result`, `completed_at`, `closed_by` |
| `waiting` | `cancelled` | Batal | Resepsionis pemilik QR, Admin, Super Admin | `cancelled_at`, `cancel_reason`, `closed_by` |
| `processing` | `cancelled` | Batal | Resepsionis pemilik QR, Admin, Super Admin | `cancelled_at`, `cancel_reason`, `closed_by` |
| `completed` / `cancelled` | status sebelum ditutup | Buka Kembali | Hanya Admin | Lihat 7.3 |

Transisi lain **ditolak** server dengan HTTP 422, termasuk: `waiting` → `completed`, `waiting` tanpa penugasan → `processing`, penugasan pada kunjungan `processing`, `completed`, atau `cancelled`, serta perubahan ke status Draf (tidak ada).

&#91;embedded content: status kunjungan · 5 label, 4 aksi, buka kembali\]

Garis putus-putus menandai buka kembali oleh Admin; garis ke Dibatalkan berlaku dari setiap status aktif.

## 7.2 Cara menjalankan perubahan (wajib atomik)

Semua perubahan status dan penugasan dijalankan oleh method di `VisitService`, masing-masing di dalam satu `DB::transaction`:

1. Jalankan update bersyarat lewat query, bukan `$model->save()`: `GuestVisit::query()->forUser($user)->whereKey($id)->where('status', $from)->update([...])`. Kondisi tambahan per aksi:
   - Tugaskan: `where('status', 'waiting')`.
   - Terima: `where('status', 'waiting')->where('assigned_department_id', $user->department_id)`.
   - Selesai: `where('status', 'processing')->where('assigned_department_id', $user->department_id)`.
2. Jika baris terpengaruh = 0: batalkan transaksi, lalu tentukan penyebabnya.
   - Kunjungan tidak ada di cakupan pengguna dan tidak pernah ditugaskan ke departemen PIC tersebut → HTTP 404 dan audit `access.denied`.
   - Kunjungan ada di cakupan tetapi statusnya sudah berubah → HTTP 409 "Status tamu ini sudah diubah oleh petugas lain. Daftar akan dimuat ulang."
   - Khusus PIC: kunjungan pernah ditugaskan ke departemennya (ada di `visit_assignments`) tetapi sudah dialihkan → HTTP 409 sesuai PIC-2, tanpa audit `access.denied`.
3. Jika berhasil: insert `visit_status_logs` (`from_status`, `to_status`, `changed_by`, `staff_member_id` untuk aksi PIC, `note`) atau `visit_assignments` untuk penugasan, lalu commit.

Pola ini mencegah dua petugas memproses kunjungan yang sama secara bersamaan.

## 7.3 Buka kembali (ADM-3)

- Status tujuan = `from_status` pada log terakhir yang menutup kunjungan tersebut (`waiting` atau `processing`).
- Kolom `completed_at`, `cancelled_at`, `cancel_reason`, dan `closed_by` dikosongkan. Nilai lamanya disimpan di `note` log status dan di `detail` audit log.
- Penugasan (`assigned_department_id`), penerimaan (`processed_*`), dan `result` dipertahankan. Jika kunjungan kembali ke `processing`, PIC dapat memperbarui hasil saat menekan Selesai lagi; hasil lama tercatat di `note` log status saat diganti.
- Alasan buka kembali wajib diisi (maks. 255 karakter).

# 8. Alur Pengguna Utama (Key User Flows) \[DIUBAH v0.10\]

Satu kunjungan berjalan melalui tujuh tahap: persiapan, pindai QR, verifikasi Google, isi formulir, tercatat, penugasan, pelayanan, lalu hasil kembali ke riwayat tamu.

## 8.1 Tahap 0 — Persiapan (Super Admin dan Admin)

1. Super Admin membuat akun Admin. \[DIUBAH v0.11\] Langkah berikutnya dikerjakan Admin; Super Admin hanya turun tangan saat darurat.
2. Admin membuat lokasi, misalnya "Kantor Dinas Jl. Ahmad Yani", lalu departemen di lokasi itu.
3. Admin membuat akun resepsionis per pos dan akun PIC per departemen.
4. Admin membuat QR untuk tiap titik masuk dan memilih akun resepsionis pemiliknya, lalu mencetak QR dan menempelnya di titik masuk.
5. Admin mengisi kategori keperluan (dengan saran departemen) dan daftar petugas setiap departemen di semua lokasi.

## 8.2 Tahap 1–4 — Tamu mendaftar

1. Tamu memindai QR dan membuka `/tamu/{token}`. Sistem memvalidasi token, QR, lokasi, dan kategori; jika gagal, tampil halaman TAM-6.
2. Tamu menekan "Pilih akun Google" dan memilih akun; server memverifikasi email.
3. Sistem mengecek otomatis apakah email pernah tercatat:
   - **Tamu baru** → formulir kosong.
   - **Tamu lama** → halaman riwayat kunjungan, lalu tombol "Isi formulir lagi" membuka formulir dengan data diri terisi dan terkunci.
4. Tamu memilih kategori keperluan, menulis rinciannya, melengkapi data (bila tamu baru), membubuhkan tanda tangan, mencentang persetujuan, lalu menekan Kirim.
5. Sistem mencatat jam datang dari server, memberi nomor urut, dan menyimpan status **Menunggu**.
6. Tamu melihat halaman konfirmasi berisi nomor urut dan jam datang.
7. Kunjungan langsung muncul di Tamu Aktif akun resepsionis pemilik QR tersebut.

**Alur alternatif:** QR tidak aktif → halaman TAM-6. Validasi gagal → formulir tampil ulang dengan pesan. Melebihi rate limit → "Terlalu banyak percobaan, coba lagi beberapa menit lagi." Gagal login Google → halaman bantuan (TAM-12).

## 8.3 Tahap 5 — Resepsionis menugaskan

1. Resepsionis melihat kunjungan baru di Tamu Aktif beserta lama menunggunya.
2. Resepsionis menekan "Tugaskan", meninjau saran departemen dari kategori, memilih departemen dan orang yang ditemui (petugas yang akan mereview tamu), lalu menekan "Tugaskan".
3. Label kunjungan berubah menjadi **Menunggu PIC**, dan penugasan muncul di daftar Penugasan PIC departemen tersebut.

## 8.4 Tahap 6 — PIC melayani

1. PIC melihat penugasan baru yang disorot di daftar Penugasan.
2. PIC menekan "Terima"**; status menjadi Sedang direview**.
3. Orang yang ditemui mereview dan menangani keperluan tamu, memakai akun PIC departemennya.
4. PIC menekan "Selesai", menulis hasil, lalu menyimpan; status menjadi **Selesai** dan kunjungan pindah ke History.

## 8.5 Tahap 7 — Hasil kembali ke tamu

1. Kapan pun tamu memindai QR lagi dan login dengan akun Google yang sama, sistem mengarahkannya ke riwayat kunjungan.
2. Riwayat menampilkan hasil yang ditulis PIC beserta departemen dan orang yang ditemui.

## 8.6 Jalur samping

- **PIC tidak merespons:** resepsionis menghubungi PIC lewat WhatsApp secara manual di luar sistem, lalu dapat menugaskan ulang ke departemen lain selama PIC belum menerima (RSP-3).
- **Pembatalan:** resepsionis pemilik QR, Admin, atau Super Admin menekan "Batal", memilih alasan, dan kunjungan menjadi **Dibatalkan** lalu masuk History.
- **Buka kembali:** Admin membuka detail kunjungan di History, menekan "Buka Kembali", dan menulis alasan; kunjungan kembali ke status sebelum ditutup.
- **Percobaan akses di luar cakupan:** staf yang mengubah ID atau parameter di URL/request ke data di luar cakupannya menerima HTTP 404 tanpa data apa pun, dan sistem mencatat `access.denied` di audit log.

# 9. Model Data \[DIUBAH v0.10\]

Skema v0.10 menambah empat tabel (`departments`, `purpose_categories`, `staff_members`, `visit_assignments`), menghapus `emergency_passes`, dan menambah kolom penugasan serta hasil di `guest_visits`.

## 9.1 Relasi

- `locations` 1—N `departments`, `qr_codes`, `purpose_categories`, `guest_visits`, `visit_counters`.
- \[DIHAPUS v0.11\] `users` (peran `admin`) N—N `locations` melalui `user_locations`. Tabel tetap ada tetapi tidak dipakai aplikasi (lihat `docs/DECISIONS.md`).
- `users` (peran `pic`) N—1 `departments` melalui `users.department_id`.
- `users` (peran `receptionist`) 1—N `qr_codes` melalui `qr_codes.receptionist_id`.
- `departments` 1—N `staff_members`; `departments` 0..1—N `purpose_categories` (saran departemen).
- `qr_codes` 1—N `guest_visits`; `purpose_categories` 1—N `guest_visits`.
- `guest_visits` N—0..1 `departments` (departemen tujuan); `guest_visits` N—0..1 `staff_members` (orang yang ditemui, dipilih resepsionis).
- `guest_visits` 1—N `visit_assignments`, 1—N `visit_status_logs`, 1—1 `visit_signatures`.
- `users` 1—N `audit_logs`.
- Model Eloquent: `User`, `Location`, `Department`, `QrCode`, `PurposeCategory`, `StaffMember`, `GuestVisit`, `VisitAssignment`, `VisitStatusLog`, `VisitSignature`, `AuditLog`. `user_locations` adalah pivot (`belongsToMany` dengan `withPivot('assigned_by', 'assigned_at')`). `visit_counters` tanpa model (diakses lewat `DB` di `VisitService`). `Location` memakai `SoftDeletes`.

## 9.2 Skema (DDL acuan)

Skema dibuat sebagai migrasi Laravel, satu migrasi per tabel, dengan urutan: `locations`, `departments`, `users`, `user_locations`, `qr_codes`, `purpose_categories`, `staff_members`, `guest_visits`, `visit_counters`, `visit_signatures`, `visit_assignments`, `visit_status_logs`, `audit_logs`. Migrasi `users` bawaan skeleton disesuaikan agar berjalan setelah `departments`, atau kolom `users.department_id` beserta foreign key-nya ditambahkan lewat migrasi terpisah setelah `departments` dibuat.

DDL di bawah adalah **hasil akhir acuan** yang harus dihasilkan migrasi, bukan file SQL yang dijalankan langsung. Nama index dan constraint boleh mengikuti konvensi Laravel. Setelah sebuah migrasi pernah dijalankan di suatu database, **JANGAN** mengeditnya; buat migrasi baru.

```sql
-- DDL ACUAN v0.10: hasil akhir yang harus dihasilkan migrasi Laravel.
CREATE TABLE locations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  description VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  deleted_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_locations_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE departments (                -- [BARU v0.10]
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_departments_loc_name (location_id, name),
  CONSTRAINT fk_dep_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('super_admin','admin','receptionist','pic') NOT NULL,   -- [DIUBAH v0.10]
  department_id BIGINT UNSIGNED NULL,     -- [BARU v0.10] wajib untuk pic, NULL untuk peran lain
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  failed_logins TINYINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_department (department_id),
  CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_locations (              -- tidak dipakai sejak v0.11 (ADM-5)
  user_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NOT NULL,
  assigned_by BIGINT UNSIGNED NULL,
  assigned_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, location_id),
  KEY idx_user_locations_location (location_id),
  CONSTRAINT fk_ul_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ul_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,
  CONSTRAINT fk_ul_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE qr_codes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id BIGINT UNSIGNED NOT NULL,
  receptionist_id BIGINT UNSIGNED NOT NULL,  -- [BARU v0.10] akun resepsionis pemilik QR
  token CHAR(64) NOT NULL,
  label VARCHAR(100) NULL,
  status ENUM('active','inactive','revoked') NOT NULL DEFAULT 'active',
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  revoked_at DATETIME NULL,
  last_used_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_qr_token (token),
  KEY idx_qr_location (location_id),
  KEY idx_qr_receptionist (receptionist_id),
  CONSTRAINT fk_qr_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT,
  CONSTRAINT fk_qr_receptionist FOREIGN KEY (receptionist_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_qr_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purpose_categories (          -- [BARU v0.10]
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  suggested_department_id BIGINT UNSIGNED NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pc_loc_name (location_id, name),
  CONSTRAINT fk_pc_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT,
  CONSTRAINT fk_pc_department FOREIGN KEY (suggested_department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE staff_members (               -- [BARU v0.10] daftar petugas per departemen
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  department_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_staff_dep_name (department_id, name),
  CONSTRAINT fk_staff_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE guest_visits (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id BIGINT UNSIGNED NOT NULL,
  qr_code_id BIGINT UNSIGNED NOT NULL,
  visit_date DATE NOT NULL,
  daily_number INT UNSIGNED NOT NULL,
  email VARCHAR(254) NOT NULL,              -- [DIUBAH v0.10] selalu dari Google
  email_verified_at DATETIME NOT NULL,      -- [DIUBAH v0.10]
  purpose_category_id BIGINT UNSIGNED NOT NULL,      -- [BARU v0.10]
  purpose_category_name VARCHAR(100) NOT NULL,       -- [BARU v0.10] salinan nama saat kunjungan
  suggested_department_id BIGINT UNSIGNED NULL,      -- [BARU v0.10] salinan saran dari kategori
  purpose VARCHAR(500) NOT NULL,            -- rincian keperluan
  -- [DIHAPUS v0.10] person_to_meet; orang yang ditemui = served_by_staff_id         
  vulnerable_group VARCHAR(30) NOT NULL,    -- [BARU v0.10] nilai enum VulnerableGroup
  name VARCHAR(100) NOT NULL,
  organization VARCHAR(150) NOT NULL,       -- [DIUBAH v0.10] wajib
  phone VARCHAR(20) NOT NULL,
  address VARCHAR(255) NOT NULL,
  status ENUM('waiting','processing','completed','cancelled') NOT NULL DEFAULT 'waiting',
  check_in_at DATETIME NOT NULL,
  assigned_department_id BIGINT UNSIGNED NULL,       -- [BARU v0.10] departemen tujuan saat ini
  assigned_at DATETIME NULL,
  assigned_by BIGINT UNSIGNED NULL,
  processed_at DATETIME NULL,
  processed_by BIGINT UNSIGNED NULL,        -- akun PIC yang menerima
  served_by_staff_id BIGINT UNSIGNED NULL,  -- [BARU v0.10] orang yang ditemui, dipilih resepsionis
  result TEXT NULL,                         -- [BARU v0.10] hasil, terlihat oleh tamu
  completed_at DATETIME NULL,
  cancelled_at DATETIME NULL,
  cancel_reason VARCHAR(255) NULL,
  closed_by BIGINT UNSIGNED NULL,
  consent_at DATETIME NOT NULL,
  ip_address VARCHAR(45) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_visits_loc_date_number (location_id, visit_date, daily_number),
  KEY idx_visits_loc_status_checkin (location_id, status, check_in_at),
  KEY idx_visits_qr_status_checkin (qr_code_id, status, check_in_at),
  KEY idx_visits_dep_status_checkin (assigned_department_id, status, check_in_at),
  KEY idx_visits_email_checkin (email, check_in_at),
  KEY idx_visits_status_checkin (status, check_in_at),
  KEY idx_visits_checkin (check_in_at),
  KEY idx_visits_completed (completed_at),
  CONSTRAINT fk_visit_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT,
  CONSTRAINT fk_visit_qr FOREIGN KEY (qr_code_id) REFERENCES qr_codes(id) ON DELETE RESTRICT,
  CONSTRAINT fk_visit_category FOREIGN KEY (purpose_category_id) REFERENCES purpose_categories(id) ON DELETE RESTRICT,
  CONSTRAINT fk_visit_suggested_dep FOREIGN KEY (suggested_department_id) REFERENCES departments(id) ON DELETE RESTRICT,
  CONSTRAINT fk_visit_assigned_dep FOREIGN KEY (assigned_department_id) REFERENCES departments(id) ON DELETE RESTRICT,
  CONSTRAINT fk_visit_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_visit_processed_by FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_visit_staff FOREIGN KEY (served_by_staff_id) REFERENCES staff_members(id) ON DELETE RESTRICT,
  CONSTRAINT fk_visit_closed_by FOREIGN KEY (closed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE visit_counters (
  location_id BIGINT UNSIGNED NOT NULL,
  visit_date DATE NOT NULL,
  last_number INT UNSIGNED NOT NULL,
  PRIMARY KEY (location_id, visit_date),
  CONSTRAINT fk_vc_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE visit_signatures (
  visit_id BIGINT UNSIGNED NOT NULL,
  image MEDIUMBLOB NOT NULL,
  mime_type VARCHAR(20) NOT NULL DEFAULT 'image/png',
  size_bytes INT UNSIGNED NOT NULL,
  sha256 CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (visit_id),
  CONSTRAINT fk_sig_visit FOREIGN KEY (visit_id) REFERENCES guest_visits(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE visit_assignments (           -- [BARU v0.10] riwayat penugasan (departemen dan orang yang ditemui)
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  visit_id BIGINT UNSIGNED NOT NULL,
  department_id BIGINT UNSIGNED NOT NULL,
  assigned_by BIGINT UNSIGNED NULL,
  assigned_at DATETIME NOT NULL,
  staff_member_id BIGINT UNSIGNED NOT NULL,  -- orang yang ditemui yang dipilih
  PRIMARY KEY (id),
  KEY idx_va_visit (visit_id),
  KEY idx_va_department (department_id),
  KEY idx_va_staff (staff_member_id),
  CONSTRAINT fk_va_visit FOREIGN KEY (visit_id) REFERENCES guest_visits(id) ON DELETE RESTRICT,
  CONSTRAINT fk_va_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
  CONSTRAINT fk_va_staff FOREIGN KEY (staff_member_id) REFERENCES staff_members(id) ON DELETE RESTRICT,
  CONSTRAINT fk_va_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE visit_status_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  visit_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NOT NULL,
  from_status ENUM('waiting','processing','completed','cancelled') NULL,
  to_status ENUM('waiting','processing','completed','cancelled') NOT NULL,
  changed_by BIGINT UNSIGNED NULL,          -- NULL = dibuat oleh tamu
  staff_member_id BIGINT UNSIGNED NULL,     -- [BARU v0.10] salinan orang yang ditemui saat aksi
  note VARCHAR(2500) NULL,                  -- [DIUBAH v0.10] cukup untuk menyimpan hasil lama
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_vsl_visit (visit_id),
  CONSTRAINT fk_vsl_visit FOREIGN KEY (visit_id) REFERENCES guest_visits(id) ON DELETE RESTRICT,
  CONSTRAINT fk_vsl_changed_by FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_vsl_staff FOREIGN KEY (staff_member_id) REFERENCES staff_members(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(50) NOT NULL,
  entity_type VARCHAR(50) NULL,
  entity_id BIGINT UNSIGNED NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  detail JSON NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_audit_created (created_at),
  KEY idx_audit_user (user_id),
  KEY idx_audit_action (action),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Catatan:**

- &#91;DIHAPUS v0.10\] Tabel `emergency_passes` serta kolom `email_source` dan `email_authorized_by` tidak dibuat.
- `purpose_category_name` dan `suggested_department_id` di `guest_visits` adalah salinan saat kunjungan dibuat, agar History tetap akurat bila kategori diubah kemudian.
- `visit_signatures.image` harus `MEDIUMBLOB`. Jika `Blueprint` tidak menyediakannya, ubah dengan `DB::statement('ALTER TABLE visit_signatures MODIFY image MEDIUMBLOB NOT NULL')` di migrasi yang sama.
- Kolom ENUM dibuat dengan `$table->enum()`; `detail` dengan `$table->json()` (cast `array`). Model tanpa `created_at`/`updated_at` standar memakai `$timestamps = false`.
- Tidak ada operasi `DELETE` terhadap `guest_visits`, `visit_signatures`, `visit_counters`, `visit_assignments`, `visit_status_logs`, `audit_logs`, `departments`, `purpose_categories`, dan `staff_members`. Model terkait menolak penghapusan lewat event `deleting` (melempar `LogicException`); `AuditLog`, `VisitStatusLog`, `VisitAssignment`, dan `VisitSignature` juga menolak `updating`.
- Query daftar (Tamu Aktif, Penugasan, History, dashboard, ekspor CSV, riwayat tamu) **JANGAN** JOIN ke `visit_signatures`. Gambar hanya dibaca route tanda tangan dan `ExportService` (Excel/PDF) lewat query terpisah per batch.
- Perkiraan kapasitas: satu tanda tangan 5–30 KB, sehingga 50.000 kunjungan membutuhkan sekitar 0,25–1,5 GB.

## 9.3 Pengambilan nomor urut (TAM-10)

Dijalankan di dalam transaksi TAM-4, sebelum insert `guest_visits`:

```sql
INSERT INTO visit_counters (location_id, visit_date, last_number)
VALUES (:location_id, :visit_date, LAST_INSERT_ID(1))
ON DUPLICATE KEY UPDATE last_number = LAST_INSERT_ID(last_number + 1);

SELECT LAST_INSERT_ID();  -- nilai ini menjadi daily_number
```

Jalankan dengan `DB::insert()` dan `DB::selectOne('SELECT LAST_INSERT_ID() AS n')` pada koneksi dan transaksi yang sama, dengan binding parameter. **JANGAN** menggantinya dengan `increment()`, `max() + 1`, atau `upsert()`. Tes nomor urut dijalankan di MySQL/MariaDB.

## 9.4 Data tamu lama \[BARU v0.10\]

`ReturningGuestService` adalah satu-satunya tempat yang membaca `guest_visits` tanpa `AccessScope`, dengan kunci email terverifikasi dari session:

- `isReturning(email)`: `GuestVisit::where('email', $email)->exists()`.
- `latestProfile(email)`: kunjungan dengan `check_in_at` terbaru, hanya kolom `vulnerable_group`, `name`, `organization`, `phone`, `address`.
- `history(email)`: daftar kunjungan untuk TAM-17 dengan eager loading lokasi, departemen tujuan, dan petugas, tanpa kolom telepon, alamat, dan alasan batal.

Email **selalu** berasal dari session (TAM-11), tidak pernah dari parameter request.

# 10. Daftar Route & Endpoint \[DIUBAH v0.10\]

Seluruh route ada di `routes/web.php`, diberi nama (notasi titik, misalnya `admin.visits.assign`), dan dikelompokkan per peran lewat middleware `role:<peran>[,<peran>]`.

**Konvensi respons AJAX:** JSON `{"ok": true, "data": {...}}` atau `{"ok": false, "error": {"code": "STATUS_CONFLICT", "message": "..."}}`. Token CSRF lewat header `X-CSRF-TOKEN`. Request AJAX WAJIB mengirim `Accept: application/json` agar request tanpa sesi menerima HTTP 401 JSON. Parameter `{id}` dicari lewat `AccessScope` (Bab 12.2), **bukan** implicit route model binding.

## 10.1 Publik (tanpa login)

| Method | Path | Keterangan |
| --- | --- | --- |
| GET | `/tamu/{token}` | \[DIUBAH v0.10\] Langkah 1 bila session belum memuat email terverifikasi; jika sudah, redirect ke formulir (tamu baru) atau riwayat (tamu lama) sesuai TAM-15; atau halaman TAM-6 (404). |
| POST | `/tamu/{token}/verifikasi-google` | AJAX: terima ID token Google (CSRF, rate limit), verifikasi, simpan email di session (TAM-11). Respons memuat URL tujuan berikutnya. |
| POST | `/tamu/{token}/ganti-akun` | Hapus hasil verifikasi di session, kembali ke Langkah 1. |
| GET | `/tamu/{token}/bantuan` | Halaman "Tidak bisa login Google?" (TAM-12). |
| GET | `/tamu/{token}/riwayat` | \[BARU v0.10\] Riwayat kunjungan tamu (TAM-17). Wajib email terverifikasi di session. |
| GET | `/tamu/{token}/formulir` | \[BARU v0.10\] Formulir kosong (tamu baru) atau terisi dan terkunci (tamu lama, TAM-16). Wajib email terverifikasi di session. |
| POST | `/tamu/{token}/formulir` | \[DIUBAH v0.10\] Simpan kunjungan (CSRF, rate limit, honeypot, token sekali pakai TAM-14). Sukses → redirect 303 ke halaman terima kasih. |
| GET | `/tamu/{token}/terima-kasih` | Halaman konfirmasi dari flash session. |
| GET | `/login` | Form login staf. |
| POST | `/login` | Proses login (CSRF, rate limit per IP). |

&#91;DIHAPUS v0.10\] `POST /tamu/{token}` (pindah ke `/tamu/{token}/formulir`), `GET` dan `POST /tamu-darurat/{token}`.

## 10.2 Semua staf (`auth`, `active`)

| Method | Path | Keterangan |
| --- | --- | --- |
| POST | `/logout` | Logout. |
| GET | `/admin` | Dashboard Admin/Super Admin; Resepsionis diarahkan ke `/admin/tamu`, PIC ke `/admin/penugasan` (DSH-5). |
| GET | `/admin/kunjungan/{id}` | Detail kunjungan sesuai cakupan; isi mengikuti peran (Bab 13). |
| GET | `/admin/password` | Form ganti password sendiri. |
| POST | `/admin/password` | Simpan password baru. |

## 10.3 Resepsionis, Admin, Super Admin (`role:receptionist,admin,super_admin`)

| Method | Path | Keterangan |
| --- | --- | --- |
| GET | `/admin/tamu` | Tamu Aktif. Query: `q`, `status` (`menunggu`, `menunggu_pic`, `direview`), `location_id`. |
| GET | `/admin/tamu/data` | JSON untuk polling; filter sama; berisi daftar, jumlah per label status, dan `server_time`. |
| POST | `/admin/kunjungan/{id}/batal` | AJAX: → `cancelled`. Body: `reason`, `reason_text`. |

## 10.4 Resepsionis (`role:receptionist`)

| Method | Path | Keterangan |
| --- | --- | --- |
| GET | `/admin/kunjungan/{id}/departemen` | \[BARU v0.10\] AJAX: daftar departemen yang dapat dipilih untuk kunjungan ini beserta saran departemen dan daftar petugas aktif tiap departemen. |
| POST | `/admin/kunjungan/{id}/tugaskan` | \[BARU v0.10\] AJAX: tugaskan atau tugaskan ulang. Body: `department_id`, staff\_member\_id (orang yang ditemui). |

## 10.5 PIC (`role:pic`)

| Method | Path | Keterangan |
| --- | --- | --- |
| GET | `/admin/penugasan` | \[BARU v0.10\] Daftar penugasan departemen (PIC-1). |
| GET | `/admin/penugasan/data` | \[BARU v0.10\] JSON untuk polling, berisi daftar, jumlah penugasan baru, dan `server_time`. |
| POST | `/admin/kunjungan/{id}/terima` | \[BARU v0.10\] AJAX: `waiting` → `processing`. `Tanpa body`. |
| POST | `/admin/kunjungan/{id}/selesai` | \[DIUBAH v0.10\] AJAX: `processing` → `completed`. Body: `result`. |

&#91;DIHAPUS v0.10\] `POST /admin/kunjungan/{id}/proses`.

## 10.6 Admin dan Super Admin (`role:admin,super_admin`)

| Method | Path | Keterangan |
| --- | --- | --- |
| GET | `/admin/kunjungan/{id}/tanda-tangan` | Gambar PNG tanda tangan. Header: `Content-Type: image/png`, `Cache-Control: private, no-store`, `X-Content-Type-Options: nosniff`. |
| GET | `/admin/history` | History. Query: `q`, `from`, `to`, `period`, `status`, `location_id`, `purpose_category_id`, `department_id`, `vulnerable_group`, `page`. |
| GET | `/admin/history/export` | Unduh ekspor dengan filter yang sama. Query tambahan: `format` (`csv`, `xlsx`, `pdf`; default `csv`). |
| GET | `/admin/kategori-keperluan` | \[BARU v0.10\] Daftar kategori keperluan per lokasi. Query: `location_id`. |
| GET / POST | `/admin/kategori-keperluan/tambah` | \[BARU v0.10\] Form dan simpan kategori. |
| GET / POST | `/admin/kategori-keperluan/{id}/ubah` | \[BARU v0.10\] Form dan simpan perubahan. |
| POST | `/admin/kategori-keperluan/{id}/status` | \[BARU v0.10\] Aktifkan/nonaktifkan. |
| GET | `/admin/petugas` | \[BARU v0.10\] Daftar petugas per departemen. Query: `department_id`. |
| GET / POST | `/admin/petugas/tambah` | \[BARU v0.10\] Form dan simpan petugas. |
| GET / POST | `/admin/petugas/{id}/ubah` | \[BARU v0.10\] Form dan simpan perubahan. |
| POST | `/admin/petugas/{id}/status` | \[BARU v0.10\] Aktifkan/nonaktifkan. |

## 10.7 Khusus Admin (`role:admin`)

| Method | Path | Keterangan |
| --- | --- | --- |
| POST | `/admin/kunjungan/{id}/buka-kembali` | \[DIUBAH v0.10\] AJAX: buka kembali kunjungan (ADM-3). Body: `note`. |

## 10.8 Khusus Super Admin (`role:super_admin`)

| Method | Path | Keterangan |
| --- | --- | --- |
| GET | `/admin/lokasi` | Daftar lokasi. |
| GET / POST | `/admin/lokasi/tambah` | Form dan simpan lokasi baru. |
| GET / POST | `/admin/lokasi/{id}/ubah` | Form dan simpan perubahan lokasi. |
| POST | `/admin/lokasi/{id}/status` | Aktifkan/nonaktifkan lokasi. |
| POST | `/admin/lokasi/{id}/hapus` | Soft delete (ditolak jika lokasi punya data, LOK-3). |
| GET | `/admin/lokasi/{id}/departemen` | \[BARU v0.10\] Daftar departemen lokasi. |
| POST | `/admin/lokasi/{id}/departemen` | \[BARU v0.10\] Tambah departemen. Body: `name`. |
| POST | `/admin/departemen/{id}/ubah` | \[BARU v0.10\] Ubah nama departemen. |
| POST | `/admin/departemen/{id}/status` | \[BARU v0.10\] Aktifkan/nonaktifkan departemen. |
| GET | `/admin/lokasi/{id}/qr` | Daftar QR lokasi. |
| POST | `/admin/lokasi/{id}/qr` | \[DIUBAH v0.10\] Buat QR baru. Body: `label`, `receptionist_id`. |
| POST | `/admin/qr/{id}/resepsionis` | \[BARU v0.10\] Ganti akun resepsionis QR (QR-6). Body: `receptionist_id`. |
| POST | `/admin/qr/{id}/status` | Aktifkan/nonaktifkan QR (ditolak jika `revoked`). |
| POST | `/admin/qr/{id}/regenerate` | Regenerate QR. |
| GET | `/admin/qr/{id}/unduh` | Unduh file QR. Query: `format` (`png` atau `svg`). |
| GET | `/admin/qr/{id}/cetak` | Halaman cetak QR. |
| GET | `/admin/pengguna` | Daftar akun staf. |
| GET / POST | `/admin/pengguna/tambah` | Form dan simpan akun baru. |
| GET / POST | `/admin/pengguna/{id}/ubah` | Ubah data, peran, lokasi (Admin), departemen (PIC), dan password. |
| POST | `/admin/pengguna/{id}/status` | Aktifkan/nonaktifkan akun (USR-4, USR-5). |
| POST | `/admin/pengguna/{id}/buka-kunci` | Buka kunci akun (AUTH-4). |
| GET | `/admin/audit-log` | Daftar audit log. |

## 10.9 Kode status HTTP

| Kondisi | Kode |
| --- | --- |
| Belum login (halaman) | 302 ke `/login` |
| Belum login (AJAX) | 401 |
| Peran tidak berhak atas route (misalnya PIC membuka `/admin/tamu`) | 403 |
| Data berdasarkan ID di luar cakupan, atau tidak ada | 404 (seragam) |
| Token CSRF tidak valid | 419 "Sesi kedaluwarsa, muat ulang halaman." |
| Transisi status atau penugasan tidak diizinkan | 422 |
| Status sudah diubah petugas lain, atau kunjungan sudah dialihkan | 409 |
| Melebihi rate limit | 429 |

Halaman error 403, 404, 419, 429, dan 500 memakai view kustom Bahasa Indonesia di `resources/views/errors/`.

# 11. Aturan Validasi Input \[DIUBAH v0.10\]

Semua validasi dijalankan di server memakai **Form Request** Laravel dengan pesan error eksplisit di `messages()`; validasi HTML5 hanya pelengkap. Trim dan string kosong → `null` ditangani middleware bawaan; spasi ganda dirapikan di `prepareForValidation()`. Aturan khusus berada di `app/Rules/` (`IndonesianPhone`, `ValidSignatureImage`).

## 11.1 Formulir tamu

Urutan baris mengikuti urutan field di formulir.

| Field (label UI) | Wajib | Aturan | Pesan error |
| --- | --- | --- | --- |
| `email` (Email) | Ya | **Tidak pernah** dibaca dari input browser; server memakai email terverifikasi di session (TAM-11). Ditampilkan read-only. | – (kembali ke Langkah 1) |
| `purpose_category_id` (Keperluan) | Ya | ID kategori aktif di lokasi QR. Dropdown dengan opsi pertama "Pilih keperluan". | "Pilih kategori keperluan." |
| `purpose` (Rincian keperluan) | Ya | 3–500 karakter, textarea 3 baris, tampil setelah kategori dipilih. Placeholder: "Contoh: Ingin konsultasi terkait perizinan perdagangan". | "Rincian keperluan wajib diisi (3–500 karakter)." |
| `vulnerable_group` (Kelompok rentan) | Ya | Salah satu nilai enum `VulnerableGroup` \[DEFAULT\]: `bukan` (Bukan kelompok rentan), `lansia` (Lansia), `disabilitas` (Penyandang disabilitas), `ibu_hamil` (Ibu hamil), `tidak_menyebutkan` (Tidak ingin menyebutkan). Tamu lama: diambil dari database (TAM-16). | "Pilih kelompok rentan." |
| `name` (Nama lengkap) | Ya | 2–100 karakter. Tamu lama: diambil dari database. | "Nama lengkap wajib diisi (2–100 karakter)." |
| `organization` (Instansi/perusahaan) | Ya \[DIUBAH v0.10\] | 2–150 karakter. Petunjuk: "Tulis Pribadi jika datang atas nama pribadi." Tamu lama: diambil dari database. | "Instansi/perusahaan wajib diisi (2–150 karakter)." |
| `phone` (Nomor telepon/WhatsApp) | Ya | Terima `08…`, `628…`, `+628…`, nomor kantor berawalan `0`, spasi, `-`, dan tanda kurung. Setelah dibersihkan dan awalan `0` dinormalisasi ke `62`, harus 10–15 digit. Disimpan `62…`, ditampilkan `0…`. `inputmode="tel"`. Tamu lama: diambil dari database. | "Nomor telepon tidak valid. Contoh: 081234567890." |
| `address` (Alamat) | Ya | 5–255 karakter, textarea 2 baris. Placeholder: "Contoh: Jl. Ahmad Yani No. 1, Surabaya". Tamu lama: diambil dari database. | "Alamat wajib diisi (5–255 karakter)." |
| `signature` (Tanda tangan) | Ya | Diawali `data:image/png;base64,`; `base64_decode(..., true)` tidak gagal dan maks. `SIGNATURE_MAX_KB`; terbaca `imagecreatefromstring()` dengan dimensi 200–1200 px × 80–600 px; minimal 50 piksel tidak transparan dan tidak putih. Disimpan PNG hasil re-encode GD. | "Tanda tangan wajib dibubuhkan." |
| `consent` (Persetujuan) | Ya | Harus bernilai `1`. | "Anda perlu menyetujui pernyataan pemrosesan data." |
| `website` (honeypot) | – | Harus kosong. | Tidak ada pesan (TAM-8). |
| `form_token` (token sekali pakai) | Ya | Harus cocok dengan token di session dan belum dipakai (TAM-14). | – (diarahkan ke halaman konfirmasi) |

Untuk tamu lama, Form Request **mengabaikan** input `vulnerable_group`, `name`, `organization`, `phone`, dan `address` dari browser dan menggantinya dengan nilai dari `ReturningGuestService::latestProfile()` sebelum validasi.

## 11.2 Aksi staf

| Field (label UI) | Dipakai di | Aturan | Pesan error |
| --- | --- | --- | --- |
| `department_id` (Departemen tujuan) | Tugaskan (RSP-2, RSP-3) | Wajib; departemen aktif di lokasi kunjungan dan memiliki akun PIC aktif. | "Pilih departemen tujuan." |
| `staff_member_id` (Orang yang ditemui) | Tugaskan (RSP-2, RSP-3) | Wajib; petugas aktif di departemen yang dipilih. | "Pilih orang yang ditemui." |
| `result` (Hasil) | Selesai (PIC-3) | Wajib; 5–2000 karakter setelah trim. | "Hasil wajib diisi (5–2000 karakter)." |
| `reason` (Alasan batal) | Batal (RSP-4, ADM-2) | Wajib; salah satu: `not_present`, `wrong_location`, `duplicate`, `other`. | "Pilih alasan pembatalan." |
| `reason_text` (Keterangan) | Batal | Wajib jika `reason` = `other`; maks. 255 karakter. | "Tulis alasan pembatalan (maks. 255 karakter)." |
| `note` (Alasan buka kembali) | Buka kembali (ADM-3) | Wajib; maks. 255 karakter. | "Alasan buka kembali wajib diisi (maks. 255 karakter)." |

## 11.3 Master data

| Field | Dipakai di | Aturan |
| --- | --- | --- |
| `name` | Lokasi, departemen, kategori, petugas | Wajib, 2–100 karakter; unik sesuai cakupannya (lokasi: global; departemen dan kategori: per lokasi; petugas: per departemen), tidak peka huruf besar/kecil. |
| `suggested_department_id` | Kategori | Opsional; departemen aktif di lokasi yang sama. |
| `sort_order` | Kategori | Angka 0–999; default 0. |
| `receptionist_id` | QR | Wajib; akun aktif berperan `receptionist`. |
| `label` | QR | Opsional, maks. 100 karakter. |
| `role`, `department_id`, `location_ids[]` | Akun staf | `role` salah satu nilai `UserRole`; `department_id` wajib dan aktif bila `pic`, harus kosong untuk peran lain; `location_ids[]` hanya untuk `admin`. Aturan USR-4 dan USR-5 berlaku. |

# 12. Kebutuhan Non-Fungsional

Inti keamanan v0.10 adalah `AccessScope`: satu titik yang menentukan data apa yang boleh disentuh setiap peran, untuk halaman, AJAX, gambar, dan ekspor.

## 12.1 Keamanan aplikasi

- Password di-hash dengan `Hash` facade (bcrypt, `BCRYPT_ROUNDS` minimal 12) lewat cast `hashed` pada model `User`; kolom `password` masuk `$hidden`.
- CSRF aktif untuk semua POST (form lewat `@csrf`, AJAX lewat header `X-CSRF-TOKEN`). Tidak ada route yang dikecualikan.
- Akses database hanya lewat Eloquent atau Query Builder dengan binding (A-5).
- Setiap model mendefinisikan `$fillable` eksplisit; `$guarded = []` dilarang. Data disimpan dari `$request->validated()`, bukan `$request->all()`.
- &#91;BARU v0.10\] Perubahan password mengakhiri semua sesi akun tersebut lewat middleware bawaan Laravel AuthenticateSession (alias auth.session) yang dipasang di grup route staf, lalu sesi aktif di-logout dengan invalidate() dan regenerateToken() (USR-3). Cookie sesi `HttpOnly`, `Secure` (produksi), `SameSite=Lax`. ID sesi diregenerasi saat login; logout memanggil `invalidate()` dan `regenerateToken()`.
- Middleware `SecurityHeaders` memasang `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Content-Security-Policy` hanya `'self'`, dan `Referrer-Policy`. Halaman `/tamu/*` memakai `Referrer-Policy: no-referrer`.
- Pengecualian CSP hanya untuk halaman Langkah 1 (`GET /tamu/{token}` tanpa email terverifikasi): `https://accounts.google.com/gsi/client` di `script-src`, `https://accounts.google.com/gsi/` di `frame-src` dan `connect-src`, `https://accounts.google.com/gsi/style` di `style-src`.
- &#91;BARU v0.10\] Email tamu dan data diri tamu lama selalu diambil server dari session dan database, tidak pernah dari input browser (TAM-11, TAM-16).
- &#91;BARU v0.10\] Halaman riwayat tamu hanya menampilkan kunjungan dengan email yang sama persis dengan email terverifikasi di session.
- ID token Google, data URL tanda tangan, dan isi hasil **JANGAN** ditulis ke log aplikasi maupun `audit_logs.detail`. Data URL tanda tangan **JANGAN** dimasukkan kembali ke HTML saat formulir tampil ulang. Gambar tanda tangan tidak pernah disajikan dari `public/`; **JANGAN** menjalankan `php artisan storage:link`.
- Di produksi `APP_DEBUG=false`; deployment menjalankan `php artisan config:cache`, `route:cache`, dan `view:cache`.

## 12.2 Pembatasan akses berbasis peran (inti keamanan) \[DIUBAH v0.11\]

Buat satu service `App\Services\AccessScope` sebagai satu-satunya sumber cakupan, dan satu trait `App\Models\Concerns\LimitedByAccess` yang menyediakan *local scope* `forUser($user)`. **JANGAN** memakai *global scope*, karena halaman tamu harus dapat membaca data tanpa filter staf.

```
AccessScope::locationIds(user):
    super_admin   → NULL (tanpa batas)
    admin         → NULL (tanpa batas, ADM-5)
    receptionist  → location_id distinct dari qr_codes milik user
    pic           → [location_id departemen user]
    (hasil disimpan di memori selama satu request)

AccessScope::applyToVisits(query, user):
    super_admin   → tanpa filter
    admin         → tanpa filter (ADM-5)
    receptionist  → whereIn(qr_code_id, id QR milik user)   // QR status apa pun
    pic           → where(assigned_department_id, user.department_id)
    daftar id kosong → query menghasilkan 0 baris

AccessScope::applyToMaster(query, user, kolomLokasi):   // QR, departemen, kategori, petugas
    super_admin   → tanpa filter
    admin         → tanpa filter (ADM-5)
    peran lain    → 0 baris (tidak punya akses master data)

AccessScope::findOrDeny(modelClass, id, user):
    cari dengan forUser(user) dan id dalam query yang sama → jika ada, kembalikan
    jika tidak ada dan id itu ada tanpa filter → audit access.denied (detail: id, model)
    abort(404)   // respons seragam
```

Aturan:

- Trait `LimitedByAccess` dipakai `GuestVisit`, `QrCode`, `Location`, `Department`, `PurposeCategory`, dan `StaffMember`; `forUser()` memanggil method `AccessScope` yang sesuai.
- Setiap parameter `location_id`, `department_id`, `purpose_category_id`, `staff_member_id`, dan `receptionist_id` dari request **wajib** dicek terhadap cakupan. Di luar cakupan diperlakukan sebagai akses ditolak.
- Pembatasan peran per route (`role:`) adalah lapis pertama; `AccessScope` adalah lapis kedua dan tetap wajib walau route sudah dibatasi peran.
- Pengecualian tunggal: `ReturningGuestService` (Bab 9.4).
- Tes otomatis (A-15) wajib membuktikan: \[DIUBAH v0.11\] Resepsionis QR A, dan PIC departemen A mendapat 404 dan audit `access.denied` pada setiap route berparameter ID yang menyentuh data lokasi B, QR B, atau departemen B; serta ekspor tidak memuat data di luar cakupan.

## 12.3 Audit log \[DIUBAH v0.10\]

| Kelompok | Aksi (`action`) |
| --- | --- |
| Autentikasi | `auth.login_success`, `auth.login_failed`, `auth.locked`, `auth.logout`, `auth.password_changed` |
| Lokasi | `location.create`, `location.update`, `location.activate`, `location.deactivate`, `location.delete` |
| Departemen \[BARU v0.10\] | `department.create`, `department.update`, `department.activate`, `department.deactivate` |
| QR Code | `qr.create`, `qr.activate`, `qr.deactivate`, `qr.regenerate`, `qr.download`, `qr.change_receptionist` \[BARU v0.10\] |
| Kategori keperluan \[BARU v0.10\] | `purpose_category.create`, `purpose_category.update`, `purpose_category.activate`, `purpose_category.deactivate` |
| Petugas \[BARU v0.10\] | `staff.create`, `staff.update`, `staff.activate`, `staff.deactivate` |
| Pengguna | `user.create`, `user.update`, `user.activate`, `user.deactivate`, `user.password_reset`, `user.unlock`. \[DIHAPUS v0.11\] `user.assign_locations` |
| Kunjungan | `visit.export`, `visit.reopen` |
| Keamanan | `access.denied` |

&#91;DIHAPUS v0.10\] `emergency_pass.create`, `emergency_pass.use`. Penugasan dicatat di `visit_assignments`; perubahan status (Terima, Selesai, Batal) di `visit_status_logs`. `detail` tidak boleh berisi password, token, data URL tanda tangan, atau isi hasil. Untuk `visit.export`, `detail` berisi format, filter aktif, dan jumlah baris.

## 12.4 Ekspor Data (CSV, Excel, PDF) \[DIUBAH v0.10\]

**Umum**

- Tombol **Ekspor** di History berupa dropdown CSV, Excel (.xlsx), dan PDF; ketiganya mengikuti filter aktif dan cakupan.
- Kolom sama untuk ketiga format (15 kolom): No. Urut, Waktu Datang (`dd/mm/yyyy HH:mm`), Lokasi, Nama Tamu, Instansi/Perusahaan, Kelompok Rentan, Kategori Keperluan, Rincian Keperluan, No. Telepon, Alamat, Email, Departemen Tujuan, Orang yang Ditemui (dipilih resepsionis), Status (Selesai, Dibatalkan, atau Sedang direview), Hasil (kosong bila belum Selesai).
- **Excel dan PDF menambah kolom terakhir Tanda Tangan** berupa gambar (16 kolom). CSV tidak memuat tanda tangan.
- Nama file: `sitamu_{nama-lokasi atau semua}_{YYYYMMDD-HHmm}.{csv|xlsx|pdf}`.
- Header respons: `Content-Type` sesuai format, `Content-Disposition: attachment`, `Cache-Control: private, no-store`, `X-Content-Type-Options: nosniff`.

**CSV**

- UTF-8 dengan BOM, pemisah `CSV_DELIMITER`, ditulis streaming (`response()->streamDownload()`, `fputcsv()`, `chunkById()` atau `cursor()`). Tidak dibatasi jumlah baris.
- Pencegahan CSV injection: sel yang diawali `=`, `+`, `-`, `@`, tab, atau carriage return diberi awalan `'`.

**Excel (.xlsx)**

- `phpoffice/phpspreadsheet`, satu sheet, header tebal dan dibekukan, teks panjang (Rincian, Hasil, Alamat) dibungkus (wrap text).
- Gambar tanda tangan tertanam di sel, diperkecil dengan GD (lebar maks. 160 px).
- Seluruh nilai teks ditulis sebagai tipe string eksplisit sehingga tidak pernah dievaluasi sebagai rumus.

**PDF**

- `dompdf/dompdf` dari view Blade di `resources/views/admin/export/`. A4 landscape \[DEFAULT\], font 7 pt, header tabel berulang di setiap halaman, nomor halaman di footer.
- Bagian atas memuat nama instansi, lokasi (atau "Semua lokasi"), filter aktif, waktu pembuatan, dan jumlah baris.
- Gambar tanda tangan sebagai data URI hasil perkecilan GD. `isRemoteEnabled` dimatikan; semua nilai di-escape Blade.

**Batas dan performa**

- Excel dan PDF dibatasi `EXPORT_RICH_MAX_ROWS` baris, dihitung **sebelum** file dibuat: "Hasil filter X baris, melebihi batas Y baris. Persempit filter atau gunakan ekspor CSV."
- Gambar tanda tangan diambil per batch tanpa JOIN. Kunjungan tanpa gambar dibiarkan kosong.

## 12.5 Lain-lain

| Aspek | Ketentuan |
| --- | --- |
| Responsivitas | Halaman tamu mobile first (base 360 px, satu kolom, tombol besar). Panel staf responsif; pada layar < 768 px, tabel Tamu Aktif dan Penugasan berubah menjadi kartu. |
| Integritas data | Kunjungan tidak pernah dihapus. Semua waktu dari server. Perubahan status dan penugasan atomik (Bab 7.2). |
| Privasi | Hanya data Bab 6.6.1 yang dikumpulkan. Nomor telepon, alamat, email, dan tanda tangan tidak ditampilkan di daftar Tamu Aktif, Penugasan, maupun History; hanya di halaman detail sesuai peran. Kelompok rentan ditampilkan di daftar staf. |
| Performa | Tamu Aktif, Penugasan, dan dashboard tampil < 1 detik untuk 50.000 kunjungan, memanfaatkan index Bab 9.2 dan eager loading (`with()`). |
| Kompatibilitas | Chrome, Firefox, Safari, dan Edge versi terbaru; Chrome Android dan Safari iOS untuk halaman tamu. |
| Aksesibilitas | Setiap input memiliki `<label>`; pesan error terhubung ke field; kontras mengikuti standar Bootstrap. |
| Operasional | Tanpa cron, queue worker, atau Node di server. Deployment: `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, lalu `config:cache`, `route:cache`, `view:cache`. Folder `storage/` dan `bootstrap/cache/` dapat ditulis web server. |

# 13. Rincian Halaman UI \[DIUBAH v0.10\]

Halaman tamu bertambah dua (riwayat dan formulir tamu lama), panel staf bertambah halaman Penugasan PIC, kategori keperluan, petugas, dan departemen.

## 13.1 Halaman tamu

| Halaman | Isi utama |
| --- | --- |
| Langkah 1: verifikasi email | Logo dan nama instansi, badge nama lokasi, satu kalimat petunjuk, tombol "Pilih akun Google" lebar penuh, tautan kecil "Tidak bisa login Google?". Tidak ada elemen lain. |
| Bantuan login Google | "Coba buka halaman ini di Chrome atau Safari. Jika masih belum bisa, silakan hubungi petugas." dan tombol "Coba lagi". |
| Riwayat kunjungan \[BARU v0.10\] | Logo, badge lokasi, email terverifikasi dengan tombol "Ganti akun", tombol utama "Isi formulir lagi". Daftar kartu kunjungan (terbaru di atas): tanggal dan jam, lokasi, kategori dan rincian keperluan, badge status, departemen tujuan, orang yang ditemui, dan kotak Hasil (bila Selesai). Paginasi sederhana "Sebelumnya / Berikutnya". |
| Formulir tamu baru | Logo dan nama instansi, badge lokasi, blok Email read-only dengan tombol "Ganti akun", teks "Jam datang dicatat otomatis saat formulir dikirim.", lalu field sesuai urutan Bab 11.1: Keperluan (dropdown) → Rincian keperluan (muncul setelah kategori dipilih) → Kelompok rentan → Nama lengkap → Instansi/perusahaan → Nomor telepon/WhatsApp → Alamat → kotak tanda tangan (lebar penuh, tinggi sekitar 180 px, tombol "Hapus Tanda Tangan", halaman tidak bergulir saat menggambar) → checkbox persetujuan → tombol "Kirim Formulir" lebar penuh, dinonaktifkan setelah diklik. |
| Formulir tamu lama \[BARU v0.10\] | Sama dengan formulir tamu baru, tetapi Kelompok rentan, Nama lengkap, Instansi/perusahaan, Nomor telepon/WhatsApp, dan Alamat tampil terisi dan read-only (`disabled`), dengan keterangan "Data diri diambil dari kunjungan terakhir Anda." |
| Terima kasih | Ikon sukses, nomor urut berukuran besar, lalu "Terima kasih, {nama}. Kunjungan Anda di {lokasi} tercatat dengan No. {nomor urut} pada pukul {HH:mm}. Silakan menunggu petugas." dan tautan "Lihat riwayat kunjungan". |
| QR tidak aktif / terlalu banyak percobaan | Pesan singkat dan ramah, tanpa detail teknis. |

## 13.2 Panel staf

| Halaman | Isi utama |
| --- | --- |
| Login | Email, password, pesan error seragam. |
| Layout staf | Sidebar di desktop, offcanvas di ponsel. Menu sesuai peran (di bawah). Nama akun, peran, dan tombol logout di pojok kanan atas. |
| Menu per peran | **Resepsionis:** Tamu Aktif, Ganti Password. **PIC:** Penugasan (dengan badge jumlah baru), Ganti Password. **Admin:** Dashboard, Tamu Aktif, History, Kategori Keperluan, Petugas, Ganti Password. **Super Admin:** Dashboard, Tamu Aktif, History, Lokasi (dengan Departemen dan QR), Kategori Keperluan, Petugas, Pengguna, Audit Log, Ganti Password. |
| Dashboard (Admin, Super Admin) | Kartu angka (DSH-1/DSH-3), Tamu Aktif ringkas, grafik 14 hari (Super Admin). |
| Tamu Aktif — Resepsionis | Kotak pencarian dan filter label status; tabel/kartu: nomor urut, nama, instansi, kelompok rentan, kategori keperluan, saran departemen, departemen dan orang yang ditemui, jam datang, lama menunggu, badge status, tombol aksi. Tombol per kondisi: Menunggu → Tugaskan, Batal; Menunggu PIC → Tugaskan ulang, Batal; Sedang direview → Batal. Modal Tugaskan berisi pilihan departemen (saran terpilih otomatis), lalu pilihan orang yang ditemui dari daftar petugas departemen tersebut. Modal alasan batal. Toast setelah aksi. |
| Tamu Aktif — Admin, Super Admin | Sama dengan versi Resepsionis ditambah kolom lokasi dan QR serta filter lokasi; tombol hanya Batal dan Detail. |
| Penugasan — PIC \[BARU v0.10\] | Dua bagian: "Penugasan baru" dan "Sedang direview". Kartu/baris: nomor urut, nama, instansi, kelompok rentan, kategori dan rincian keperluan, orang yang ditemui, lokasi, jam datang, lama menunggu. Tombol: Terima pada penugasan baru; Selesai (modal isian Hasil dengan keterangan "Hasil ini akan terlihat oleh tamu.") pada yang sedang direview. Tombol "Aktifkan bunyi notifikasi" (opsional, PIC-4). |
| Detail kunjungan | Admin dan Super Admin: 12 data Bab 6.6.1 (tanda tangan dari route tanda tangan, `loading="lazy"`), lokasi, QR, saran departemen, departemen tujuan, orang yang ditemui, hasil, riwayat penugasan, dan timeline status (waktu, dari → ke, oleh akun, catatan); tombol Buka Kembali hanya untuk Admin pada kunjungan Selesai/Dibatalkan. Resepsionis dan PIC: sama, tanpa tanda tangan dan tanpa tombol Buka Kembali. |
| History | Panel filter (Bab 6.10), tabel berpaginasi (tanggal, nomor urut, nama, instansi, kelompok rentan, kategori, departemen tujuan, lokasi, jam datang, status), tombol Ekspor berupa dropdown (CSV, Excel, PDF). |
| Lokasi | Tabel lokasi (nama, jumlah departemen, jumlah QR aktif, status), tombol tambah, ubah, aktif/nonaktif, hapus (bila memenuhi syarat), tautan Departemen dan QR. |
| Departemen per lokasi \[BARU v0.10\] | Tabel departemen (nama, akun PIC, jumlah petugas aktif, status), tombol tambah, ubah nama, aktif/nonaktif. |
| QR per lokasi | Tabel QR (label, akun resepsionis, status, dibuat, terakhir dipakai) dengan tombol ganti resepsionis, aktif/nonaktif, regenerate, unduh PNG/SVG, cetak. Form buat QR berisi label dan pilihan akun resepsionis. |
| Cetak QR | Layout A4 bersih tanpa navigasi: logo, nama instansi, lokasi, label QR, QR, instruksi; siap `window.print()`. |
| Kategori keperluan \[BARU v0.10\] | Pilihan lokasi (bila lebih dari satu), tabel kategori (urutan, nama, saran departemen, status), tombol tambah, ubah, aktif/nonaktif. |
| Petugas \[BARU v0.10\] | Pilihan lokasi dan departemen, tabel petugas (nama, status), tombol tambah, ubah, aktif/nonaktif. |
| Pengguna | Tabel akun (nama, email, peran, lokasi/departemen/QR terkait, status, login terakhir) dengan pencarian dan filter peran serta status; form dengan field yang berubah sesuai peran (USR-1); tombol Buka kunci. |
| Audit log | Tabel dengan filter tanggal, akun, dan aksi. |

Identitas visual: tampilan mengikuti template Volt (Bab 5.6); logo di `public/assets/img/logo.png` (placeholder sampai klien memberikan logo resmi) dan warna utama sebagai variabel CSS di satu file.

# 14. Integrasi Pihak Ketiga

Sistem berdiri sendiri kecuali satu integrasi: Google Identity Services untuk memverifikasi email tamu (TAM-11), yang hanya membaca email dan status verifikasinya. Laravel, `endroid/qr-code`, `phpoffice/phpspreadsheet`, `dompdf/dompdf`, Bootstrap, dan Chart.js dipasang atau di-host sendiri, bukan layanan online. Komunikasi resepsionis ke PIC lewat WhatsApp terjadi di luar sistem dan tidak diintegrasikan.

# 15. Rencana Implementasi (Milestone) \[DIUBAH v0.10\]

Delapan milestone dikerjakan berurutan; satu milestone selesai jika seluruh Definition of Done terpenuhi, dikerjakan dalam commit-commit kecil sesuai A-11 dan A-12.

| Milestone | Cakupan | Definition of Done |
| --- | --- | --- |
| M0 — Fondasi | Skeleton Laravel (`composer.lock` di-commit), pembersihan Vite/Node dan route contoh, `.env.example` lengkap (Bab 5.4), `config/sitamu.php` dan `config/consent.php`, locale `id`, zona waktu, `DB_TIMEZONE`, enum (`VisitStatus`, `UserRole`, `QrStatus`, `VulnerableGroup`), middleware `SecurityHeaders`, `EnsureUserIsActive`, `EnsureRole` (multi-peran), semua migrasi Bab 9.2 beserta model dan relasinya, `php artisan sitamu:create-superadmin`, layout Blade dasar berbasis Volt (Bab 5.6), halaman error kustom, `README.md`. | `php artisan migrate` berjalan di database kosong; Super Admin dapat dibuat lewat artisan; halaman ber-layout tampil; `php artisan test` hijau; `vendor/bin/pint --test` bersih; tidak ada secret di repositori. |
| M1 — Autentikasi, Akun & AccessScope | Login/logout, penguncian akun, batas sesi, middleware `active` dan `role`, `AccessScope` dan trait `LimitedByAccess` untuk empat peran, `AuditService`, CRUD akun staf empat peran dengan field per peran (USR-1, USR-5), penugasan lokasi Admin, buka kunci, ganti password, halaman audit log, menu per peran. | AUTH-1–4, USR-1–5, AUD-1–2 terpenuhi. Setiap peran mendapat 403 pada route peran lain. Setelah ganti password, semua sesi akun itu berakhir dan pengguna diarahkan ke login. Tes otomatis untuk penguncian akun, akun nonaktif, 403 per peran, dan `AccessScope` keempat peran lulus. |
| M2 — Master Data | CRUD lokasi, departemen, QR (dengan akun resepsionis, ganti resepsionis, regenerate, unduh, cetak), kategori keperluan, dan petugas. | LOK-1–3, DEP-1–3, QR-1–6, KAT-1–3, PTG-1–2 terpenuhi. QR yang diunduh dapat dipindai ponsel dan membuka URL yang benar. Admin dapat mengelola kategori dan petugas di semua lokasi. |
| M3 — Halaman Tamu | Langkah 1 verifikasi Google (`GoogleTokenVerifier`, `guest-google.js`, pengecualian CSP), halaman bantuan, deteksi tamu lama, riwayat tamu, formulir tamu baru dan lama (`ReturningGuestService`), kotak tanda tangan, `SignatureService`, nomor urut, penyimpanan, persetujuan, rate limiter bernama, honeypot, token sekali pakai, halaman terima kasih. | TAM-1–12 dan TAM-14–17 terpenuhi. Refresh halaman terima kasih tidak menyimpan data ganda. Dua kiriman bersamaan di lokasi yang sama mendapat nomor urut berbeda. Email dan data diri tamu lama tidak dapat diubah dari DevTools. Riwayat hanya menampilkan kunjungan email tersebut. Tanda tangan dan verifikasi Google diuji di Chrome Android dan Safari iOS. Tes otomatis untuk nomor urut, email dari session, data diri tamu lama dari database, isolasi riwayat, token TAM-14, dan rate limit lulus (Google di-fake dengan `Http::fake()`). |
| M4 — Resepsionis & PIC | Tamu Aktif (versi Resepsionis dan Admin), penugasan dan penugasan ulang, pemilihan orang yang ditemui saat menugaskan, Penugasan PIC, terima, hasil dan selesai, batal, buka kembali oleh Admin, detail kunjungan per peran, route tanda tangan, timeline status dan penugasan, polling dan penanda menunggu lama. | RSP-1–8, PIC-1–6, ADM-1–4 terpenuhi. Kunjungan dari QR A hanya muncul di resepsionis QR A. Setelah QR-6, kunjungan aktif pindah ke resepsionis baru. Penugasan ulang setelah PIC menerima mendapat 409. PIC yang menerima kunjungan yang baru saja dialihkan mendapat 409. Dua PIC tidak dapat menerima kunjungan yang sama. Tes otomatis untuk semua transisi Bab 7, respons 409/422, dan penugasan lulus. |
| M5 — History & Ekspor | Halaman History dengan filter baru, dropdown Ekspor (CSV, Excel, PDF) dengan 15/16 kolom, batas baris Excel/PDF. | HIS-1–5 terpenuhi. CSV terbuka rapi di Excel; Excel menampilkan gambar tanda tangan; PDF terbaca dengan header berulang; ketiganya hanya berisi data dalam cakupan. Ekspor di atas batas ditolak dengan pesan jelas. Tercatat di audit log. Tes otomatis cakupan ekspor lulus. |
| M6 — Dashboard | Dashboard Admin dan Super Admin, grafik 14 hari, pengalihan Resepsionis dan PIC. | DSH-1–5 terpenuhi. Angka dashboard sama dengan hitung manual di database. |
| M7 — Penyempurnaan | Tampilan kartu di ponsel, review keamanan terhadap Bab 12, pembaruan README (instalasi server, document root `public/`, trusted proxies, Google Client ID, backup, urutan pengisian master data awal). | Semua halaman nyaman di layar 360 px; checklist Bab 12 terpenuhi; konektivitas server ke Google terverifikasi lewat cURL; runbook backup dan prosedur darurat pemulihan akun Super Admin via CLI terdokumentasi; review kerapian kode A-13; deployment dengan `--no-dev` dan cache Laravel tanpa error; `APP_DEBUG=false`; IP klien dan HTTPS terbaca benar di balik proxy. |

# 16. Fitur Usulan / Fase Lanjutan (JANGAN diimplementasikan di MVP)

- **Statistik durasi layanan:** rata-rata lama menunggu penugasan, lama menunggu PIC, dan lama layanan per departemen dari `check_in_at`, `assigned_at`, `processed_at`, dan `completed_at`.
- **Laporan kelompok rentan:** rekap kunjungan per kelompok rentan per periode untuk laporan pelayanan publik.
- **Tabel `guests` terpisah:** profil tamu yang dapat diperbarui tamu sendiri, menggantikan pengambilan data dari kunjungan terakhir.
- **Notifikasi WhatsApp ke PIC:** pemberitahuan otomatis saat ada penugasan baru, menggantikan kontak manual.
- **Penolakan penugasan oleh PIC:** PIC mengembalikan kunjungan ke resepsionis dengan alasan.
- **Jalur bagi tamu tanpa akun Google:** pencatatan yang dibantu petugas (QR Darurat atau pengisian dari perangkat petugas).
- **Data identitas opsional per lokasi:** foto, KTP, atau nomor identitas bila diwajibkan, disertai kebijakan retensi.
- **Pembatalan otomatis:** kunjungan Menunggu ditutup otomatis setelah batas waktu.

# 17. Keputusan Tertunda dan Nilai Default Sementara \[DIUBAH v0.10\]

Agent memakai kolom **Default untuk MVP**; jika klien memutuskan lain, cukup ubah bagian di kolom **Dampak jika berubah**. Daftar disusun ulang di v0.10; nomor v0.9 tidak berlaku lagi.

| No | Pertanyaan untuk klien | Default untuk MVP | Dampak jika berubah |
| --- | --- | --- | --- |
| 1 | Satu tabel `guest_visits` untuk data tamu dan kunjungan? | Ya. | Besar: skema, model, ekspor. |
| 2 | Boleh langsung Menunggu → Selesai? | Tidak. | Kecil: tabel transisi `VisitService` dan tombol UI. |
| 3 | Perlu CAPTCHA sejak awal? | Tidak; rate limit dan honeypot. | Sedang: formulir tamu dan dependency baru. |
| 4 | Interval polling dan batas "menunggu lama"? | 30 detik dan 15 menit. | Kecil: nilai `.env`. |
| 5 | Zona waktu? | WIB (`Asia/Jakarta`). | Kecil: nilai `.env`. |
| 6 | Nomor urut reset harian per lokasi? | Ya. | Kecil: kunci `visit_counters` dan unique key. |
| 7 | Kebijakan retensi data dan teks persetujuan resmi? | Tanpa penghapusan otomatis; teks sementara di bawah. | Kecil: `config/consent.php`; retensi otomatis menjadi fitur baru. |
| 8 | Nama resmi, logo, dan warna identitas? | Nama Disperindag Jatim, logo placeholder, warna biru netral. | Kecil: `.env`, file logo, variabel CSS. |
| 9 | Data awal: lokasi, departemen, akun, QR, kategori, petugas? | Diinput manual oleh Super Admin dan Admin setelah instalasi. | Tidak ada perubahan kode. |
| 10 | Versi Laravel dan PHP server? | Laravel mayor stabil terbaru yang kompatibel dengan PHP server; dicatat di `docs/DECISIONS.md`. Versi PHP WAJIB dikonfirmasi sebelum M0. | Sedang: pemilihan versi Laravel. |
| 11 | Jenis hosting dan domain? | Apache dengan `.htaccess`, document root `public/`, domain lewat `APP_URL`. | Kecil sampai sedang: konfigurasi server. |
| 12 | Build frontend (Node/Vite)? | Tidak. | Sedang: layout dan mesin build. |
| 13 | Driver session dan cache? | `file` (satu server). | Kecil: `.env`. |
| 14 | Verifikasi ID token Google: `tokeninfo` atau library? | `tokeninfo` lewat `Http` facade, timeout 5 detik. | Sedang: `GoogleTokenVerifier`; library butuh persetujuan A-3. |
| 15 | Pemilik project Google Cloud dan OAuth Client ID? | Dibuat instansi; Web application, "In production", tanpa scope sensitif. | Kecil: `.env`. Tanpa Client ID, halaman tamu tidak berjalan. |
| 16 | Masa berlaku verifikasi email di session? | 30 menit. | Kecil: `.env`. |
| 17 | Server produksi punya akses internet keluar ke Google? | Terkonfirmasi: ya. | Prasyarat deployment, diverifikasi di M7. |
| 18 | Library ekspor Excel dan PDF? | `phpoffice/phpspreadsheet` dan `dompdf/dompdf`. | Sedang: `ExportService` dan ekstensi PHP. |
| 19 | Ukuran kertas PDF untuk 16 kolom? | A4 landscape, font 7 pt. | Kecil: template PDF (misalnya F4 landscape). |
| 20 | Batas baris ekspor Excel dan PDF? | 500 baris; CSV tanpa batas. | Kecil: `.env`. |
| 21 | \[DIUBAH v0.10\] Semua sesi diakhiri setelah ganti password? | Ya, termasuk sesi yang sedang dipakai; pengguna login kembali. | Kecil: lepas middleware `AuthenticateSession`. |
| 22 | Sudah ada kode atau database PHP native sebelumnya? | Tidak; mulai dari nol. | Sedang: rencana migrasi data. |
| 23 | \[BARU v0.10\] Daftar pilihan kelompok rentan? | Bukan kelompok rentan, Lansia, Penyandang disabilitas, Ibu hamil, Tidak ingin menyebutkan. | Kecil: enum `VulnerableGroup`, ekspor, filter. |
| 24 | \[BARU v0.10\] Kategori keperluan per lokasi atau berlaku untuk semua lokasi? | Per lokasi. | Sedang: skema `purpose_categories` dan `AccessScope`. |
| 25 | \[BARU v0.10\] Satu akun resepsionis boleh memegang lebih dari satu QR? | Ya. | Kecil: validasi QR-2/QR-6. |
| 26 | \[BARU v0.10\] Satu departemen tepat satu akun PIC aktif? | Ya. | Kecil: validasi USR-5. |
| 27 | \[BARU v0.10\] Siapa yang mengelola kategori keperluan dan daftar petugas? | Admin dan Super Admin, untuk semua lokasi. | Kecil: route dan matriks akses. |
| 28 | \[BARU v0.10\] Tamu lama dikenali lintas semua lokasi atau per lokasi? | Lintas semua lokasi. | Kecil: query `ReturningGuestService`. |
| 29 | \[BARU v0.10\] Data diri tamu lama dikunci atau boleh diubah tamu? | Dikunci; diambil dari kunjungan terakhir. | Sedang: formulir, Form Request, `ReturningGuestService`. |
| 30 | \[BARU v0.10\] Kategori yang pernah dilaporkan tamu tetap dapat dipilih lagi? | Ya. | Kecil: filter opsi kategori di formulir tamu lama. |
| 31 | \[BARU v0.10\] Apakah orang yang ditemui sama dengan petugas yang melayani? | Ya; tidak diisi tamu, dipilih resepsionis saat menugaskan. | Sedang: field formulir, kolom database, validasi, dan ekspor bila tamu perlu mengisinya sendiri. |
| 32 | \[BARU v0.10\] Instansi/perusahaan wajib? | Ya; tamu pribadi menulis "Pribadi". | Kecil: validasi dan kolom. |
| 33 | \[BmaARU v0.10\] Super Admin juga boleh membuka kembali kunjungan? | Tidak; hanya Admin. | Kecil: middleware route 10.7 dan tombol UI. |
| 34 | \[BARU v0.10\] Riwayat tamu menampilkan orang yang ditemui dan hasil? | Ya. | Kecil: view riwayat dan `ReturningGuestService`. |
| 35 | \[BARU v0.10\] Panjang maksimal rincian keperluan dan hasil? | 500 dan 2000 karakter. | Kecil: validasi dan kolom. |

**Teks persetujuan sementara (menunggu persetujuan instansi) \[DIUBAH v0.10\]:**

> Saya setuju Disperindag Provinsi Jawa Timur memproses data pada formulir ini.

# 18. Glosarium

- **QR Code:** kode dua dimensi yang dipindai untuk membuka halaman buku tamu suatu titik masuk.
- **Token:** kode acak unik pada URL QR yang mengidentifikasi satu QR, lokasinya, dan akun resepsionisnya.
- **Lokasi:** gedung atau kantor tempat tamu diterima; satu lokasi dapat memiliki beberapa QR dan departemen.
- **Departemen:** bidang atau unit kerja di suatu lokasi yang melayani tamu; dilayani oleh satu akun PIC.
- **Resepsionis:** akun bersama per pos penerimaan tamu yang menugaskan kunjungan ke departemen.
- **PIC (Person in Charge):** akun bersama per departemen yang menerima penugasan, melayani tamu, dan menulis hasil.
- **Orang yang ditemui:** pegawai yang mereview tamu memakai akun PIC departemennya; dipilih resepsionis dari daftar petugas saat menugaskan.
- **Akun bersama:** satu akun login yang dipakai bergantian oleh beberapa petugas.
- **Penugasan:** penentuan departemen tujuan dan orang yang ditemui oleh resepsionis; dapat diulang selama PIC belum menerima.
- **Kategori keperluan:** pilihan jenis keperluan pada formulir tamu, masing-masing dapat memiliki saran departemen.
- **Saran departemen:** departemen yang otomatis diusulkan kepada resepsionis berdasarkan kategori keperluan; tidak terlihat oleh tamu.
- **Kelompok rentan:** kategori tamu yang mungkin membutuhkan perhatian khusus dalam pelayanan, misalnya lansia atau penyandang disabilitas.
- **Hasil:** tanggapan atau jawaban PIC atas keperluan tamu, ditampilkan di riwayat tamu.
- **Tamu lama:** tamu yang email Google-nya sudah pernah tercatat pada kunjungan sebelumnya.
- **Nomor urut:** nomor pencatatan kunjungan per lokasi per hari. Bukan nomor antrean.
- **Tanda tangan digital (goresan):** gambar tanda tangan yang digambar tamu di layar sebagai bukti kehadiran, bukan Tanda Tangan Elektronik tersertifikasi.
- **Re-encode:** membuat ulang gambar dari data pikselnya agar isi tersembunyi di file asli terbuang.
- **Tamu Aktif:** kunjungan berstatus Menunggu, Menunggu PIC, atau Sedang direview.
- **History:** kunjungan berstatus Selesai atau Dibatalkan.
- **Regenerate:** membuat QR baru dan mencabut QR lama secara permanen.
- **Cakupan (scope):** batas data yang boleh diakses seorang staf: lokasi (Admin), QR (Resepsionis), atau departemen (PIC).
- **AccessScope:** service pusat yang menerapkan cakupan setiap peran pada semua query staf.
- **IDOR:** celah mengakses data milik pihak lain dengan mengubah ID atau parameter; dicegah dengan `AccessScope` di server.
- **CSRF:** serangan yang memaksa pengguna yang sedang login mengirim aksi tanpa sadar.
- **Rate limiting:** pembatasan jumlah permintaan dalam periode waktu tertentu.
- **Honeypot:** field tersembunyi yang hanya diisi bot, untuk mendeteksi kiriman otomatis.
- **PRG (Post/Redirect/Get):** pola redirect setelah form dikirim agar refresh tidak mengirim data dua kali.
- **Polling:** browser meminta data terbaru ke server secara berkala untuk memperbarui daftar tanpa memuat ulang halaman.
- **Soft delete:** menandai data terhapus (mengisi `deleted_at`) tanpa menghapus barisnya.
- **Audit log:** catatan aktivitas penting sistem untuk penelusuran.
- **Verifikasi email Google:** langkah awal di halaman tamu; server memastikan email milik akun Google yang dipilih. Bukan pembuatan akun pengguna.
- **Laravel, Eloquent, Migrasi, Artisan, Form Request, Middleware, Blade, Local scope:** komponen framework Laravel sebagaimana didefinisikan di PRD v0.9.

---

*Dokumen ini merupakan draft dan dapat berubah seiring pembahasan dengan klien. Nilai bertanda \[DEFAULT\] wajib dikonfirmasi sebelum sistem digunakan di produksi.*
