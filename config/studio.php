<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Studio Musik
|--------------------------------------------------------------------------
|
| Nilai di sini adalah nilai bawaan. Admin dapat mengubah sebagian besar
| nilai ini lewat halaman Pengaturan (disimpan di tabel `pengaturan`).
|
*/

return [

    'nama' => env('STUDIO_NAMA', 'Studio Musik ITERA'),
    'singkatan' => 'SMI',
    'lokasi' => 'Gedung D Lantai 3, Institut Teknologi Sumatera',
    'maps_url' => env('STUDIO_MAPS_URL', 'https://maps.app.goo.gl/83vvmsvktTwXoemw8'),

    'kontak' => [
        'email' => env('STUDIO_EMAIL', 'musikitera@gmail.com'),
        'whatsapp' => env('STUDIO_WHATSAPP', '6281271543205'),
        // Username Instagram tanpa "@"; dikosongkan = tidak ditampilkan
        'instagram' => env('STUDIO_INSTAGRAM', ''),
    ],

    'pengembang' => [
        'nama' => 'Regen',
        'url' => 'https://www.instagram.com/regenrps',
    ],

    /*
    | Pengaturan bawaan. Kunci di bawah ini dapat ditimpa admin.
    */
    'default' => [
        // Hari operasional (ISO-8601: 1 = Senin ... 7 = Minggu)
        'hari_operasional' => [1, 2, 3, 4, 5],
        'jam_buka' => '17:00',
        'jam_tutup' => '21:00',
        // Langkah pilihan jam mulai (menit)
        'interval_menit' => 10,
        'durasi_min_menit' => 60,
        'durasi_maks_menit' => 240,
        // Seberapa jauh ke depan peminjaman boleh diajukan
        'batas_hari_pemesanan' => 30,
        // Pengajuan paling lambat sekian menit sebelum jam mulai
        'jeda_minimal_menit' => 60,
        'maks_booking_aktif' => 1,
        'maks_orang' => 10,
        // Pembatalan oleh peminjam paling lambat sekian jam sebelum mulai
        'batas_pembatalan_jam' => 2,
        // Pengingat dikirim sekian menit sebelum jam mulai
        'pengingat_menit' => 180,
        // Rentang tenggat pesanan jasa musik
        'jasa_tenggat_min_hari' => 3,
        'jasa_tenggat_maks_hari' => 90,
        // Email tambahan penerima notifikasi admin (dipisah koma)
        'email_notifikasi' => env('STUDIO_EMAIL', 'musikitera@gmail.com'),
        'pengumuman' => '',
    ],

    // Jalankan sinkronisasi status (kedaluwarsa, tidak hadir, pengingat) dari
    // request web bila scheduler tidak tersedia. Matikan jika cron sudah aktif.
    'sinkron_via_request' => env('STUDIO_SINKRON_VIA_REQUEST', true),

    // Content-Security-Policy: enforce (bawaan) | report (hanya laporkan) | off
    'csp_mode' => env('CSP_MODE', 'enforce'),

    // Privasi: hapus foto KTM/KTP jaminan sekian hari setelah peminjaman
    // berakhir (selesai/ditolak/dibatalkan/kedaluwarsa/tidak hadir). 0 = simpan.
    'retensi_jaminan_hari' => (int) env('STUDIO_RETENSI_JAMINAN_HARI', 30),

    'upload' => [
        'gambar_maks_kb' => 5120,
        'audio_maks_kb' => 20480,
        'gambar_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'lampiran_mimes' => ['mp3', 'wav', 'mp4', 'm4a', 'pdf', 'jpg', 'jpeg', 'png', 'zip'],
        'hasil_mimes' => ['mp3', 'wav', 'mp4', 'm4a', 'flac', 'zip', 'pdf'],
    ],

    'sop' => [
        'Peminjaman studio hanya dapat dilakukan pada hari Senin–Jumat pukul 17.00–21.00.',
        'Unggah foto kondisi studio saat mulai (check-in) dan setelah selesai (check-out) melalui website.',
        'Setelah selesai, rapikan alat ke kondisi semula dan bawa kembali seluruh barang bawaan.',
        'Jumlah maksimal orang di dalam studio adalah 10 orang.',
        'Kerusakan alat wajib diganti dengan spesifikasi yang sama. Pelanggaran dapat dikenai sanksi.',
        'Studio tidak dapat dipinjam selama UTS/UAS dan kegiatan UKMBSM ITERA.',
        'Pemesanan jasa musik mengikuti syarat & ketentuan masing-masing layanan.',
    ],
];
