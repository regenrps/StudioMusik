<?php

namespace Tests\Feature;

use App\Enums\StatusPeminjaman;
use App\Models\JasaMusik;
use App\Models\Peminjaman;
use App\Models\PesananJasa;
use App\Models\Ruangan;
use App\Models\TutorialAlat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Uji asap: setiap halaman utama dapat dirender tanpa galat.
 */
class HalamanTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_publik(): void
    {
        $this->seninPagi();
        $ruangan = Ruangan::factory()->create();
        $jasa = JasaMusik::factory()->denganField()->create();
        $tutorial = TutorialAlat::create(['nama_alat' => 'Mixer', 'gambar_alat' => 'tutorial/x.jpg', 'deskripsi' => "1. Nyalakan\n2. Atur fader"]);

        foreach ([
            route('beranda'), route('jadwal'), route('layanan.index'), route('layanan.show', $jasa),
            route('ruangan.show', $ruangan), route('tutorial.index'), route('tutorial.show', $tutorial),
            route('login'), route('register'), route('password.request'),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/tidak-ada-halaman')->assertNotFound()->assertSee('Halaman tidak ditemukan');
        $this->getJson('/api/health')->assertOk()->assertJson(['status' => 'ok']);
    }

    public function test_jadwal_publik_tidak_membocorkan_identitas(): void
    {
        $this->seninPagi();
        $p = Peminjaman::factory()->pada('2030-01-08', '17:00', '19:00')->status(StatusPeminjaman::Disetujui)->create();

        $respons = $this->getJson(route('jadwal.data', ['mulai' => '2030-01-07', 'selesai' => '2030-01-13']))
            ->assertOk()
            ->assertJsonPath('events.0.judul', 'Terisi')
            ->assertJsonPath('events.0.mulai', '17:00');

        $this->assertStringNotContainsString($p->user->username, $respons->getContent());
        $this->assertStringNotContainsString($p->ket_keperluan, $respons->getContent());
    }

    public function test_halaman_admin(): void
    {
        $this->seninPagi();
        $admin = User::factory()->admin()->create();
        $p = Peminjaman::factory()->create();
        $j = PesananJasa::factory()->create();

        foreach ([
            route('admin.dashboard'), route('admin.peminjaman.index'), route('admin.peminjaman.kalender'),
            route('admin.peminjaman.show', $p), route('admin.jasa.index'), route('admin.jasa.show', $j),
            route('admin.layanan.index'), route('admin.layanan.create'), route('admin.layanan.edit', $j->jasa),
            route('admin.ruangan.index'), route('admin.ruangan.create'), route('admin.ruangan.edit', $p->ruangan),
            route('admin.jadwal-tutup.index'), route('admin.tutorial.index'), route('admin.tutorial.create'),
            route('admin.laporan.index'), route('admin.laporan.create'), route('admin.pengguna.index'),
            route('admin.pengaturan.edit'), route('admin.log.index'), route('profil.edit'), route('notifikasi.index'),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $this->actingAs($admin)->get(route('admin.peminjaman.ekspor'))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_pengaturan_dapat_diubah_dan_langsung_berlaku(): void
    {
        $admin = User::factory()->admin()->create();
        $data = array_merge(config('studio.default'), [
            'hari_operasional' => [1, 2, 3, 4, 5, 6],
            'jam_buka' => '16:00',
            'jam_tutup' => '22:00',
            'email_notifikasi' => 'studio@contoh.test',
        ]);

        $this->actingAs($admin)->put(route('admin.pengaturan.update'), $data)->assertSessionHasNoErrors();

        $this->assertSame([1, 2, 3, 4, 5, 6], pengaturan('hari_operasional'));
        $this->assertSame('16:00', pengaturan('jam_buka'));
    }
}
