<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menyatukan tabel detail_pesanan_jadwal_studio (relasi 1:1) ke tabel
 * pesanan_jadwal_studio dan mengganti tiga kolom status berkode huruf
 * (P/Y/N/X) dengan satu kolom `status` yang eksplisit.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE pesanan_jadwal_studio MODIFY img_jaminan VARCHAR(255) NULL');

        Schema::table('pesanan_jadwal_studio', function (Blueprint $table) {
            $table->string('kode', 20)->nullable()->unique()->after('id_pesanan_jadwal_studio');
            $table->string('status', 20)->default('pending')->after('id_ruangan');
            $table->unsignedTinyInteger('jumlah_orang')->default(1)->after('waktu_selesai');
            $table->string('img_kondisi_awal')->nullable()->after('img_jaminan');
            $table->string('img_kondisi_akhir')->nullable()->after('img_kondisi_awal');
            $table->text('catatan_checkout')->nullable()->after('img_kondisi_akhir');
            $table->text('alasan_batal')->nullable()->after('ket_admin');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('review')->nullable();
            $table->unsignedBigInteger('diproses_oleh')->nullable();
            $table->timestamp('diproses_at')->nullable();
            $table->timestamp('checkin_at')->nullable();
            $table->timestamp('checkout_at')->nullable();
            $table->timestamp('dibatalkan_at')->nullable();
            $table->timestamp('diulas_at')->nullable();
            $table->timestamp('pengingat_at')->nullable();

            $table->index(['id_ruangan', 'tgl_pinjam', 'status'], 'pjs_ruangan_tgl_status_idx');
            $table->index(['status', 'tgl_pinjam'], 'pjs_status_tgl_idx');
            $table->foreign('diproses_oleh')->references('id_user')->on('users')->nullOnDelete();
        });

        if (Schema::hasTable('detail_pesanan_jadwal_studio')) {
            $rows = DB::table('pesanan_jadwal_studio as p')
                ->leftJoin('detail_pesanan_jadwal_studio as d', 'd.id_pesanan_jadwal_studio', '=', 'p.id_pesanan_jadwal_studio')
                ->select('p.*', 'd.status_persetujuan', 'd.status_pengajuan', 'd.status_peminjaman',
                    'd.img_kondisi_awal as d_awal', 'd.img_kondisi_akhir as d_akhir',
                    'd.rating as d_rating', 'd.review as d_review', 'd.updated_at as d_updated_at')
                ->get();

            foreach ($rows as $row) {
                $mulai = Carbon::parse($row->tgl_pinjam.' '.$row->waktu_mulai);
                $selesai = Carbon::parse($row->tgl_pinjam.' '.$row->waktu_selesai);
                $diubah = $row->d_updated_at ?? $row->updated_at;

                $status = match (true) {
                    $row->status_pengajuan === 'X' => 'cancelled',
                    $row->status_persetujuan === 'N' => 'rejected',
                    $row->status_persetujuan === 'Y' && $row->status_peminjaman === 'Y' => 'completed',
                    $row->status_persetujuan === 'Y' && $row->d_awal => 'in_use',
                    $row->status_persetujuan === 'Y' && $selesai->isPast() => 'no_show',
                    $row->status_persetujuan === 'Y' => 'approved',
                    $mulai->isPast() => 'expired',
                    default => 'pending',
                };

                $rating = is_numeric($row->d_rating) ? max(1, min(5, (int) $row->d_rating)) : null;

                DB::table('pesanan_jadwal_studio')
                    ->where('id_pesanan_jadwal_studio', $row->id_pesanan_jadwal_studio)
                    ->update([
                        'kode' => 'STD-'.Carbon::parse($row->created_at)->format('ymd').'-'.str_pad((string) $row->id_pesanan_jadwal_studio, 4, '0', STR_PAD_LEFT),
                        'status' => $status,
                        'img_kondisi_awal' => $row->d_awal,
                        'img_kondisi_akhir' => $row->d_akhir,
                        'rating' => $rating,
                        'review' => $row->d_review,
                        'diproses_at' => in_array($row->status_persetujuan, ['Y', 'N'], true) ? $diubah : null,
                        'checkin_at' => $row->d_awal ? $mulai : null,
                        'checkout_at' => $status === 'completed' ? $selesai : null,
                        'dibatalkan_at' => $status === 'cancelled' ? $diubah : null,
                        'diulas_at' => $rating ? $diubah : null,
                    ]);
            }

            Schema::drop('detail_pesanan_jadwal_studio');
        }
    }

    public function down(): void
    {
        Schema::create('detail_pesanan_jadwal_studio', function (Blueprint $table) {
            $table->id('id_detail_pesanan_jadwal_studio');
            $table->unsignedBigInteger('id_pesanan_jadwal_studio');
            $table->enum('status_persetujuan', ['Y', 'N', 'P'])->default('P');
            $table->enum('status_pengajuan', ['Y', 'X'])->default('Y');
            $table->enum('status_peminjaman', ['Y', 'N'])->default('N');
            $table->string('img_kondisi_awal')->nullable();
            $table->string('img_kondisi_akhir')->nullable();
            $table->integer('harga_sewa')->nullable();
            $table->string('rating')->nullable();
            $table->string('review')->nullable();
            $table->timestamps();

            $table->foreign('id_pesanan_jadwal_studio')->references('id_pesanan_jadwal_studio')->on('pesanan_jadwal_studio')->cascadeOnDelete();
        });

        foreach (DB::table('pesanan_jadwal_studio')->get() as $row) {
            DB::table('detail_pesanan_jadwal_studio')->insert([
                'id_detail_pesanan_jadwal_studio' => $row->id_pesanan_jadwal_studio,
                'id_pesanan_jadwal_studio' => $row->id_pesanan_jadwal_studio,
                'status_persetujuan' => match ($row->status) {
                    'pending', 'expired' => 'P',
                    'rejected' => 'N',
                    'cancelled' => $row->diproses_at ? 'Y' : 'P',
                    default => 'Y',
                },
                'status_pengajuan' => $row->status === 'cancelled' ? 'X' : 'Y',
                'status_peminjaman' => $row->status === 'completed' ? 'Y' : 'N',
                'img_kondisi_awal' => $row->img_kondisi_awal,
                'img_kondisi_akhir' => $row->img_kondisi_akhir,
                'rating' => $row->rating,
                'review' => $row->review ? mb_substr($row->review, 0, 255) : null,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        Schema::table('pesanan_jadwal_studio', function (Blueprint $table) {
            $table->dropForeign(['diproses_oleh']);
            $table->dropIndex('pjs_ruangan_tgl_status_idx');
            $table->dropIndex('pjs_status_tgl_idx');
            $table->dropUnique(['kode']);
            $table->dropColumn([
                'kode', 'status', 'jumlah_orang', 'img_kondisi_awal', 'img_kondisi_akhir', 'catatan_checkout',
                'alasan_batal', 'rating', 'review', 'diproses_oleh', 'diproses_at', 'checkin_at',
                'checkout_at', 'dibatalkan_at', 'diulas_at', 'pengingat_at',
            ]);
        });
    }
};
