<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nim_nip', 30)->nullable()->after('username');
            $table->boolean('is_active')->default(true)->after('user_role');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->index('user_role');
        });

        DB::statement('ALTER TABLE data_ruangan MODIFY thumbnail VARCHAR(255) NULL');
        Schema::table('data_ruangan', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('nama_ruangan');
            $table->text('deskripsi')->nullable()->after('slug');
            $table->boolean('is_active')->default(true)->after('lokasi');
        });

        $slugs = [];
        foreach (DB::table('data_ruangan')->get() as $ruangan) {
            $slug = Str::slug($ruangan->nama_ruangan) ?: 'ruangan';
            if (in_array($slug, $slugs, true)) {
                $slug .= '-'.$ruangan->id_ruangan;
            }
            $slugs[] = $slug;
            DB::table('data_ruangan')->where('id_ruangan', $ruangan->id_ruangan)->update(['slug' => $slug]);
        }

        Schema::table('detail_data_ruangan', function (Blueprint $table) {
            $table->unsignedSmallInteger('urutan')->default(0)->after('img_ruangan');
        });

        Schema::table('laporan', function (Blueprint $table) {
            $table->unsignedBigInteger('id_user')->nullable()->after('id_laporan');
            $table->unsignedBigInteger('id_ruangan')->nullable()->after('id_user');
            $table->unsignedBigInteger('id_pesanan_jadwal_studio')->nullable()->after('id_ruangan');
            $table->string('status', 20)->default('open')->after('jenis_laporan');
            $table->text('tindak_lanjut')->nullable()->after('keterangan');
            $table->timestamp('selesai_at')->nullable()->after('tindak_lanjut');

            $table->index(['status', 'tgl_laporan']);
            $table->foreign('id_user')->references('id_user')->on('users')->nullOnDelete();
            $table->foreign('id_ruangan')->references('id_ruangan')->on('data_ruangan')->nullOnDelete();
            $table->foreign('id_pesanan_jadwal_studio')->references('id_pesanan_jadwal_studio')->on('pesanan_jadwal_studio')->nullOnDelete();
        });
        DB::statement('ALTER TABLE laporan MODIFY gambar JSON NULL');

        Schema::table('tutorial_penggunaan_alat', function (Blueprint $table) {
            $table->string('kategori', 60)->nullable()->after('nama_alat');
            $table->string('video_url')->nullable()->after('deskripsi');
        });
    }

    public function down(): void
    {
        Schema::table('tutorial_penggunaan_alat', function (Blueprint $table) {
            $table->dropColumn(['kategori', 'video_url']);
        });

        Schema::table('laporan', function (Blueprint $table) {
            $table->dropForeign(['id_user']);
            $table->dropForeign(['id_ruangan']);
            $table->dropForeign(['id_pesanan_jadwal_studio']);
            $table->dropIndex(['status', 'tgl_laporan']);
            $table->dropColumn(['id_user', 'id_ruangan', 'id_pesanan_jadwal_studio', 'status', 'tindak_lanjut', 'selesai_at']);
        });

        Schema::table('detail_data_ruangan', function (Blueprint $table) {
            $table->dropColumn('urutan');
        });

        Schema::table('data_ruangan', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'deskripsi', 'is_active']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['user_role']);
            $table->dropColumn(['nim_nip', 'is_active', 'last_login_at']);
        });
    }
};
