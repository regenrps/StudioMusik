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

- **Backend:** Laravel 10 (PHP 8.1+), MySQL 8
- **Frontend:** Blade components, Tailwind CSS v4, Alpine.js, dan Chart.js, dibangun dengan Vite
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

## Instalasi lokal (Laragon/XAMPP)

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

Akun demo dari `DemoSeeder` memakai kata sandi `Studio123` untuk semuanya: `admin@demo.test`, `k3l@demo.test`, dan `mahasiswa@demo.test`.

### Memperbarui dari versi lama

Migrasi `2026_10_12_*` mengonversi data lama secara otomatis. Kode status P/Y/N/X diubah menjadi status eksplisit, tabel `detail_pesanan_jadwal_studio` digabung ke tabel utama, dan berkas sensitif dipindahkan ke disk privat. **Backup database dan folder `public/storage` sebelum menjalankan `php artisan migrate`.** Semua migrasi memiliki `down()`, sehingga bisa dikembalikan dengan `php artisan migrate:rollback --step=7`.

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

Test suite (57 pengujian) mencakup:
- logika ketersediaan: bentrok, jadwal bersambung, akhir pekan, penutupan, jeda pengajuan, dan batas hari;
- alur peminjaman end-to-end dan alur jasa musik;
- otorisasi dan akses berkas privat;
- sinkronisasi otomatis, autentikasi dan *rate limit*;
- uji asap seluruh halaman.

Pengujian akan **menolak berjalan** jika basis data yang dipakai tidak berakhiran `_test`.

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
