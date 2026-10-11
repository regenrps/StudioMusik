<?php

namespace App\Services;

use App\Enums\StatusPesananJasa;
use App\Enums\TipeField;
use App\Exceptions\AturanBisnisException;
use App\Models\JasaMusik;
use App\Models\LogAktivitas;
use App\Models\PesananJasa;
use App\Models\User;
use App\Notifications\AktivitasPesananJasa;
use App\Notifications\PesananJasaBaru;
use App\Notifications\PesananJasaDiperbarui;
use App\Support\Berkas;
use App\Support\Notifier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Alur pesanan jasa musik:
 * pending → approved (antrean) → in_production → completed
 *     ↘ rejected / cancelled
 */
class PesananJasaService
{
    /**
     * @param  array{judul:string, tenggat_produksi:string, keterangan?:string|null, fields?:array<string, mixed>}  $data
     * @param  array<string, UploadedFile>  $berkas  Kunci = nama input field (field_{id})
     */
    public function buat(User $user, JasaMusik $jasa, array $data, array $berkas = []): PesananJasa
    {
        if (! $jasa->is_active) {
            throw new AturanBisnisException('Layanan ini sedang tidak menerima pesanan.');
        }

        $tersimpan = [];

        try {
            $pesanan = DB::transaction(function () use ($user, $jasa, $data, $berkas, &$tersimpan) {
                $pesanan = PesananJasa::create([
                    'kode' => PesananJasa::buatKode(),
                    'id_user' => $user->getKey(),
                    'id_jasa_musik' => $jasa->getKey(),
                    'status' => StatusPesananJasa::Menunggu,
                    'judul' => trim($data['judul']),
                    'tenggat_produksi' => $data['tenggat_produksi'],
                    'keterangan' => filled($data['keterangan'] ?? null) ? trim($data['keterangan']) : null,
                ]);

                foreach ($jasa->field as $field) {
                    $nilai = null;
                    $namaAsli = null;

                    if ($field->jenis_field === TipeField::Berkas) {
                        $file = $berkas[$field->input_name] ?? null;
                        if ($file instanceof UploadedFile) {
                            $nilai = Berkas::simpan($file, 'jasa/lampiran');
                            $tersimpan[] = $nilai;
                            $namaAsli = Berkas::namaAsli($file);
                        }
                    } else {
                        $nilai = $data['fields'][$field->input_name] ?? null;
                        $nilai = is_string($nilai) ? trim($nilai) : $nilai;
                    }

                    if (blank($nilai)) {
                        continue;
                    }

                    $pesanan->informasi()->create([
                        'id_field' => $field->id,
                        'tipe_field' => $field->jenis_field->value,
                        'nama_field' => $field->nama_field,
                        'value_field' => (string) $nilai,
                        'nama_asli' => $namaAsli,
                    ]);
                }

                LogAktivitas::catat($pesanan, 'diajukan', 'Memesan '.$jasa->nama_jenis_jasa.' "'.$pesanan->judul.'"', [], $user);

                return $pesanan;
            });
        } catch (Throwable $e) {
            foreach ($tersimpan as $path) {
                Berkas::hapus($path);
            }
            throw $e;
        }

        Notifier::staf(new PesananJasaBaru($pesanan));

        return $pesanan;
    }

    public function setujui(PesananJasa $pesanan, User $admin, Carbon $tglProduksi, Carbon $tenggat, ?string $catatan = null, bool $langsungProduksi = false): PesananJasa
    {
        $this->pastikanStatus($pesanan, [StatusPesananJasa::Menunggu], 'Pesanan ini sudah diproses sebelumnya.');
        $this->validasiJadwal($tglProduksi, $tenggat);

        $pesanan->update([
            'status' => $langsungProduksi ? StatusPesananJasa::Diproduksi : StatusPesananJasa::Disetujui,
            'tgl_produksi' => $tglProduksi->toDateString(),
            'tenggat_produksi' => $tenggat->toDateString(),
            'keterangan_admin' => $catatan ?: null,
            'diproses_oleh' => $admin->getKey(),
            'diproses_at' => now(),
            'mulai_produksi_at' => $langsungProduksi ? now() : null,
        ]);

        LogAktivitas::catat($pesanan, 'disetujui', $langsungProduksi ? 'Menyetujui dan langsung memulai produksi' : 'Menyetujui pesanan',
            array_filter(['produksi' => $tglProduksi->toDateString(), 'tenggat' => $tenggat->toDateString(), 'catatan' => $catatan]), $admin);

        Notifier::kirim($pesanan->user, new PesananJasaDiperbarui($pesanan));

        return $pesanan;
    }

    public function perbaruiJadwal(PesananJasa $pesanan, User $admin, Carbon $tglProduksi, Carbon $tenggat, ?string $catatan = null): PesananJasa
    {
        $this->pastikanStatus($pesanan, [StatusPesananJasa::Disetujui, StatusPesananJasa::Diproduksi], 'Jadwal hanya dapat diubah untuk pesanan yang sedang berjalan.');
        $this->validasiJadwal($tglProduksi, $tenggat, cekLampau: false);

        $pesanan->update([
            'tgl_produksi' => $tglProduksi->toDateString(),
            'tenggat_produksi' => $tenggat->toDateString(),
            'keterangan_admin' => $catatan ?? $pesanan->keterangan_admin,
        ]);

        LogAktivitas::catat($pesanan, 'jadwal_diubah', 'Mengubah jadwal produksi',
            ['produksi' => $tglProduksi->toDateString(), 'tenggat' => $tenggat->toDateString()], $admin);

        return $pesanan;
    }

    public function tolak(PesananJasa $pesanan, User $admin, string $alasan): PesananJasa
    {
        $this->pastikanStatus($pesanan, [StatusPesananJasa::Menunggu], 'Pesanan ini sudah diproses sebelumnya.');

        $pesanan->update([
            'status' => StatusPesananJasa::Ditolak,
            'keterangan_admin' => $alasan,
            'diproses_oleh' => $admin->getKey(),
            'diproses_at' => now(),
        ]);

        LogAktivitas::catat($pesanan, 'ditolak', 'Menolak pesanan', ['alasan' => $alasan], $admin);
        Notifier::kirim($pesanan->user, new PesananJasaDiperbarui($pesanan));

        return $pesanan;
    }

    public function mulaiProduksi(PesananJasa $pesanan, User $admin): PesananJasa
    {
        $this->pastikanStatus($pesanan, [StatusPesananJasa::Disetujui], 'Produksi hanya dapat dimulai untuk pesanan dalam antrean.');

        $pesanan->update([
            'status' => StatusPesananJasa::Diproduksi,
            'mulai_produksi_at' => now(),
            'tgl_produksi' => $pesanan->tgl_produksi ?? today(),
        ]);

        LogAktivitas::catat($pesanan, 'diproduksi', 'Memulai produksi', [], $admin);
        Notifier::kirim($pesanan->user, new PesananJasaDiperbarui($pesanan));

        return $pesanan;
    }

    public function selesaikan(PesananJasa $pesanan, User $admin, ?UploadedFile $hasil, ?string $tautan, ?string $catatan): PesananJasa
    {
        $this->pastikanStatus($pesanan, [StatusPesananJasa::Diproduksi, StatusPesananJasa::Disetujui], 'Pesanan ini tidak sedang diproduksi.');

        if (! $hasil && blank($tautan)) {
            throw new AturanBisnisException('Unggah berkas hasil atau cantumkan tautan hasil produksi.');
        }

        $path = $hasil ? Berkas::simpan($hasil, 'jasa/hasil') : null;
        $lama = $pesanan->hasil_berkas;

        try {
            $pesanan->update([
                'status' => StatusPesananJasa::Selesai,
                'hasil_berkas' => $path ?? $pesanan->hasil_berkas,
                'hasil_tautan' => $tautan ?: null,
                'hasil_catatan' => $catatan ?: null,
                'mulai_produksi_at' => $pesanan->mulai_produksi_at ?? now(),
                'selesai_at' => now(),
            ]);
        } catch (Throwable $e) {
            Berkas::hapus($path);
            throw $e;
        }

        if ($path && $lama && $lama !== $path) {
            Berkas::hapus($lama);
        }

        LogAktivitas::catat($pesanan, 'selesai', 'Menyelesaikan produksi dan mengirim hasil', [], $admin);
        Notifier::kirim($pesanan->user, new PesananJasaDiperbarui($pesanan));

        return $pesanan;
    }

    public function batalkan(PesananJasa $pesanan, User $oleh, string $alasan): PesananJasa
    {
        $olehPemesan = $oleh->getKey() === $pesanan->id_user && ! $oleh->isAdmin();

        if ($olehPemesan && ! $pesanan->bisaDibatalkanPemesan()) {
            throw new AturanBisnisException('Pesanan yang sudah diterima tidak dapat dibatalkan sendiri. Silakan hubungi admin.');
        }
        $this->pastikanStatus($pesanan, StatusPesananJasa::active(), 'Pesanan ini tidak dapat dibatalkan.');

        $pesanan->update([
            'status' => StatusPesananJasa::Dibatalkan,
            'alasan_batal' => $alasan,
            'dibatalkan_at' => now(),
        ]);

        LogAktivitas::catat($pesanan, 'dibatalkan', $olehPemesan ? 'Pemesan membatalkan pesanan' : 'Admin membatalkan pesanan', ['alasan' => $alasan], $oleh);

        if ($olehPemesan) {
            Notifier::staf(new AktivitasPesananJasa($pesanan, 'dibatalkan'));
        } else {
            Notifier::kirim($pesanan->user, new PesananJasaDiperbarui($pesanan));
        }

        return $pesanan;
    }

    public function beriUlasan(PesananJasa $pesanan, int $rating, ?string $review): PesananJasa
    {
        if (! $pesanan->bisaDiulas()) {
            throw new AturanBisnisException('Ulasan hanya dapat diberikan sekali untuk pesanan yang telah selesai.');
        }

        $pesanan->update([
            'rating' => max(1, min(5, $rating)),
            'review' => $review ?: null,
            'diulas_at' => now(),
        ]);
        LogAktivitas::catat($pesanan, 'diulas', 'Memberi ulasan '.$rating.'/5');

        return $pesanan;
    }

    private function validasiJadwal(Carbon $produksi, Carbon $tenggat, bool $cekLampau = true): void
    {
        if ($cekLampau && $produksi->lt(today())) {
            throw new AturanBisnisException('Tanggal mulai produksi tidak boleh di masa lalu.');
        }
        if ($tenggat->lt($produksi)) {
            throw new AturanBisnisException('Tenggat tidak boleh sebelum tanggal mulai produksi.');
        }
    }

    /** @param  array<StatusPesananJasa>  $diizinkan */
    private function pastikanStatus(PesananJasa $pesanan, array $diizinkan, string $pesan): void
    {
        if (! in_array($pesanan->status, $diizinkan, true)) {
            throw new AturanBisnisException($pesan);
        }
    }
}
