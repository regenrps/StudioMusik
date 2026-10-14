# Sistem Informasi Studio Musik ITERA

Aplikasi web untuk peminjaman Studio Musik ITERA dan pemesanan jasa musik (jingle, aransemen, minus one) oleh civitas akademika ITERA.

## Fitur per peran

| Peran | Yang dapat dilakukan |
|---|---|
| **Publik** (tanpa login) | Melihat beranda dengan status studio hari ini, jadwal mingguan (identitas peminjam disembunyikan), katalog jasa musik, detail ruangan beserta ulasan, dan tutorial alat. |
| **Peminjam** (`user`) | Mengajukan peminjaman lewat wizard 3 langkah dengan kalender ketersediaan. Melakukan check-in dan check-out dengan foto kondisi studio, membatalkan, dan memberi ulasan. Memesan jasa musik dengan formulir dinamis, lalu memantau progres dan mengunduh hasilnya. Menerima notifikasi lewat email dan di dasbor. |
| **K3L** (`k3l`) | Melihat dasbor, daftar dan kalender peminjaman, foto jaminan dan kondisi ruangan. Membuat serta menindaklanjuti laporan masalah. |
| **Admin** (`admin`) | Semua akses K3L, ditambah: menyetujui, menolak, membatalkan, dan menyelesaikan peminjaman; mengelola pesanan jasa (jadwal produksi, kirim hasil); mengelola ruangan, layanan beserta formulirnya, jadwal tutup (UTS/UAS dan kegiatan UKM), tutorial, dan pengguna; mengatur pengaturan operasional; melihat log aktivitas; mengekspor CSV. |

## Teknologi

- **Backend:** Laravel 13 (PHP 8.4+, disarankan 8.5), MySQL 8.0+ (Docker: MySQL 9.7 LTS)
- **Frontend:** Blade components, Tailwind CSS v4, Alpine.js, dan Chart.js, dibangun dengan Vite 8
- **Pengujian:** PHPUnit 13
- **Ikon:** Heroicons (`blade-ui-kit/blade-heroicons`)
- **Font:** Plus Jakarta Sans, di-host sendiri tanpa CDN

## Arsitektur singkat

```
app/
├── Enums/           Status eksplisit: StatusPeminjaman, StatusPesananJasa, StatusLaporan, Role, TipeField
├── Services/
│   ├── KetersediaanService   Satu-satunya sumber aturan jadwal (bentrok, jam operasional, penutupan)
│   ├── PeminjamanService     Semua transisi status peminjaman + audit + notifikasi
│   ├── PesananJasaService    Alur pesanan jasa musik
│   └── PengaturanService     Pengaturan yang dapat diubah admin (tabel `pengaturan`)
├── Policies/        Hak akses per data (pemilik vs staf)
├── Notifications/   Email + notifikasi in-app dari satu definisi
├── Support/         Berkas (penyimpanan), Notifier (pengiriman aman), EksporCsv
└── Http/Controllers/{Akun,Admin,Auth}
resources/views/components/   Design system (button, badge, card, modal, input, file-input, dll.)
resources/js/modules/         Komponen Alpine: wizard peminjaman, kalender mingguan, unggah berkas, dll.
```

### Alur status

```
Peminjaman:  pending ──setujui──▶ approved ──check-in──▶ in_use ──check-out──▶ completed
                │                    │
                ├─ rejected          ├─ cancelled (peminjam ≥ N jam sebelum mulai / admin)
                ├─ cancelled         └─ no_show   (otomatis bila tidak check-in sampai jadwal berakhir)
                └─ expired (otomatis bila belum diproses saat jadwal dimulai)

Pesanan jasa: pending ──▶ approved (antrean) ──▶ in_production ──▶ completed
                 ├─ rejected                 └─ cancelled (admin)
                 └─ cancelled (pemesan, hanya saat pending)
```

### Jaminan integritas jadwal

Pengecekan bentrok dilakukan **di server** dalam transaksi database, dengan *row lock* pada ruangan dan pengguna. Pengajuan yang datang bersamaan diproses berurutan, sehingga dua orang tidak mungkin mendapat slot yang sama. Rentang jadwal bersifat setengah terbuka `[mulai, selesai)`, jadi jadwal yang bersambung (17.00–18.00 lalu 18.00–19.00) diperbolehkan.

### Penyimpanan berkas

| Disk | Lokasi | Isi |
|---|---|---|
| `public` | `public/storage/` | Foto ruangan, gambar layanan, gambar tutorial |
| `private` | `storage/app/private/` | KTM/KTP jaminan, foto kondisi, lampiran dan hasil jasa, foto laporan |

Berkas privat hanya dapat diakses lewat `/berkas/...` setelah pemeriksaan *policy*. Foto dari kamera ponsel diperkecil otomatis di browser sebelum diunggah.

## Menjalankan dengan Docker (paling mudah)

Hanya butuh [Docker Desktop](https://www.docker.com/products/docker-desktop/). PHP, MySQL, Node, dan Laragon tidak perlu dipasang.

```bash
docker compose up -d --build
```

Build pertama memakan beberapa menit karena mengunduh image dan dependensi. Setelah semua container berstatus *healthy*:

| Layanan | Alamat | Keterangan |
|---|---|---|
| Aplikasi | http://localhost:8088 | Data demo otomatis. Kata sandi semua akun: `Studio123` |
| Mailpit | http://localhost:8025 | Semua email notifikasi tertangkap di sini; tidak ada yang benar-benar terkirim |
| MySQL 9.7 | `127.0.0.1:3307` | User `studio`, DB `studio_musik` |
| Adminer | http://localhost:8081 | Opsional, jalankan dengan `docker compose --profile tools up -d` |

Akun demo yang tersedia:
- `admin@demo.test` — Admin
- `k3l@demo.test` — K3L
- `mahasiswa@demo.test` — Peminjam

Perintah sehari-hari:

```bash
docker compose ps                                 # status container
docker compose logs -f app                        # log aplikasi (juga tersimpan di volume storage)
docker compose exec app php artisan test          # jalankan pengujian di dalam container
docker compose exec app php artisan tinker
docker compose down                               # hentikan (data tetap tersimpan di volume)
docker compose down -v                            # hentikan DAN hapus semua data (DB, unggahan, log)
docker compose up -d --build                      # setelah mengubah kode
```

Container yang berjalan:
- `app`: Apache + PHP. Menjalankan migrasi otomatis saat start.
- `queue`: worker email/notifikasi.
- `scheduler`: menjalankan `studio:sinkron-status` setiap 5 menit, serta retensi KTM dan log setiap hari.
- `mysql`
- `mailpit`

Variabel `DOCKER_*` dapat diubah lewat shell atau berkas `.env`, misalnya `DOCKER_APP_PORT=9000` (bawaan 8088, agar tidak bentrok dengan `php artisan serve` di 8000) atau `DOCKER_SEED_DEMO=false`. Daftar lengkapnya ada di `docker-compose.yml`. Untuk membuka aplikasi dari HP di jaringan yang sama, set `DOCKER_APP_BIND=0.0.0.0` dan `DOCKER_APP_URL=http://<IP-laptop>:8088`.

**Image produksi:**
```bash
docker build --build-arg COMPOSER_DEV=false -t studio-musik-itera:3.0 .
```
Jalankan dengan `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY`, kredensial DB/SMTP sungguhan, dan `TRUSTED_PROXIES` bila berada di balik reverse proxy.

## Instalasi lokal tanpa Docker (Laragon/XAMPP)

Prasyarat: **PHP ≥ 8.4** (disarankan 8.5) dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`, `intl`, `zip`; **Composer ≥ 2.8** (versi lama gagal di PHP 8.5); Node.js ≥ 20.19; MySQL 8.0+.

> **Laragon:** PHP 8.5 tersedia di `C:\laragon\bin\php\php-8.5.11-Win32-vs17-x64`. Aktifkan lewat **Menu → PHP → Version → php-8.5.11**, lalu buka ulang terminal dan cek `php -v`. Perbarui Composer global dengan `composer self-update` dari terminal *Run as administrator*.
>
> **Folder OneDrive:** `composer install` bisa gagal dengan pesan *"Could not delete … antivirus or the Windows Search Indexer"* karena OneDrive mengunci berkas yang baru diekstrak. Ulangi perintahnya, atau jeda sinkronisasi OneDrive selama instalasi.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# atur DB_* di .env, buat database-nya, lalu:
php artisan migrate
php artisan db:seed                       # membuat akun admin pertama (lihat ADMIN_* di .env)
php artisan db:seed --class=DemoSeeder    # opsional: data contoh untuk demo
npm run build                             # atau `npm run dev` saat mengembangkan
php artisan serve
```

> **Error `SQLSTATE[HY000] [2002] No connection could be made…`** berarti MySQL belum berjalan. Di Laragon klik **Start All** (atau jalankan Docker seperti di atas). Pastikan juga `DB_*` di `.env` cocok.
>
> **Unggahan audio/hasil gagal ("gagal diunggah")** berarti batas PHP terlalu kecil. Naikkan `upload_max_filesize=110M` dan `post_max_size=120M` di `php.ini` Laragon (Menu → PHP → php.ini). Konfigurasi Docker sudah mengaturnya.
>
> **Tampilan berantakan (tanpa CSS) saat dibuka lewat port forwarding VS Code/devtunnels/ngrok**, dan Chrome meminta izin "akses aplikasi lain di perangkat ini": tunnel mengirim domain asli lewat header `X-Forwarded-*`, dan Laravel baru membacanya bila `TRUSTED_PROXIES=*` diisi di `.env`. Docker sudah mengaturnya secara bawaan. Agar tautan di email juga memakai domain tunnel, isi `APP_URL` (atau `DOCKER_APP_URL`) dengan URL tunnel tersebut.

Akun demo dari `DemoSeeder` memakai kata sandi `Studio123` untuk semuanya: `admin@demo.test`, `k3l@demo.test`, dan `mahasiswa@demo.test`.

### Memperbarui dari versi lama

Migrasi `2026_10_12_*` mengonversi data lama secara otomatis. Kode status P/Y/N/X diubah menjadi status eksplisit, tabel `detail_pesanan_jadwal_studio` digabung ke tabel utama, dan berkas sensitif dipindahkan ke disk privat. **Backup database dan folder `public/storage` sebelum menjalankan `php artisan migrate`.** Semua migrasi memiliki `down()`, sehingga bisa dikembalikan dengan `php artisan migrate:rollback --step=7`. Riwayat perubahan lengkap ada di [CHANGELOG.md](CHANGELOG.md).

## Produksi

1. Atur `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` sesuai domain (gunakan HTTPS), dan `MAIL_*` dengan *App Password* Gmail.
2. Optimasi:
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   php artisan migrate --force
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
3. **Antrean email.** Set `QUEUE_CONNECTION=database`, lalu jalankan worker dengan supervisor/systemd:
   `php artisan queue:work --tries=3 --max-time=3600`
4. **Scheduler.** Tambahkan cron berikut, lalu set `STUDIO_SINKRON_VIA_REQUEST=false`:
   `* * * * * cd /path/aplikasi && php artisan schedule:run >> /dev/null 2>&1`
   Scheduler menjalankan `studio:sinkron-status` setiap 5 menit: mengubah pengajuan yang lewat menjadi kedaluwarsa, menandai tidak hadir, dan mengirim pengingat.
   Jika hosting tidak menyediakan cron, biarkan `STUDIO_SINKRON_VIA_REQUEST=true`. Sinkronisasi lalu berjalan otomatis dari kunjungan web (paling sering setiap 5 menit, setelah respons dikirim).
5. Pastikan `storage/` dan `bootstrap/cache/` dapat ditulis oleh web server.
6. Pemeriksaan kesehatan untuk uptime monitor: `GET /api/health`.

## Pengujian

```bash
# sekali saja: buat basis data uji
mysql -uroot -e "CREATE DATABASE studio_musik_test"

php artisan config:clear   # wajib: konfigurasi yang ter-cache akan mengabaikan phpunit.xml
php artisan test
```

Test suite (70 pengujian) mencakup:
- logika ketersediaan: bentrok, jadwal bersambung, akhir pekan, penutupan, jeda pengajuan, dan batas hari;
- alur peminjaman end-to-end dan alur jasa musik;
- otorisasi dan akses berkas privat;
- sinkronisasi otomatis, autentikasi dan *rate limit*;
- uji asap seluruh halaman;
- regresi keamanan: unggahan `.php`, open redirect, CSP, CRLF email, formula CSV, sanitasi HTML, dan retensi data.

Pengujian akan **menolak berjalan** jika basis data yang dipakai tidak berakhiran `_test`.

## Logging

| Lokasi | Isi | Retensi |
|---|---|---|
| Menu **Log Aktivitas** (tabel `log_aktivitas`) | Jejak audit: pengajuan, persetujuan, check-in/out, perubahan data master/pengguna/pengaturan, masuk/keluar/gagal masuk. Ada filter Keamanan dan ekspor CSV | `LOG_RETENSI_AUDIT_HARI` (365) |
| `storage/logs/laravel-*.log` | Error aplikasi, beserta URL, user, IP, dan `request_id` | `LOG_DAILY_DAYS` (14) |
| `storage/logs/keamanan-*.log` (JSON) | Login, gagal login, reset sandi, 403/419/429, akses berkas privat (siapa membuka KTM siapa) | `LOG_KEAMANAN_DAYS` (90) |
| `storage/logs/akses-*.log` (JSON) | Satu baris per request: metode, path, status, durasi, user, IP | `LOG_AKSES_DAYS` (30) |

Setiap respons membawa header `X-Request-Id`. Cari nilainya di semua berkas log untuk menelusuri satu kejadian dari awal sampai akhir.

## Keamanan

Lapisan pertahanan yang terpasang:
- **Akses & data:**
  - otorisasi per peran dan per kepemilikan data (Policy);
  - berkas pribadi di disk privat dan hanya dilayani setelah pemeriksaan;
  - KTM/KTP dihapus otomatis setelah masa retensi (`STUDIO_RETENSI_JAMINAN_HARI`).
- **Unggahan:** nama acak dengan daftar putih ekstensi; folder unggahan publik tidak bisa mengeksekusi skrip.
- **Konsistensi jadwal:** cek bentrok di server dalam transaksi + *row lock*.
- **Header HTTP:** CSP berbasis nonce, `X-Frame-Options`, HSTS (HTTPS), `no-store` untuk halaman ber-login, TrustHosts.
- **Serangan umum:** rate limit login/daftar/reset/aksi, perlindungan CSRF, cegah open redirect, sanitasi HTML konten admin, netralisasi formula CSV.

Checklist produksi:
- [ ] `APP_ENV=production` dan `APP_DEBUG=false`
- [ ] `APP_URL` memakai `https://`
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] `TRUSTED_PROXIES` diisi IP proxy (jangan `*` bila server dapat diakses langsung)
- [ ] Ganti kata sandi semua akun bawaan/demo
- [ ] Jangan menjalankan `DemoSeeder`
- [ ] Batasi akses SSH dan basis data dari luar; jadwalkan backup DB dan folder `storage/app/private`
- [ ] Jangan membagikan aplikasi lewat tunnel publik (devtunnels/ngrok) selama `APP_DEBUG=true`: halaman error akan memperlihatkan kode sumber dan path server

> **Catatan versi:** aplikasi memakai Laravel 13, yang masih menerima patch keamanan. `composer audit` tidak menemukan advisori. Jalankan `composer update` dan `npm update` secara berkala, lalu `composer audit` dan `npm audit`.

## Pengaturan operasional

Nilai bawaan ada di `config/studio.php` dan dapat diubah admin lewat menu **Pengaturan**, tanpa deploy ulang:

- hari dan jam operasional, serta interval pilihan jam;
- durasi minimum dan maksimum;
- batas hari pemesanan ke depan dan jeda pengajuan;
- maksimal peminjaman aktif per orang dan maksimal orang per sesi;
- batas pembatalan dan waktu pengingat;
- rentang tenggat jasa musik;
- email notifikasi tambahan dan pengumuman.

---

Dikembangkan oleh Regen untuk Tugas Akhir — *Rancang Bangun Sistem Informasi Jasa Studio Musik dengan Metode RAD (Studi Kasus Studio Musik ITERA)*.
