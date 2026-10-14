<?php

namespace Tests\Feature;

use App\Enums\StatusPeminjaman;
use App\Models\LogAktivitas;
use App\Models\Peminjaman;
use App\Models\Ruangan;
use App\Models\User;
use App\Support\Berkas;
use App\Support\EksporCsv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KeamananTest extends TestCase
{
    use RefreshDatabase;

    public function test_unggahan_bernama_php_tidak_pernah_disimpan_sebagai_php(): void
    {
        Storage::fake('public');

        // Gambar valid (lolos validasi `image`) tetapi bernama shell.php.
        $polyglot = UploadedFile::fake()->image('shell.php');
        $path = Berkas::simpan($polyglot, 'ruangan', Berkas::PUBLIK);

        $this->assertStringEndsNotWith('.php', $path);
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $this->assertTrue(in_array($ext, [...Berkas::EKSTENSI_AMAN, 'bin'], true), 'Ekstensi tidak aman: '.$ext);
        $this->assertSame('jpg', Berkas::ekstensiAman(UploadedFile::fake()->image('foto.JPG')));
        $this->assertSame('bin', Berkas::ekstensiAman(UploadedFile::fake()->create('x.phtml', 1, 'text/x-php')));
    }

    public function test_open_redirect_ditolak(): void
    {
        $aman = route('beranda');

        $this->assertSame('/akun', tujuan_aman('/akun', $aman));
        $this->assertSame(url('/admin'), tujuan_aman(url('/admin'), $aman));

        foreach ([
            'https://evil.com', '//evil.com/x', 'http://localhost.evil.com/x', 'javascript:alert(1)',
            "/\\evil.com", 'http://user@evil.com', "http://localhost\r\n.evil.com", null, 123,
        ] as $jahat) {
            $this->assertSame($aman, tujuan_aman($jahat, $aman), 'Lolos: '.var_export($jahat, true));
        }
    }

    public function test_notifikasi_dengan_url_luar_tidak_mengarahkan_keluar(): void
    {
        $user = User::factory()->create();
        $user->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'uji',
            'data' => ['judul' => 'x', 'pesan' => 'y', 'url' => 'https://evil.com/phish'],
        ]);
        $id = $user->notifications()->first()->id;

        $this->actingAs($user)->get(route('notifikasi.buka', $id))->assertRedirect(route('notifikasi.index'));
    }

    public function test_header_keamanan_dan_request_id(): void
    {
        $respons = $this->get(route('beranda'))->assertOk();

        $csp = (string) $respons->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[A-Za-z0-9+\/=]+'/", $csp);
        $respons->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $respons->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertNotEmpty($respons->headers->get('X-Request-Id'));

        // Skrip inline tema memakai nonce yang sama dengan header CSP.
        preg_match("/'nonce-([^']+)'/", $csp, $m);
        $respons->assertSee('nonce="'.$m[1].'"', false);
    }

    public function test_halaman_ber_login_tidak_disimpan_cache(): void
    {
        $respons = $this->actingAs(User::factory()->create())->get(route('akun.dashboard'))->assertOk();

        $this->assertStringContainsString('no-store', (string) $respons->headers->get('Cache-Control'));
    }

    public function test_email_berisi_crlf_ditolak(): void
    {
        $this->post(route('register'), [
            'username' => 'Penyusup',
            'email' => "a@contoh.test\r\nBcc: korban@contoh.test",
            'no_wa' => '081234567890',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'setuju' => '1',
        ])->assertSessionHasErrors('email');

        $this->assertSame(0, User::count());
    }

    public function test_ekspor_csv_menetralkan_rumus(): void
    {
        $this->assertSame("'=HYPERLINK(\"http://evil\")", EksporCsv::sel('=HYPERLINK("http://evil")'));
        $this->assertSame("'+1+1", EksporCsv::sel('+1+1'));
        $this->assertSame("'@SUM(A1)", EksporCsv::sel('@SUM(A1)'));
        $this->assertSame('Latihan band', EksporCsv::sel('Latihan band'));
        $this->assertSame(5, EksporCsv::sel(5));
    }

    public function test_sanitizer_konten_membuang_atribut_berbahaya(): void
    {
        $html = (string) konten('<p/onclick=alert(1)>Halo</p><p onmouseover="x()">A</p><script>alert(1)</script><a href="javascript:x">b</a>');

        $this->assertStringNotContainsStringIgnoringCase('onclick', $html);
        $this->assertStringNotContainsStringIgnoringCase('onmouseover', $html);
        $this->assertStringNotContainsStringIgnoringCase('<script', $html);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $html);
        $this->assertStringContainsString('Halo', $html);
        $this->assertStringContainsString('<p>A</p>', $html, 'Tag yang diizinkan tetap ada, tanpa atribut.');

        $md = (string) konten("[klik](javascript:alert(1)) <img src=x onerror=alert(1)>");
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $md);
        $this->assertStringNotContainsString('<img', $md);
    }

    public function test_masuk_dan_gagal_masuk_tercatat_di_log_audit(): void
    {
        User::factory()->create(['email' => 'mhs@contoh.test']);

        $this->post(route('login'), ['email' => 'mhs@contoh.test', 'password' => 'salah']);
        $this->post(route('login'), ['email' => 'tidakada@contoh.test', 'password' => 'salah']);
        $this->post(route('login'), ['email' => 'mhs@contoh.test', 'password' => 'password']);
        $this->post(route('logout'));

        $this->assertSame(2, LogAktivitas::where('aksi', 'gagal_masuk')->count());
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'masuk']);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'keluar']);

        $gagal = LogAktivitas::where('aksi', 'gagal_masuk')->first();
        $this->assertArrayHasKey('email', $gagal->properti);
        $this->assertArrayNotHasKey('password', $gagal->properti);
    }

    public function test_akses_ditolak_dicatat_di_log_keamanan(): void
    {
        Log::spy();

        $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertForbidden();

        Log::shouldHaveReceived('channel')->with('keamanan')->atLeast()->once();
    }

    public function test_foto_jaminan_dihapus_setelah_masa_retensi(): void
    {
        $this->seninPagi();
        Storage::fake('private');
        Storage::disk('private')->put('jaminan/lama.jpg', 'x');
        Storage::disk('private')->put('jaminan/aktif.jpg', 'x');
        $ruangan = Ruangan::factory()->create();

        $lama = Peminjaman::factory()->for($ruangan)->pada('2029-11-01', '17:00', '18:00')
            ->status(StatusPeminjaman::Selesai)->create(['img_jaminan' => 'jaminan/lama.jpg']);
        $aktif = Peminjaman::factory()->for($ruangan)->pada('2030-01-08', '17:00', '18:00')
            ->status(StatusPeminjaman::Disetujui)->create(['img_jaminan' => 'jaminan/aktif.jpg']);

        $this->artisan('studio:bersihkan-jaminan')->assertSuccessful();

        $this->assertNull($lama->refresh()->img_jaminan);
        Storage::disk('private')->assertMissing('jaminan/lama.jpg');
        $this->assertSame('jaminan/aktif.jpg', $aktif->refresh()->img_jaminan);
        Storage::disk('private')->assertExists('jaminan/aktif.jpg');
    }

    public function test_log_audit_lama_dipangkas(): void
    {
        config(['logging.retensi_audit_hari' => 30]);
        LogAktivitas::catat(null, 'lama', 'x', [], null)->forceFill(['created_at' => now()->subDays(40)])->save();
        LogAktivitas::catat(null, 'baru', 'y', [], null);

        $this->artisan('model:prune', ['--model' => [LogAktivitas::class]])->assertSuccessful();

        $this->assertDatabaseMissing('log_aktivitas', ['aksi' => 'lama']);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'baru']);
    }

    public function test_ekspor_log_aktivitas_hanya_untuk_admin(): void
    {
        Notification::fake();
        LogAktivitas::catat(null, 'uji', '=cmd|calc', [], null);

        $this->actingAs(User::factory()->k3l()->create())->get(route('admin.log.ekspor'))->assertForbidden();

        $respons = $this->actingAs(User::factory()->admin()->create())->get(route('admin.log.ekspor'))->assertOk();
        $this->assertStringContainsString("'=cmd|calc", $respons->streamedContent());
    }
}
