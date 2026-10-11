<?php

namespace Tests\Feature;

use App\Enums\StatusPesananJasa;
use App\Models\JasaMusik;
use App\Models\PesananJasa;
use App\Models\User;
use App\Notifications\PesananJasaBaru;
use App\Notifications\PesananJasaDiperbarui;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PesananJasaTest extends TestCase
{
    use RefreshDatabase;

    private JasaMusik $jasa;

    private User $pemesan;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seninPagi();
        Notification::fake();
        Storage::fake('private');

        $this->jasa = JasaMusik::factory()->denganField()->create(['nama_jenis_jasa' => 'Jingle']);
        $this->pemesan = User::factory()->create();
        $this->admin = User::factory()->admin()->create();
    }

    private function fieldId(string $nama): string
    {
        return 'field_'.$this->jasa->field()->where('nama_field', $nama)->value('id');
    }

    public function test_pemesan_dapat_memesan_dengan_field_dinamis_dan_lampiran(): void
    {
        $respons = $this->actingAs($this->pemesan)->post(route('akun.jasa.store', $this->jasa), [
            'judul' => 'Jingle PPLK 2030',
            'tenggat_produksi' => '2030-01-21',
            'keterangan' => 'Nuansa semangat',
            'fields' => [$this->fieldId('Genre') => 'Pop rock'],
            $this->fieldId('Referensi') => UploadedFile::fake()->create('referensi.mp3', 500, 'audio/mpeg'),
            'setuju_sk' => '1',
        ]);

        $pesanan = PesananJasa::with('informasi')->firstOrFail();
        $respons->assertRedirect(route('akun.jasa.show', $pesanan));

        $this->assertSame(StatusPesananJasa::Menunggu, $pesanan->status);
        $this->assertCount(2, $pesanan->informasi);
        $this->assertSame('Pop rock', $pesanan->informasi->firstWhere('nama_field', 'Genre')->value_field);

        $lampiran = $pesanan->informasi->firstWhere('nama_field', 'Referensi');
        $this->assertSame('referensi.mp3', $lampiran->nama_asli);
        Storage::disk('private')->assertExists($lampiran->value_field);
        Notification::assertSentTo($this->admin, PesananJasaBaru::class);
    }

    public function test_field_wajib_dan_tenggat_divalidasi(): void
    {
        $this->actingAs($this->pemesan)->post(route('akun.jasa.store', $this->jasa), [
            'judul' => 'Jingle',
            'tenggat_produksi' => '2030-01-08', // kurang dari tenggat minimum
            'setuju_sk' => '1',
        ])->assertSessionHasErrors(['tenggat_produksi', 'fields.'.$this->fieldId('Genre')]);

        $this->assertSame(0, PesananJasa::count());
    }

    public function test_alur_admin_dari_antrean_hingga_selesai(): void
    {
        $pesanan = PesananJasa::factory()->for($this->pemesan, 'user')->for($this->jasa, 'jasa')->create();

        $this->actingAs($this->admin)->post(route('admin.jasa.setujui', $pesanan), [
            'tgl_produksi' => '2030-01-07', 'tenggat_produksi' => '2030-01-15', 'catatan' => 'Komposer: Andi',
        ])->assertSessionHasNoErrors();
        $this->assertSame(StatusPesananJasa::Disetujui, $pesanan->refresh()->status);

        $this->actingAs($this->admin)->post(route('admin.jasa.mulai', $pesanan))->assertSessionHasNoErrors();
        $this->assertSame(StatusPesananJasa::Diproduksi, $pesanan->refresh()->status);

        // Selesai tanpa hasil ditolak
        $this->actingAs($this->admin)->post(route('admin.jasa.selesai', $pesanan), [])->assertSessionHasErrors('tautan');

        $this->actingAs($this->admin)->post(route('admin.jasa.selesai', $pesanan), [
            'hasil' => UploadedFile::fake()->create('final.mp3', 800, 'audio/mpeg'),
            'catatan' => 'Selamat menikmati',
        ])->assertSessionHasNoErrors();
        $pesanan->refresh();
        $this->assertSame(StatusPesananJasa::Selesai, $pesanan->status);
        Storage::disk('private')->assertExists($pesanan->hasil_berkas);
        Notification::assertSentTo($this->pemesan, PesananJasaDiperbarui::class);

        $this->actingAs($this->pemesan)->get(route('berkas.jasa.hasil', $pesanan))->assertOk();
        $this->actingAs($this->pemesan)->post(route('akun.jasa.ulasan', $pesanan), ['rating' => 4])->assertSessionHasNoErrors();
        $this->assertSame(4, $pesanan->refresh()->rating);
    }

    public function test_pemesan_tidak_dapat_membatalkan_pesanan_yang_sudah_diterima(): void
    {
        $pesanan = PesananJasa::factory()->for($this->pemesan, 'user')->status(StatusPesananJasa::Disetujui)->create();

        $this->actingAs($this->pemesan)->post(route('akun.jasa.batal', $pesanan), ['alasan' => 'Tidak jadi'])->assertSessionHasErrors('aturan');
        $this->assertSame(StatusPesananJasa::Disetujui, $pesanan->refresh()->status);
    }

    public function test_layanan_nonaktif_tidak_dapat_dipesan(): void
    {
        $this->jasa->update(['is_active' => false]);

        $this->actingAs($this->pemesan)->get(route('akun.jasa.create', $this->jasa))->assertNotFound();
    }
}
