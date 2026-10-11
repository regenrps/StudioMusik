<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // ---------- master_jasa_musik ----------
        Schema::table('master_jasa_musik', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('nama_jenis_jasa');
            $table->string('ringkasan')->nullable()->after('slug');
            $table->unsignedSmallInteger('estimasi_hari')->default(7)->after('gambar');
            $table->unsignedInteger('harga_mulai')->nullable()->after('estimasi_hari');
            $table->boolean('is_active')->default(true)->after('harga_mulai');
            $table->unsignedSmallInteger('urutan')->default(0)->after('is_active');
        });
        Schema::table('master_jasa_musik', function (Blueprint $table) {
            $table->dropColumn('biaya_produksi');
        });

        $slugs = [];
        foreach (DB::table('master_jasa_musik')->orderBy('id_jasa_musik')->get() as $jasa) {
            $slug = Str::slug($jasa->nama_jenis_jasa) ?: 'jasa';
            if (in_array($slug, $slugs, true)) {
                $slug .= '-'.$jasa->id_jasa_musik;
            }
            $slugs[] = $slug;
            DB::table('master_jasa_musik')->where('id_jasa_musik', $jasa->id_jasa_musik)->update([
                'slug' => $slug,
                'ringkasan' => $jasa->deskripsi ? Str::limit(strip_tags($jasa->deskripsi), 150) : null,
            ]);
        }

        // ---------- form_jasa_musik ----------
        DB::table('form_jasa_musik')
            ->whereNotIn('jasa_musik_id', DB::table('master_jasa_musik')->select('id_jasa_musik'))
            ->delete();

        Schema::table('form_jasa_musik', function (Blueprint $table) {
            $table->boolean('is_required')->default(true)->after('jenis_field');
            $table->string('bantuan')->nullable()->after('is_required');
            $table->unsignedSmallInteger('urutan')->default(0)->after('bantuan');
            $table->foreign('jasa_musik_id')->references('id_jasa_musik')->on('master_jasa_musik')->cascadeOnDelete();
        });

        foreach (DB::table('form_jasa_musik')->orderBy('id')->get()->groupBy('jasa_musik_id') as $fields) {
            foreach ($fields->values() as $i => $field) {
                DB::table('form_jasa_musik')->where('id', $field->id)->update(['urutan' => $i + 1]);
            }
        }

        // ---------- pesanan_jasa_musik ----------
        DB::statement('ALTER TABLE pesanan_jasa_musik MODIFY rating TINYINT UNSIGNED NULL');
        DB::statement('ALTER TABLE pesanan_jasa_musik MODIFY review TEXT NULL');
        DB::statement('ALTER TABLE pesanan_jasa_musik MODIFY keterangan TEXT NULL');

        Schema::table('pesanan_jasa_musik', function (Blueprint $table) {
            $table->string('kode', 20)->nullable()->unique()->after('id_pesanan_jasa_musik');
            $table->string('status', 20)->default('pending')->after('id_jasa_musik');
            $table->string('judul')->nullable()->after('status');
            $table->string('hasil_berkas')->nullable()->after('keterangan_admin');
            $table->string('hasil_tautan')->nullable()->after('hasil_berkas');
            $table->text('hasil_catatan')->nullable()->after('hasil_tautan');
            $table->text('alasan_batal')->nullable()->after('hasil_catatan');
            $table->unsignedBigInteger('diproses_oleh')->nullable();
            $table->timestamp('diproses_at')->nullable();
            $table->timestamp('mulai_produksi_at')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->timestamp('dibatalkan_at')->nullable();
            $table->timestamp('diulas_at')->nullable();

            $table->index(['status', 'tenggat_produksi'], 'pjm_status_tenggat_idx');
            $table->index(['id_user', 'status'], 'pjm_user_status_idx');
            $table->foreign('diproses_oleh')->references('id_user')->on('users')->nullOnDelete();
        });

        $jasaNama = DB::table('master_jasa_musik')->pluck('nama_jenis_jasa', 'id_jasa_musik');
        foreach (DB::table('pesanan_jasa_musik')->get() as $row) {
            $status = match (true) {
                $row->status_pengajuan === 'X' => 'cancelled',
                $row->status_persetujuan === 'N' => 'rejected',
                $row->status_persetujuan === 'Y' && $row->status_produksi === 'Y' => 'completed',
                $row->status_persetujuan === 'Y' && $row->status_produksi === 'P' => 'in_production',
                $row->status_persetujuan === 'Y' => 'approved',
                default => 'pending',
            };

            DB::table('pesanan_jasa_musik')->where('id_pesanan_jasa_musik', $row->id_pesanan_jasa_musik)->update([
                'kode' => 'JSM-'.Carbon::parse($row->created_at)->format('ymd').'-'.str_pad((string) $row->id_pesanan_jasa_musik, 4, '0', STR_PAD_LEFT),
                'status' => $status,
                'judul' => ($jasaNama[$row->id_jasa_musik] ?? 'Jasa Musik').' #'.$row->id_pesanan_jasa_musik,
                'diproses_at' => in_array($row->status_persetujuan, ['Y', 'N'], true) ? $row->updated_at : null,
                'mulai_produksi_at' => in_array($status, ['in_production', 'completed'], true) ? ($row->tgl_produksi ?? $row->updated_at) : null,
                'selesai_at' => $status === 'completed' ? $row->updated_at : null,
                'dibatalkan_at' => $status === 'cancelled' ? $row->updated_at : null,
                'diulas_at' => $row->rating ? $row->updated_at : null,
            ]);
        }

        Schema::table('pesanan_jasa_musik', function (Blueprint $table) {
            $table->dropColumn(['status_persetujuan', 'status_pengajuan', 'status_produksi']);
        });

        // ---------- pesanan_jasa_musik_informasi ----------
        DB::statement('ALTER TABLE pesanan_jasa_musik_informasi MODIFY value_field TEXT NULL');
        Schema::table('pesanan_jasa_musik_informasi', function (Blueprint $table) {
            $table->unsignedBigInteger('id_field')->nullable()->after('pesanan_jasa_musik_id');
            $table->string('nama_asli')->nullable()->after('value_field');
            $table->foreign('id_field')->references('id')->on('form_jasa_musik')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pesanan_jasa_musik_informasi', function (Blueprint $table) {
            $table->dropForeign(['id_field']);
            $table->dropColumn(['id_field', 'nama_asli']);
        });

        Schema::table('pesanan_jasa_musik', function (Blueprint $table) {
            $table->enum('status_persetujuan', ['Y', 'N', 'P'])->default('P');
            $table->enum('status_pengajuan', ['Y', 'X'])->default('Y');
            $table->enum('status_produksi', ['Y', 'N', 'P'])->default('N');
        });

        foreach (DB::table('pesanan_jasa_musik')->get() as $row) {
            DB::table('pesanan_jasa_musik')->where('id_pesanan_jasa_musik', $row->id_pesanan_jasa_musik)->update([
                'status_persetujuan' => match ($row->status) {
                    'pending' => 'P',
                    'rejected' => 'N',
                    'cancelled' => $row->diproses_at ? 'Y' : 'P',
                    default => 'Y',
                },
                'status_pengajuan' => $row->status === 'cancelled' ? 'X' : 'Y',
                'status_produksi' => match ($row->status) {
                    'completed' => 'Y',
                    'in_production' => 'P',
                    default => 'N',
                },
            ]);
        }

        Schema::table('pesanan_jasa_musik', function (Blueprint $table) {
            $table->dropForeign(['diproses_oleh']);
            $table->dropIndex('pjm_status_tenggat_idx');
            $table->dropIndex('pjm_user_status_idx');
            $table->dropUnique(['kode']);
            $table->dropColumn([
                'kode', 'status', 'judul', 'hasil_berkas', 'hasil_tautan', 'hasil_catatan', 'alasan_batal',
                'diproses_oleh', 'diproses_at', 'mulai_produksi_at', 'selesai_at', 'dibatalkan_at', 'diulas_at',
            ]);
        });

        Schema::table('form_jasa_musik', function (Blueprint $table) {
            $table->dropForeign(['jasa_musik_id']);
            $table->dropColumn(['is_required', 'bantuan', 'urutan']);
        });

        Schema::table('master_jasa_musik', function (Blueprint $table) {
            $table->string('biaya_produksi')->nullable();
        });
        Schema::table('master_jasa_musik', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'ringkasan', 'estimasi_hari', 'harga_mulai', 'is_active', 'urutan']);
        });
    }
};
