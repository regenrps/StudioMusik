<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Menyeragamkan penyimpanan berkas:
 *  - Kolom berkas kini menyimpan path relatif terhadap disk (bukan hanya nama file).
 *  - Berkas sensitif (KTP/KTM jaminan, foto kondisi, lampiran pesanan jasa,
 *    foto laporan) dipindahkan dari folder publik ke disk `private`
 *    sehingga hanya bisa diakses lewat route yang memeriksa hak akses.
 */
return new class extends Migration
{
    private string $publicRoot;

    private string $privateRoot;

    public function __construct()
    {
        $this->publicRoot = public_path('storage');
        $this->privateRoot = storage_path('app/private');
    }

    public function up(): void
    {
        // Pengujian otomatis memakai basis data kosong; jangan sentuh berkas sungguhan.
        if (app()->environment('testing')) {
            return;
        }

        // Berkas publik: cukup tambahkan prefix folder.
        $this->prefix('data_ruangan', 'id_ruangan', 'thumbnail', 'img_upload/data_ruangan/');
        $this->prefix('detail_data_ruangan', 'id_detail_ruangan', 'img_ruangan', 'img_upload/data_ruangan/');
        $this->prefix('master_jasa_musik', 'id_jasa_musik', 'gambar', 'img_upload/jasa_musik/');
        $this->prefix('tutorial_penggunaan_alat', 'id_tutorial', 'gambar_alat', 'img_upload/tutorial_alat/');

        // Berkas privat: pindahkan seluruh isi folder lama.
        $this->moveDir($this->publicRoot.'/img_upload/pesanan_jadwal', $this->privateRoot.'/jaminan');
        $this->moveDir($this->publicRoot.'/img_upload', $this->privateRoot.'/jaminan');
        $this->moveDir($this->publicRoot.'/img_upload/kondisi/awal', $this->privateRoot.'/kondisi');
        $this->moveDir($this->publicRoot.'/img_upload/kondisi/akhir', $this->privateRoot.'/kondisi');
        $this->moveDir($this->publicRoot.'/pesanan/jasa_musik_file', $this->privateRoot.'/jasa/lampiran');
        $this->moveDir($this->publicRoot.'/img_upload/laporan', $this->privateRoot.'/laporan');

        $this->prefix('pesanan_jadwal_studio', 'id_pesanan_jadwal_studio', 'img_jaminan', 'jaminan/');
        $this->prefix('pesanan_jadwal_studio', 'id_pesanan_jadwal_studio', 'img_kondisi_awal', 'kondisi/');
        $this->prefix('pesanan_jadwal_studio', 'id_pesanan_jadwal_studio', 'img_kondisi_akhir', 'kondisi/');

        foreach (DB::table('pesanan_jasa_musik_informasi')->where('tipe_field', 'file')->get() as $info) {
            if (! $info->value_field || str_contains($info->value_field, '/')) {
                continue;
            }
            DB::table('pesanan_jasa_musik_informasi')->where('id', $info->id)->update([
                'value_field' => 'jasa/lampiran/'.$info->value_field,
                'nama_asli' => preg_replace('/^\d+-/', '', $info->value_field),
            ]);
        }

        foreach (DB::table('laporan')->get() as $laporan) {
            // Data lama tersimpan sebagai JSON yang di-encode dua kali.
            $gambar = $laporan->gambar;
            while (is_string($gambar)) {
                $decoded = json_decode($gambar, true);
                if ($decoded === null) {
                    break;
                }
                $gambar = $decoded;
            }
            $gambar = is_array($gambar) ? $gambar : array_filter([is_string($gambar) ? $gambar : null]);
            $baru = array_map(fn ($p) => 'laporan/'.basename((string) $p), $gambar);
            DB::table('laporan')->where('id_laporan', $laporan->id_laporan)->update(['gambar' => json_encode(array_values($baru))]);
        }
    }

    public function down(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $this->unprefix('data_ruangan', 'id_ruangan', 'thumbnail');
        $this->unprefix('detail_data_ruangan', 'id_detail_ruangan', 'img_ruangan');
        $this->unprefix('master_jasa_musik', 'id_jasa_musik', 'gambar');
        $this->unprefix('tutorial_penggunaan_alat', 'id_tutorial', 'gambar_alat');
        $this->unprefix('pesanan_jadwal_studio', 'id_pesanan_jadwal_studio', 'img_jaminan');
        $this->unprefix('pesanan_jadwal_studio', 'id_pesanan_jadwal_studio', 'img_kondisi_awal');
        $this->unprefix('pesanan_jadwal_studio', 'id_pesanan_jadwal_studio', 'img_kondisi_akhir');

        DB::table('pesanan_jasa_musik_informasi')->where('tipe_field', 'file')
            ->update(['value_field' => DB::raw("SUBSTRING_INDEX(value_field, '/', -1)")]);

        $this->moveDir($this->privateRoot.'/jaminan', $this->publicRoot.'/img_upload/pesanan_jadwal');
        $this->moveDir($this->privateRoot.'/jasa/lampiran', $this->publicRoot.'/pesanan/jasa_musik_file');
        $this->moveDir($this->privateRoot.'/laporan', $this->publicRoot.'/img_upload/laporan');
        foreach (File::exists($this->privateRoot.'/kondisi') ? File::files($this->privateRoot.'/kondisi') : [] as $file) {
            $sub = str_starts_with($file->getFilename(), 'akhir') ? 'akhir' : 'awal';
            File::ensureDirectoryExists($this->publicRoot.'/img_upload/kondisi/'.$sub);
            File::move($file->getPathname(), $this->publicRoot.'/img_upload/kondisi/'.$sub.'/'.$file->getFilename());
        }
    }

    private function prefix(string $table, string $key, string $column, string $prefix): void
    {
        foreach (DB::table($table)->whereNotNull($column)->get([$key, $column]) as $row) {
            $value = $row->{$column};
            if ($value === '' || str_contains($value, '/')) {
                continue;
            }
            DB::table($table)->where($key, $row->{$key})->update([$column => $prefix.$value]);
        }
    }

    private function unprefix(string $table, string $key, string $column): void
    {
        foreach (DB::table($table)->whereNotNull($column)->get([$key, $column]) as $row) {
            DB::table($table)->where($key, $row->{$key})->update([$column => basename((string) $row->{$column})]);
        }
    }

    private function moveDir(string $from, string $to): void
    {
        if (! File::isDirectory($from)) {
            return;
        }
        File::ensureDirectoryExists($to);
        foreach (File::files($from) as $file) {
            $target = $to.'/'.$file->getFilename();
            if (! File::exists($target)) {
                File::move($file->getPathname(), $target);
            }
        }
    }
};
