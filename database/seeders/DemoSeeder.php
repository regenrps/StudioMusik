<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\StatusLaporan;
use App\Enums\StatusPeminjaman;
use App\Enums\StatusPesananJasa;
use App\Models\JadwalTutup;
use App\Models\JasaMusik;
use App\Models\Laporan;
use App\Models\Peminjaman;
use App\Models\PesananJasa;
use App\Models\Ruangan;
use App\Models\TutorialAlat;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Data contoh untuk demo & pengujian manual.
 * Jalankan: php artisan db:seed --class=DemoSeeder
 *
 * Akun (kata sandi semuanya: Studio123):
 *  - admin@demo.test   (Admin)
 *  - k3l@demo.test     (K3L)
 *  - mahasiswa@demo.test (Peminjam)
 */
class DemoSeeder extends Seeder
{
    public const SANDI = 'Studio123';

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoSeeder tidak boleh dijalankan di produksi.');

            return;
        }

        $akun = fn (string $email, string $nama, Role $peran) => User::query()->firstOrCreate(['email' => $email], [
            'username' => $nama, 'no_wa' => '081234567890', 'password' => self::SANDI, 'user_role' => $peran, 'is_active' => true,
        ]);

        $akun('admin@demo.test', 'Admin Studio', Role::Admin);
        $akun('k3l@demo.test', 'Petugas K3L', Role::K3L);
        $mhs = $akun('mahasiswa@demo.test', 'Mahasiswa Demo', Role::User);
        $lain = User::factory()->count(6)->create();

        $ruangan = Ruangan::query()->first() ?? Ruangan::create([
            'nama_ruangan' => 'Studio Musik ITERA',
            'deskripsi' => 'Studio full band dengan peredam suara untuk latihan, rekaman demo, dan kolaborasi civitas ITERA.',
            'kapasitas' => 10,
            'fasilitas' => "Drum set akustik\nAmpli gitar Fender & Bugera\nAmpli bass Hartke\nKeyboard + ampli Peavey\nMixer Proel 16 channel\nAC & peredam suara",
            'lokasi' => 'Gedung D Lantai 3',
            'is_active' => true,
        ]);

        if (JasaMusik::query()->doesntExist()) {
            foreach ([
                ['Jingle', 'Jingle orisinal untuk acara dan organisasi kampus.', ['Genre' => 'text', 'Durasi' => 'text', 'Lirik' => 'textarea', 'Referensi' => 'file']],
                ['Aransemen', 'Aransemen ulang lagu sesuai kebutuhan penampilan.', ['Judul lagu' => 'text', 'Genre tujuan' => 'text', 'File lagu' => 'file']],
                ['Minus One', 'Minus one untuk latihan atau tampil live.', ['Instrumen yang dihilangkan' => 'text', 'File lagu' => 'file']],
            ] as $i => [$nama, $ringkas, $field]) {
                $jasa = JasaMusik::create([
                    'nama_jenis_jasa' => $nama, 'ringkasan' => $ringkas,
                    'deskripsi' => $ringkas.' Dikerjakan oleh tim komposer UKMBSM ITERA.',
                    'sk' => "1. Data kebutuhan harus jelas dan lengkap.\n2. Revisi maksimal dua kali.\n3. Hasil hanya untuk keperluan internal ITERA.",
                    'estimasi_hari' => 7 + $i * 3, 'is_active' => true, 'urutan' => $i + 1,
                ]);
                $urutan = 1;
                foreach ($field as $label => $jenis) {
                    $jasa->field()->create(['nama_field' => $label, 'jenis_field' => $jenis, 'is_required' => $jenis !== 'file', 'urutan' => $urutan++]);
                }
            }
        }

        if (TutorialAlat::query()->doesntExist()) {
            foreach (['Mixer Analog' => 'Mixer', 'Ampli Gitar' => 'Amplifier', 'Drum Set' => 'Drum'] as $alat => $kategori) {
                TutorialAlat::create([
                    'nama_alat' => $alat, 'kategori' => $kategori, 'gambar_alat' => '',
                    'deskripsi' => "1. Pastikan volume pada posisi minimum.\n2. Nyalakan perangkat.\n3. Naikkan volume perlahan.\n4. Setelah selesai, kembalikan ke posisi semula dan matikan.",
                ]);
            }
        }

        // Peminjaman tersebar di sekitar minggu ini.
        $senin = today()->startOfWeek(Carbon::MONDAY);
        $buat = function (User $u, Carbon $tgl, string $mulai, string $selesai, StatusPeminjaman $status, array $ekstra = []) use ($ruangan) {
            return Peminjaman::factory()->for($u, 'user')->for($ruangan)
                ->pada($tgl->toDateString(), $mulai, $selesai)->status($status)
                ->create(array_merge(['img_jaminan' => null], $ekstra));
        };

        $buat($lain[0], $senin->copy()->subWeek(), '17:00', '19:00', StatusPeminjaman::Selesai, ['rating' => 5, 'review' => 'Alat lengkap dan ruangan dingin.', 'diulas_at' => now()]);
        $buat($lain[1], $senin->copy()->subWeek()->addDay(), '18:00', '20:00', StatusPeminjaman::Selesai, ['rating' => 4, 'review' => 'Mantap, tutorial mixernya membantu.', 'diulas_at' => now()]);
        $buat($lain[2], $senin->copy()->subWeek()->addDays(2), '17:00', '18:00', StatusPeminjaman::TidakHadir);
        $buat($lain[3], $senin->copy()->addWeek(), '17:00', '19:00', StatusPeminjaman::Disetujui);
        $buat($lain[4], $senin->copy()->addWeek()->addDay(), '19:00', '21:00', StatusPeminjaman::Menunggu);
        $buat($lain[5], $senin->copy()->addWeek()->addDays(2), '17:30', '19:30', StatusPeminjaman::Menunggu);
        $buat($mhs, $senin->copy()->addWeek()->addDays(3), '18:00', '20:00', StatusPeminjaman::Disetujui, ['ket_admin' => 'Kunci diambil di pos satpam Gedung D.']);

        JadwalTutup::query()->firstOrCreate(
            ['keterangan' => 'Kegiatan UKMBSM (contoh)'],
            ['tgl_mulai' => $senin->copy()->addWeeks(2)->addDays(4)->toDateString(), 'tgl_selesai' => $senin->copy()->addWeeks(2)->addDays(4)->toDateString()],
        );

        $jasa = JasaMusik::query()->urut()->get();
        PesananJasa::factory()->for($mhs, 'user')->for($jasa[0], 'jasa')->status(StatusPesananJasa::Diproduksi)
            ->create(['judul' => 'Jingle Dies Natalis', 'tgl_produksi' => today(), 'tenggat_produksi' => today()->addDays(10)]);
        PesananJasa::factory()->for($lain[0], 'user')->for($jasa[1] ?? $jasa[0], 'jasa')->status(StatusPesananJasa::Menunggu)
            ->create(['judul' => 'Aransemen Mars ITERA']);
        PesananJasa::factory()->for($lain[1], 'user')->for($jasa[2] ?? $jasa[0], 'jasa')->status(StatusPesananJasa::Selesai)
            ->create(['judul' => 'Minus One Lagu Daerah', 'hasil_tautan' => 'https://drive.google.com/', 'selesai_at' => now()->subDays(2), 'rating' => 5, 'review' => 'Hasilnya rapi dan cepat.', 'diulas_at' => now()]);

        Laporan::query()->firstOrCreate(['keterangan' => 'Senar gitar listrik putih putus (contoh).'], [
            'tgl_laporan' => today()->subDays(2), 'jenis_laporan' => 'Kerusakan Alat', 'status' => StatusLaporan::Baru, 'gambar' => [], 'id_ruangan' => $ruangan->getKey(),
        ]);

        $this->command?->info('Data demo dibuat. Masuk dengan admin@demo.test / k3l@demo.test / mahasiswa@demo.test, kata sandi: '.self::SANDI);
    }
}
