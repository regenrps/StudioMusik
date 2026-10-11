<?php

namespace Tests\Feature;

use App\Enums\StatusPeminjaman;
use App\Models\Peminjaman;
use App\Notifications\PeminjamanDiperbarui;
use App\Notifications\PengingatPeminjaman;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SinkronStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_pengajuan_yang_tidak_diproses_menjadi_kedaluwarsa(): void
    {
        Carbon::setTestNow('2030-01-07 17:05:00');
        $p = Peminjaman::factory()->pada('2030-01-07', '17:00', '18:00')->create();
        $nanti = Peminjaman::factory()->pada('2030-01-07', '19:00', '20:00')->create();

        $this->artisan('studio:sinkron-status')->assertSuccessful();

        $this->assertSame(StatusPeminjaman::Kedaluwarsa, $p->refresh()->status);
        $this->assertSame(StatusPeminjaman::Menunggu, $nanti->refresh()->status);
        Notification::assertSentTo($p->user, PeminjamanDiperbarui::class);
    }

    public function test_peminjaman_disetujui_tanpa_checkin_ditandai_tidak_hadir(): void
    {
        Carbon::setTestNow('2030-01-07 18:10:00');
        $p = Peminjaman::factory()->pada('2030-01-07', '17:00', '18:00')->status(StatusPeminjaman::Disetujui)->create();
        $berlangsung = Peminjaman::factory()->pada('2030-01-07', '17:00', '18:00')->status(StatusPeminjaman::Berlangsung)->create();

        $this->artisan('studio:sinkron-status')->assertSuccessful();

        $this->assertSame(StatusPeminjaman::TidakHadir, $p->refresh()->status);
        $this->assertSame(StatusPeminjaman::Berlangsung, $berlangsung->refresh()->status, 'Yang sudah check-in tidak boleh ditandai tidak hadir.');
    }

    public function test_pengingat_dikirim_sekali_menjelang_jadwal(): void
    {
        Carbon::setTestNow('2030-01-07 15:00:00');
        $dekat = Peminjaman::factory()->pada('2030-01-07', '17:00', '19:00')->status(StatusPeminjaman::Disetujui)->create();
        $jauh = Peminjaman::factory()->pada('2030-01-09', '17:00', '19:00')->status(StatusPeminjaman::Disetujui)->create();

        $this->artisan('studio:sinkron-status');
        $this->artisan('studio:sinkron-status');

        Notification::assertSentToTimes($dekat->user, PengingatPeminjaman::class, 1);
        Notification::assertNotSentTo($jauh->user, PengingatPeminjaman::class);
        $this->assertNotNull($dekat->refresh()->pengingat_at);
    }
}
