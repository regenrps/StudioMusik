<?php

namespace Tests\Feature;

use App\Models\Peminjaman;
use App\Models\PesananJasa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OtorisasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        $this->get(route('akun.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_peminjam_tidak_dapat_melihat_peminjaman_orang_lain(): void
    {
        $milikLain = Peminjaman::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('akun.peminjaman.show', $milikLain))
            ->assertForbidden();
    }

    public function test_peminjam_tidak_dapat_membatalkan_peminjaman_orang_lain(): void
    {
        $milikLain = Peminjaman::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('akun.peminjaman.batal', $milikLain), ['alasan' => 'iseng saja'])
            ->assertForbidden();
    }

    public function test_peminjam_tidak_dapat_membuka_panel_admin(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_k3l_dapat_memantau_tetapi_tidak_dapat_memproses(): void
    {
        $k3l = User::factory()->k3l()->create();
        $p = Peminjaman::factory()->create();

        $this->actingAs($k3l)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($k3l)->get(route('admin.peminjaman.show', $p))->assertOk();
        $this->actingAs($k3l)->get(route('admin.laporan.index'))->assertOk();

        $this->actingAs($k3l)->post(route('admin.peminjaman.setujui', $p))->assertForbidden();
        $this->actingAs($k3l)->get(route('admin.jasa.index'))->assertForbidden();
        $this->actingAs($k3l)->get(route('admin.pengaturan.edit'))->assertForbidden();
    }

    public function test_berkas_jaminan_hanya_untuk_pemilik_dan_staf(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('jaminan/ktm.jpg', 'isi');
        $p = Peminjaman::factory()->create(['img_jaminan' => 'jaminan/ktm.jpg']);

        $this->actingAs(User::factory()->create())->get(route('berkas.peminjaman', [$p, 'jaminan']))->assertForbidden();
        $this->actingAs($p->user)->get(route('berkas.peminjaman', [$p, 'jaminan']))->assertOk();
        $this->actingAs(User::factory()->k3l()->create())->get(route('berkas.peminjaman', [$p, 'jaminan']))->assertOk();
    }

    public function test_berkas_tidak_dapat_diakses_langsung_dari_folder_publik(): void
    {
        $this->assertSame('private', config('filesystems.disks.private.visibility'));
        $this->assertStringStartsWith(storage_path(), config('filesystems.disks.private.root'));
    }

    public function test_pesanan_jasa_orang_lain_tidak_dapat_dilihat(): void
    {
        $pesanan = PesananJasa::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('akun.jasa.show', $pesanan))->assertForbidden();
        $this->actingAs($pesanan->user)->get(route('akun.jasa.show', $pesanan))->assertOk();
    }

    public function test_akun_nonaktif_dikeluarkan_otomatis(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('akun.dashboard'))->assertOk();

        $user->update(['is_active' => false]);

        $this->actingAs($user)->get(route('akun.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_tidak_dapat_menonaktifkan_akun_sendiri(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.pengguna.status', $admin));

        $this->assertTrue($admin->refresh()->is_active);
    }
}
