<?php

namespace Tests\Feature;

use App\Enums\StatusPeminjaman;
use App\Models\JadwalTutup;
use App\Models\Peminjaman;
use App\Models\Ruangan;
use App\Services\KetersediaanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class KetersediaanTest extends TestCase
{
    use RefreshDatabase;

    private Ruangan $ruangan;

    private KetersediaanService $layanan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seninPagi(); // Senin 7 Jan 2030, 10.00
        $this->ruangan = Ruangan::factory()->create();
        $this->layanan = app(KetersediaanService::class);
    }

    private function cek(string $tanggal, string $mulai, int $durasi, ?int $abaikan = null): ?string
    {
        return $this->layanan->cekSlot($this->ruangan, Carbon::parse($tanggal), $mulai, $durasi, $abaikan);
    }

    public function test_slot_kosong_pada_hari_operasional_tersedia(): void
    {
        $this->assertNull($this->cek('2030-01-08', '17:00', 120));
    }

    public function test_akhir_pekan_ditolak(): void
    {
        $this->assertStringContainsString('Senin–Jumat', (string) $this->cek('2030-01-12', '17:00', 60));
    }

    public function test_di_luar_jam_operasional_ditolak(): void
    {
        $this->assertStringContainsString('17.00 dan 21.00', (string) $this->cek('2030-01-08', '20:00', 120));
        $this->assertStringContainsString('17.00 dan 21.00', (string) $this->cek('2030-01-08', '16:00', 60));
    }

    public function test_durasi_di_luar_batas_ditolak(): void
    {
        $this->assertStringContainsString('Durasi', (string) $this->cek('2030-01-08', '17:00', 30));
        $this->assertStringContainsString('Durasi', (string) $this->cek('2030-01-08', '17:00', 300));
    }

    public function test_jam_mulai_harus_kelipatan_interval(): void
    {
        $this->assertStringContainsString('kelipatan', (string) $this->cek('2030-01-08', '17:05', 60));
    }

    public function test_jadwal_bentrok_ditolak_tetapi_jadwal_bersambung_diizinkan(): void
    {
        Peminjaman::factory()->for($this->ruangan)->pada('2030-01-08', '17:00', '19:00')->create();

        $this->assertStringContainsString('sudah terisi', (string) $this->cek('2030-01-08', '18:00', 60));
        $this->assertStringContainsString('sudah terisi', (string) $this->cek('2030-01-08', '17:00', 60));
        $this->assertNull($this->cek('2030-01-08', '19:00', 60), 'Jadwal yang bersambung tidak boleh dianggap bentrok.');
    }

    public function test_peminjaman_ditolak_atau_dibatalkan_tidak_menempati_slot(): void
    {
        Peminjaman::factory()->for($this->ruangan)->pada('2030-01-08', '17:00', '19:00')->status(StatusPeminjaman::Ditolak)->create();
        Peminjaman::factory()->for($this->ruangan)->pada('2030-01-08', '17:00', '19:00')->status(StatusPeminjaman::Dibatalkan)->create();

        $this->assertNull($this->cek('2030-01-08', '17:00', 120));
    }

    public function test_ruangan_lain_tidak_saling_memblokir(): void
    {
        $lain = Ruangan::factory()->create();
        Peminjaman::factory()->for($lain)->pada('2030-01-08', '17:00', '19:00')->create();

        $this->assertNull($this->cek('2030-01-08', '17:00', 120));
    }

    public function test_jadwal_tutup_sepanjang_hari_memblokir(): void
    {
        JadwalTutup::create(['tgl_mulai' => '2030-01-08', 'tgl_selesai' => '2030-01-10', 'keterangan' => 'UAS']);

        $this->assertStringContainsString('UAS', (string) $this->cek('2030-01-09', '17:00', 60));
        $this->assertNull($this->cek('2030-01-11', '17:00', 60));
    }

    public function test_jadwal_tutup_sebagian_hanya_memblokir_jam_tertentu(): void
    {
        JadwalTutup::create(['tgl_mulai' => '2030-01-08', 'tgl_selesai' => '2030-01-08', 'jam_mulai' => '17:00', 'jam_selesai' => '18:00', 'keterangan' => 'Rapat UKM']);

        $this->assertNotNull($this->cek('2030-01-08', '17:30', 60));
        $this->assertNull($this->cek('2030-01-08', '18:00', 60));
    }

    public function test_pengajuan_mendadak_ditolak(): void
    {
        Carbon::setTestNow('2030-01-07 16:30:00');

        $this->assertStringContainsString('paling lambat', (string) $this->cek('2030-01-07', '17:00', 60));
        $this->assertNull($this->cek('2030-01-07', '17:30', 60));
    }

    public function test_pemesanan_terlalu_jauh_ditolak(): void
    {
        $this->assertStringContainsString('30 hari', (string) $this->cek('2030-03-04', '17:00', 60));
    }

    public function test_ringkasan_bulan_memberi_status_yang_tepat(): void
    {
        Peminjaman::factory()->for($this->ruangan)->pada('2030-01-08', '17:00', '18:00')->create();
        Peminjaman::factory()->for($this->ruangan)->pada('2030-01-09', '17:00', '21:00')->status(StatusPeminjaman::Disetujui)->create();

        $hari = $this->layanan->ringkasanBulan($this->ruangan, Carbon::parse('2030-01-01'));

        $this->assertSame('lewat', $hari['2030-01-04']['status']);
        $this->assertSame('sebagian', $hari['2030-01-08']['status']);
        $this->assertSame('penuh', $hari['2030-01-09']['status']);
        $this->assertSame('tersedia', $hari['2030-01-10']['status']);
        $this->assertSame('libur', $hari['2030-01-12']['status']);
    }

    public function test_detail_hari_tidak_membocorkan_identitas_peminjam(): void
    {
        $p = Peminjaman::factory()->for($this->ruangan)->pada('2030-01-08', '17:00', '18:00')->create();

        $json = json_encode($this->layanan->detailHari($this->ruangan, Carbon::parse('2030-01-08')));

        $this->assertStringNotContainsString($p->user->username, $json);
        $this->assertStringNotContainsString($p->kode, $json);
        $this->assertStringContainsString('17:00', $json);
    }
}
