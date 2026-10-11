<?php

namespace Tests\Feature;

use App\Enums\StatusPeminjaman;
use App\Models\Peminjaman;
use App\Models\Ruangan;
use App\Models\User;
use App\Notifications\AktivitasPeminjaman;
use App\Notifications\PeminjamanBaru;
use App\Notifications\PeminjamanDiperbarui;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PeminjamanTest extends TestCase
{
    use RefreshDatabase;

    private Ruangan $ruangan;

    private User $peminjam;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seninPagi();
        Notification::fake();
        Storage::fake('private');

        $this->ruangan = Ruangan::factory()->create();
        $this->peminjam = User::factory()->create();
        $this->admin = User::factory()->admin()->create();
    }

    /** @return array<string, mixed> */
    private function dataPengajuan(array $ubah = []): array
    {
        return array_merge([
            'ruangan' => $this->ruangan->slug,
            'tgl_pinjam' => '2030-01-08',
            'waktu_mulai' => '17:00',
            'durasi' => 120,
            'jumlah_orang' => 5,
            'ket_keperluan' => 'Latihan band untuk Dies Natalis',
            'jaminan' => UploadedFile::fake()->image('ktm.jpg'),
            'setuju_sop' => '1',
        ], $ubah);
    }

    public function test_peminjam_dapat_mengajukan_peminjaman(): void
    {
        $respons = $this->actingAs($this->peminjam)->post(route('akun.peminjaman.store'), $this->dataPengajuan());

        $p = Peminjaman::firstOrFail();
        $respons->assertRedirect(route('akun.peminjaman.show', $p));

        $this->assertSame(StatusPeminjaman::Menunggu, $p->status);
        $this->assertSame('19:00:00', $p->waktu_selesai);
        $this->assertMatchesRegularExpression('/^STD-\d{6}-[A-Z0-9]{4}$/', $p->kode);
        Storage::disk('private')->assertExists($p->img_jaminan);
        $this->assertStringStartsWith('jaminan/', $p->img_jaminan);
        Notification::assertSentTo($this->admin, PeminjamanBaru::class);
        $this->assertDatabaseHas('log_aktivitas', ['subjek_id' => $p->getKey(), 'aksi' => 'diajukan']);
    }

    public function test_slot_yang_sama_tidak_dapat_dipesan_dua_kali(): void
    {
        Peminjaman::factory()->for($this->ruangan)->pada('2030-01-08', '18:00', '19:00')->create();

        $this->actingAs($this->peminjam)
            ->post(route('akun.peminjaman.store'), $this->dataPengajuan())
            ->assertSessionHasErrors('aturan');

        $this->assertSame(1, Peminjaman::count());
        $this->assertSame([], Storage::disk('private')->allFiles(), 'Berkas jaminan harus dihapus bila pengajuan gagal.');
    }

    public function test_peminjam_dibatasi_jumlah_peminjaman_aktif(): void
    {
        Peminjaman::factory()->for($this->peminjam, 'user')->for($this->ruangan)
            ->pada('2030-01-10', '17:00', '18:00')->status(StatusPeminjaman::Disetujui)->create();

        $this->actingAs($this->peminjam)
            ->post(route('akun.peminjaman.store'), $this->dataPengajuan())
            ->assertSessionHasErrors('aturan');
    }

    public function test_peminjaman_lama_yang_sudah_lewat_tidak_dihitung_aktif(): void
    {
        Peminjaman::factory()->for($this->peminjam, 'user')->for($this->ruangan)
            ->pada('2030-01-03', '17:00', '18:00')->status(StatusPeminjaman::Disetujui)->create();

        $this->actingAs($this->peminjam)
            ->post(route('akun.peminjaman.store'), $this->dataPengajuan())
            ->assertSessionHasNoErrors();
    }

    public function test_validasi_formulir(): void
    {
        $this->actingAs($this->peminjam)
            ->post(route('akun.peminjaman.store'), $this->dataPengajuan(['jaminan' => null, 'durasi' => 30, 'ket_keperluan' => 'abc', 'setuju_sop' => null]))
            ->assertSessionHasErrors(['jaminan', 'durasi', 'ket_keperluan', 'setuju_sop']);
    }

    public function test_staf_tidak_dapat_mengajukan_peminjaman(): void
    {
        $this->actingAs($this->admin)->post(route('akun.peminjaman.store'), $this->dataPengajuan())->assertForbidden();
    }

    public function test_admin_dapat_menyetujui_dan_peminjam_diberi_tahu(): void
    {
        $p = Peminjaman::factory()->for($this->peminjam, 'user')->for($this->ruangan)->pada('2030-01-08', '17:00', '19:00')->create();

        $this->actingAs($this->admin)
            ->post(route('admin.peminjaman.setujui', $p), ['catatan' => 'Kunci di pos satpam'])
            ->assertSessionHasNoErrors();

        $p->refresh();
        $this->assertSame(StatusPeminjaman::Disetujui, $p->status);
        $this->assertSame('Kunci di pos satpam', $p->ket_admin);
        $this->assertSame($this->admin->getKey(), $p->diproses_oleh);
        Notification::assertSentTo($this->peminjam, PeminjamanDiperbarui::class);
    }

    public function test_admin_tidak_dapat_menyetujui_jadwal_yang_bentrok(): void
    {
        // Data lama: dua pengajuan bertumpuk sebelum validasi server ada.
        $a = Peminjaman::factory()->for($this->ruangan)->pada('2030-01-08', '17:00', '19:00')->status(StatusPeminjaman::Disetujui)->create();
        $b = Peminjaman::factory()->for($this->ruangan)->pada('2030-01-08', '18:00', '20:00')->create();

        $this->actingAs($this->admin)->post(route('admin.peminjaman.setujui', $b))->assertSessionHasErrors('aturan');
        $this->assertSame(StatusPeminjaman::Menunggu, $b->refresh()->status);
    }

    public function test_penolakan_wajib_disertai_alasan(): void
    {
        $p = Peminjaman::factory()->for($this->ruangan)->create();

        $this->actingAs($this->admin)->post(route('admin.peminjaman.tolak', $p), ['alasan' => ''])->assertSessionHasErrors('alasan');

        $this->actingAs($this->admin)->post(route('admin.peminjaman.tolak', $p), ['alasan' => 'Foto KTM tidak jelas'])->assertSessionHasNoErrors();
        $this->assertSame(StatusPeminjaman::Ditolak, $p->refresh()->status);
        $this->assertSame('Foto KTM tidak jelas', $p->ket_admin);
    }

    public function test_peminjam_dapat_membatalkan_pengajuan(): void
    {
        $p = Peminjaman::factory()->for($this->peminjam, 'user')->for($this->ruangan)->create();

        $this->actingAs($this->peminjam)->post(route('akun.peminjaman.batal', $p), ['alasan' => 'Jadwal latihan diundur'])->assertSessionHasNoErrors();

        $this->assertSame(StatusPeminjaman::Dibatalkan, $p->refresh()->status);
        Notification::assertSentTo($this->admin, AktivitasPeminjaman::class);
    }

    public function test_pembatalan_mendadak_atas_peminjaman_disetujui_ditolak(): void
    {
        Carbon::setTestNow('2030-01-07 16:00:00');
        $p = Peminjaman::factory()->for($this->peminjam, 'user')->for($this->ruangan)
            ->pada('2030-01-07', '17:00', '19:00')->status(StatusPeminjaman::Disetujui)->create();

        $this->actingAs($this->peminjam)->post(route('akun.peminjaman.batal', $p), ['alasan' => 'Mendadak ada kuliah'])->assertSessionHasErrors('aturan');
        $this->assertSame(StatusPeminjaman::Disetujui, $p->refresh()->status);
    }

    public function test_alur_checkin_checkout_dan_ulasan(): void
    {
        $p = Peminjaman::factory()->for($this->peminjam, 'user')->for($this->ruangan)
            ->pada('2030-01-07', '17:00', '19:00')->status(StatusPeminjaman::Disetujui)->create();

        // Terlalu awal
        $this->actingAs($this->peminjam)->post(route('akun.peminjaman.checkin', $p), ['foto' => UploadedFile::fake()->image('awal.jpg')])
            ->assertSessionHasErrors('aturan');

        Carbon::setTestNow('2030-01-07 16:50:00');
        $this->actingAs($this->peminjam)->post(route('akun.peminjaman.checkin', $p), ['foto' => UploadedFile::fake()->image('awal.jpg')])
            ->assertSessionHasNoErrors();
        $this->assertSame(StatusPeminjaman::Berlangsung, $p->refresh()->status);
        Storage::disk('private')->assertExists($p->img_kondisi_awal);

        Carbon::setTestNow('2030-01-07 18:55:00');
        $this->actingAs($this->peminjam)->post(route('akun.peminjaman.checkout', $p), ['foto' => UploadedFile::fake()->image('akhir.jpg'), 'catatan' => 'Aman'])
            ->assertSessionHasNoErrors();
        $p->refresh();
        $this->assertSame(StatusPeminjaman::Selesai, $p->status);
        $this->assertNotNull($p->checkout_at);

        $this->actingAs($this->peminjam)->post(route('akun.peminjaman.ulasan', $p), ['rating' => 5, 'review' => 'Mantap'])->assertSessionHasNoErrors();
        $this->assertSame(5, $p->refresh()->rating);

        $this->actingAs($this->peminjam)->post(route('akun.peminjaman.ulasan', $p), ['rating' => 1])->assertSessionHasErrors('aturan');
        $this->assertSame(5, $p->refresh()->rating, 'Ulasan hanya boleh sekali.');
    }

    public function test_rating_harus_antara_satu_dan_lima(): void
    {
        $p = Peminjaman::factory()->for($this->peminjam, 'user')->for($this->ruangan)->status(StatusPeminjaman::Selesai)->create();

        $this->actingAs($this->peminjam)->post(route('akun.peminjaman.ulasan', $p), ['rating' => 9])->assertSessionHasErrors('rating');
    }

    public function test_halaman_wizard_dan_detail_dapat_dibuka(): void
    {
        $p = Peminjaman::factory()->for($this->peminjam, 'user')->for($this->ruangan)->create();

        $this->actingAs($this->peminjam)->get(route('akun.peminjaman.create'))->assertOk()->assertSee('Pilih tanggal');
        $this->actingAs($this->peminjam)->get(route('akun.peminjaman.show', $p))->assertOk()->assertSee($p->kode);
        $this->actingAs($this->peminjam)->get(route('akun.peminjaman.index'))->assertOk();
        $this->actingAs($this->peminjam)->get(route('akun.dashboard'))->assertOk();
    }
}
