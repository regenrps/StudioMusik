<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan', function (Blueprint $table) {
            $table->string('kunci', 64)->primary();
            $table->text('nilai')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_user')->nullable();
            $table->nullableMorphs('subjek');
            $table->string('aksi', 50);
            $table->string('deskripsi');
            $table->json('properti')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->foreign('id_user')->references('id_user')->on('users')->nullOnDelete();
        });

        Schema::create('jadwal_tutup', function (Blueprint $table) {
            $table->id();
            // null = berlaku untuk semua ruangan
            $table->unsignedBigInteger('id_ruangan')->nullable();
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            // null = sepanjang hari operasional
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->string('keterangan');
            $table->unsignedBigInteger('dibuat_oleh')->nullable();
            $table->timestamps();

            $table->index(['tgl_mulai', 'tgl_selesai']);
            $table->foreign('id_ruangan')->references('id_ruangan')->on('data_ruangan')->cascadeOnDelete();
            $table->foreign('dibuat_oleh')->references('id_user')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_tutup');
        Schema::dropIfExists('log_aktivitas');
        Schema::dropIfExists('pengaturan');
    }
};
