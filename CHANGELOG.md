# Catatan Perubahan

Semua perubahan penting pada proyek ini dicatat di berkas ini.
Format mengikuti [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/); versi mengikuti [Semantic Versioning](https://semver.org/lang/id/).

## [3.0.0] — 2026-10-14 · Laravel 13 & PHP 8.5

### Peningkatan versi
- **Laravel 10.50 → 13.36.** Laravel 10 sudah EOL; versi 13 masih menerima patch keamanan. `composer audit` kini bersih, sehingga empat pengecualian advisori Laravel 10 di `composer.json` dihapus.
- **PHP minimal 8.4** (sebelumnya 8.1). Image Docker memakai PHP 8.5; Laragon mendapat PHP 8.5.11 berdampingan dengan PHP 8.1.
- **Paket PHP lain:**
  - PHPUnit 10 → 13;
  - Carbon 2 → 3;
  - Collision 7 → 8;
  - Tinker 2 → 3;
  - Symfony 6.4 → 8.1.
- **Frontend:** Vite 6 → 8 (Rolldown) dan laravel-vite-plugin 1 → 3. Tailwind 4.3, Alpine 3.17, dan Chart.js 4.5 sudah versi terbaru.
- **Docker:**
  - PHP 8.5 (OPcache kini bawaan inti);
  - Node 24 LTS untuk build aset;
  - MySQL 8.0 → 9.7 LTS. Volume lama ditingkatkan bertahap lewat 8.4 LTS, karena lompatan langsung 8.0 → 9.7 tidak didukung MySQL;
  - Adminer 6.
- **Paket dihapus:** `laravel/sanctum` (tidak dipakai), `spatie/laravel-ignition` (Laravel 13 punya halaman error sendiri), `laravel/sail` (sudah ada Docker sendiri), dan `guzzlehttp/guzzle` eksplisit (sudah ikut framework).

### Perubahan yang mengikuti Laravel 13
- Middleware CSRF diganti `PreventRequestForgery`. Selain token, middleware ini juga memverifikasi asal request lewat header `Sec-Fetch-Site`.
- Konfigurasi `cache.serializable_classes = false` dan `session.serialization = json` mencegah serangan deserialisasi objek bila `APP_KEY` bocor. Karena itu, cache ulasan di beranda kini menyimpan waktu sebagai teks, bukan objek Carbon.
  - **Dampak:** semua sesi lama tidak berlaku, sehingga pengguna perlu masuk ulang sekali.
- Opsi SSL MySQL memakai `Pdo\Mysql::ATTR_SSL_CA`, karena `PDO::MYSQL_ATTR_*` sudah usang di PHP 8.5.
- Struktur aplikasi gaya Laravel 10 (`Kernel`, `Handler`, `RouteServiceProvider`) dipertahankan sesuai rekomendasi panduan upgrade resmi.

### Lainnya
- Skrip `composer test` (bersihkan cache konfigurasi lalu jalankan pengujian).
- Seluruh 70 pengujian lulus di PHP 8.5 lokal dan di container, tanpa *deprecation notice*.

## [2.1.0] — 2026-10-14 · Keamanan, logging, Docker

### Keamanan
- **Unggahan berbahaya diblokir (kritis).** Sebelumnya ekstensi berkas diambil dari nama file klien. Akibatnya gambar "polyglot" bernama `shell.php` bisa tersimpan di folder publik dan dieksekusi (RCE). Kini hanya ekstensi dalam daftar putih yang dipakai (`Berkas::EKSTENSI_AMAN`). Ditambah `public/storage/.htaccess` dan konfigurasi Apache Docker yang menolak eksekusi skrip di folder unggahan.
- **Open redirect ditutup.** Notifikasi dan tombol "kembali" admin kini memakai helper `tujuan_aman()`. Pengecekan lama `str_starts_with($url, url('/'))` bisa ditembus dengan host seperti `http://localhost.evil.com`.
- **XSS di sanitizer konten diperbaiki.** Tag tanpa spasi seperti `<p/onclick=...>` lolos dari penyaringan atribut. Markdown kini juga dibatasi kedalaman *nesting*-nya untuk mencegah DoS.
- **CRLF injection pada email dimitigasi.** Advisori Laravel 10 ini tidak punya patch; seluruh input email kini memakai `email:rfc,filter`.
- **Header keamanan baru:**
  - Content-Security-Policy berbasis nonce (mode `CSP_MODE=enforce|report|off`);
  - `Cross-Origin-Opener-Policy` dan `X-Permitted-Cross-Domain-Policies`;
  - `X-Powered-By` dihapus;
  - `Cache-Control: no-store` untuk halaman ber-login, karena aplikasi dipakai di komputer lab bersama.
- **Berkas privat:** disajikan dengan CSP `sandbox`. Hanya JPEG/PNG/WEBP/GIF yang ditampilkan inline; tipe lain selalu diunduh.
- **TrustHosts diaktifkan** (mencegah *host header poisoning* pada tautan reset sandi). **TrustProxies** dapat diatur lewat `TRUSTED_PROXIES`.
- **HTTPS dipaksa** di produksi bila `APP_URL` memakai https. Cookie sesi otomatis `secure`. Muncul peringatan kritis di log bila `APP_DEBUG=true` di produksi.
- **Formula injection pada ekspor CSV dinetralkan.** Sel yang diawali `= + - @` diberi apostrof.
- **Login:**
  - akun nonaktif ditolak tanpa sempat masuk;
  - panjang kata sandi dibatasi;
  - aturan sandi disatukan lewat `Password::defaults()`.
- **Rate limit** ditambahkan pada `/jadwal/data` dan polling notifikasi.
- **Privasi:** foto KTM/KTP dihapus otomatis N hari setelah peminjaman berakhir (`studio:bersihkan-jaminan`, `STUDIO_RETENSI_JAMINAN_HARI`, bawaan 30 hari), sejalan dengan prinsip minimisasi data UU PDP.
- **Dependensi diperbarui** ke versi tanpa advisori: Laravel 10.50.3, Guzzle 7.15.5, CommonMark 2.10.3, Symfony 6.4.47, Flysystem 3.36, Vite 6.4 / esbuild 0.25. Empat advisori Laravel 10 yang tidak dipatch didokumentasikan beserta mitigasinya di `composer.json` → `config.audit.ignore`.
- Kunci sandbox Midtrans lama dihapus dari `.env.example`.

### Logging
- **Kanal `keamanan`** (`storage/logs/keamanan-*.log`, JSON, 90 hari) mencatat:
  - masuk, keluar, gagal masuk (beserta alasannya), reset sandi;
  - akses ditolak (403), CSRF tidak valid (419), batas permintaan (429);
  - setiap akses berkas privat: siapa membuka berkas milik siapa.
- **Kanal `akses`** (`storage/logs/akses-*.log`, JSON, 30 hari): satu baris per request, berisi status, durasi, rute, user, dan IP. Query string sengaja tidak dicatat.
- **Request ID:** header `X-Request-Id` ditambahkan pada setiap respons dan ikut tercatat di semua baris log untuk korelasi.
- **Konteks error:** setiap error kini tercatat bersama URL, IP, dan `request_id`.
- **Jejak audit (DB):**
  - dilengkapi untuk ubah tutorial, ubah jadwal tutup, dan hapus foto ruangan;
  - login/logout kini dicatat lewat *event subscriber* `CatatKejadianKeamanan`;
  - retensi otomatis lewat `model:prune` (`LOG_RETENSI_AUDIT_HARI`).
- **Halaman Log Aktivitas:**
  - tab Keamanan / Data & operasional;
  - pencarian berdasarkan IP;
  - ekspor CSV;
  - peringatan bila ada ≥10 kali gagal masuk dalam 24 jam.
- Kanal utama memakai rotasi harian (`LOG_STACK`, `LOG_DAILY_DAYS`).

### Docker
- `Dockerfile` multi-stage: aset Vite (Node 22), vendor Composer, lalu runtime PHP 8.3 + Apache. Berjalan sebagai non-root pada port 8080, dengan OPcache dan *healthcheck*.
- `docker-compose.yml` berisi app, worker antrean, scheduler, MySQL 8, Mailpit (penangkap email), dan Adminer (opsional, profil `tools`).
- Entrypoint mengurus:
  - `APP_KEY` persisten, juga untuk `docker compose exec app php artisan ...`;
  - menunggu basis data;
  - migrasi otomatis dan seed demo sekali saja;
  - cache konfigurasi di produksi.

### Lainnya
- `CLAUDE.md` (panduan untuk Claude Code) dan berkas ini ditambahkan.
- 13 pengujian keamanan baru (`tests/Feature/KeamananTest.php`).
- `phpunit.xml` memaksa nilai uji (`DB_DATABASE=studio_musik_test`, dll.) agar variabel lingkungan container tidak menimpanya. Seluruh 70 pengujian lulus, baik lokal maupun di dalam container.

## [2.0.0] — 2026-10-12 · Pembaruan menyeluruh

### Alur & fitur
- **Wizard peminjaman 3 langkah:**
  - kalender bulanan berwarna menandai tanggal yang kosong, terisi sebagian, penuh, atau tutup;
  - pilihan durasi (1–4 jam) dan jam mulai, hanya dari slot yang benar-benar kosong;
  - detail peminjaman beserta unggah KTM.
- **Check-in/check-out** dengan foto kondisi studio, ulasan setelah selesai, dan pembatalan dengan batas waktu.
- **Status otomatis:** pengajuan kedaluwarsa, tidak hadir, dan pengingat jadwal (`studio:sinkron-status`).
- **Jadwal publik** mingguan tanpa identitas peminjam, kalender admin, dan **Jadwal Tutup** (UTS/UAS, kegiatan UKM).
- **Jasa musik:**
  - formulir dinamis per layanan (penyusun field oleh admin);
  - alur antrean → produksi → selesai;
  - kirim berkas atau tautan hasil, lalu ulasan.
- **Notifikasi** email + in-app (lonceng) untuk setiap perubahan status.
- **Dasbor admin:** KPI, grafik tren, antrean persetujuan cepat, jadwal hari ini, tenggat jasa, aktivitas terbaru.
- **Menu admin baru:** Pengguna (peran & status aktif), Pengaturan operasional tanpa deploy, Log Aktivitas, ekspor CSV.
- **Peran K3L berfungsi:** memantau jadwal dan laporan masalah. Sebelumnya peran ini selalu mendapat halaman 403.
- **Akun:** reset kata sandi lewat email, profil, ganti sandi.

### Teknis
- Status berkode P/Y/N/X di tiga kolom diganti satu kolom `status` (enum). Tabel `detail_pesanan_jadwal_studio` digabung ke tabel utama. Migrasi menyertakan konversi data lama dan `down()` yang berfungsi.
- Pengecekan bentrok jadwal di server dalam transaksi + *row lock*. Jadwal yang bersambung tidak lagi dianggap bentrok.
- Otorisasi kepemilikan data (Policy).
- KTM/KTP, foto kondisi, dan lampiran dipindah ke penyimpanan privat.
- UI dibangun ulang dengan Tailwind v4 + Alpine.js: dark mode, responsif di ponsel, navigasi bawah untuk peminjam.
- Template lama (Mazer, CarVilla, DataTables, SweetAlert) dan kode Midtrans yang tidak berfungsi dihapus.
- Pesan validasi berbahasa Indonesia, 57 pengujian otomatis, `DemoSeeder`, README.

## [1.x] — 2024–2025 · Versi awal (Tugas Akhir)
- Peminjaman studio dan pemesanan jasa musik berbasis Bootstrap/jQuery, persetujuan admin, laporan masalah, dan tutorial alat.
