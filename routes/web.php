<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Akun;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\BerkasController;
use App\Http\Controllers\JadwalPublikController;
use App\Http\Controllers\KatalogController;
use App\Http\Controllers\KetersediaanController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\TutorialController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Publik
|--------------------------------------------------------------------------
*/
Route::get('/', [BerandaController::class, 'index'])->name('beranda');
Route::get('/jadwal', [JadwalPublikController::class, 'index'])->name('jadwal');
Route::get('/jadwal/data', [JadwalPublikController::class, 'data'])->middleware('throttle:120,1')->name('jadwal.data');
Route::get('/ruangan/{ruangan}', [KatalogController::class, 'ruangan'])->name('ruangan.show');
Route::get('/layanan', [KatalogController::class, 'layanan'])->name('layanan.index');
Route::get('/layanan/{jasa}', [KatalogController::class, 'layananShow'])->name('layanan.show');
Route::get('/tutorial', [TutorialController::class, 'index'])->name('tutorial.index');
Route::get('/tutorial/{tutorial}', [TutorialController::class, 'show'])->name('tutorial.show');

Route::prefix('ketersediaan/{ruangan}')->name('ketersediaan.')->middleware('throttle:120,1')->group(function () {
    Route::get('/bulan', [KetersediaanController::class, 'bulan'])->name('bulan');
    Route::get('/hari', [KetersediaanController::class, 'hari'])->name('hari');
});

/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [LoginController::class, 'create'])->name('login');
    Route::post('/masuk', [LoginController::class, 'store'])->middleware('throttle:auth');
    Route::get('/daftar', [RegisterController::class, 'create'])->name('register');
    Route::post('/daftar', [RegisterController::class, 'store'])->middleware('throttle:auth');
    Route::get('/lupa-password', [PasswordController::class, 'request'])->name('password.request');
    Route::post('/lupa-password', [PasswordController::class, 'email'])->middleware('throttle:auth')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'update'])->middleware('throttle:auth')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/keluar', [LoginController::class, 'destroy'])->name('logout');

    // Arahkan ke dasbor sesuai peran.
    Route::get('/dashboard', fn () => redirect(auth()->user()->homeRoute()))->name('dashboard');

    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/password', [ProfilController::class, 'password'])->name('profil.password');

    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::get('/notifikasi/terbaru', [NotifikasiController::class, 'terbaru'])->middleware('throttle:60,1')->name('notifikasi.terbaru');
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::get('/notifikasi/{id}', [NotifikasiController::class, 'buka'])->name('notifikasi.buka');

    // Berkas privat (diperiksa lewat policy).
    Route::prefix('berkas')->name('berkas.')->group(function () {
        Route::get('/peminjaman/{peminjaman}/{jenis}', [BerkasController::class, 'peminjaman'])
            ->whereIn('jenis', ['jaminan', 'awal', 'akhir'])->name('peminjaman');
        Route::get('/jasa/{pesanan}/lampiran/{informasi}', [BerkasController::class, 'lampiranJasa'])->name('jasa.lampiran');
        Route::get('/jasa/{pesanan}/hasil', [BerkasController::class, 'hasilJasa'])->name('jasa.hasil');
        Route::get('/laporan/{laporan}/{indeks}', [BerkasController::class, 'laporan'])->whereNumber('indeks')->name('laporan');
    });
});

/*
|--------------------------------------------------------------------------
| Peminjam (civitas)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:user'])->prefix('akun')->name('akun.')->group(function () {
    Route::get('/', [Akun\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/peminjaman', [Akun\PeminjamanController::class, 'index'])->name('peminjaman.index');
    Route::get('/peminjaman/baru', [Akun\PeminjamanController::class, 'create'])->name('peminjaman.create');
    Route::post('/peminjaman', [Akun\PeminjamanController::class, 'store'])->middleware('throttle:aksi')->name('peminjaman.store');
    Route::get('/peminjaman/{peminjaman}', [Akun\PeminjamanController::class, 'show'])->name('peminjaman.show');
    Route::post('/peminjaman/{peminjaman}/batal', [Akun\PeminjamanController::class, 'batal'])->name('peminjaman.batal');
    Route::post('/peminjaman/{peminjaman}/checkin', [Akun\PeminjamanController::class, 'checkIn'])->middleware('throttle:aksi')->name('peminjaman.checkin');
    Route::post('/peminjaman/{peminjaman}/checkout', [Akun\PeminjamanController::class, 'checkOut'])->middleware('throttle:aksi')->name('peminjaman.checkout');
    Route::post('/peminjaman/{peminjaman}/ulasan', [Akun\PeminjamanController::class, 'ulasan'])->name('peminjaman.ulasan');

    Route::get('/jasa', [Akun\PesananJasaController::class, 'index'])->name('jasa.index');
    Route::get('/jasa/pesan/{jasa}', [Akun\PesananJasaController::class, 'create'])->name('jasa.create');
    Route::post('/jasa/pesan/{jasa}', [Akun\PesananJasaController::class, 'store'])->middleware('throttle:aksi')->name('jasa.store');
    Route::get('/jasa/{pesanan}', [Akun\PesananJasaController::class, 'show'])->name('jasa.show');
    Route::post('/jasa/{pesanan}/batal', [Akun\PesananJasaController::class, 'batal'])->name('jasa.batal');
    Route::post('/jasa/{pesanan}/ulasan', [Akun\PesananJasaController::class, 'ulasan'])->name('jasa.ulasan');
});

/*
|--------------------------------------------------------------------------
| Staf: Admin & K3L
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin,k3l'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // Peminjaman: K3L dapat melihat, hanya admin yang memproses.
    Route::get('/peminjaman', [Admin\PeminjamanController::class, 'index'])->name('peminjaman.index');
    Route::get('/peminjaman/kalender', [Admin\PeminjamanController::class, 'kalender'])->name('peminjaman.kalender');
    Route::get('/peminjaman/kalender/data', [Admin\PeminjamanController::class, 'kalenderData'])->name('peminjaman.kalender.data');
    Route::get('/peminjaman/ekspor', [Admin\PeminjamanController::class, 'ekspor'])->name('peminjaman.ekspor');
    Route::get('/peminjaman/{peminjaman}', [Admin\PeminjamanController::class, 'show'])->name('peminjaman.show');

    // Laporan masalah: admin & K3L.
    Route::resource('laporan', Admin\LaporanController::class)->parameters(['laporan' => 'laporan']);

    Route::middleware('role:admin')->group(function () {
        Route::post('/peminjaman/{peminjaman}/setujui', [Admin\PeminjamanController::class, 'setujui'])->name('peminjaman.setujui');
        Route::post('/peminjaman/{peminjaman}/tolak', [Admin\PeminjamanController::class, 'tolak'])->name('peminjaman.tolak');
        Route::post('/peminjaman/{peminjaman}/batal', [Admin\PeminjamanController::class, 'batal'])->name('peminjaman.batal');
        Route::post('/peminjaman/{peminjaman}/selesai', [Admin\PeminjamanController::class, 'selesai'])->name('peminjaman.selesai');
        Route::post('/peminjaman/{peminjaman}/tidak-hadir', [Admin\PeminjamanController::class, 'tidakHadir'])->name('peminjaman.tidak-hadir');

        Route::get('/jasa', [Admin\PesananJasaController::class, 'index'])->name('jasa.index');
        Route::get('/jasa/ekspor', [Admin\PesananJasaController::class, 'ekspor'])->name('jasa.ekspor');
        Route::get('/jasa/{pesanan}', [Admin\PesananJasaController::class, 'show'])->name('jasa.show');
        Route::post('/jasa/{pesanan}/setujui', [Admin\PesananJasaController::class, 'setujui'])->name('jasa.setujui');
        Route::post('/jasa/{pesanan}/tolak', [Admin\PesananJasaController::class, 'tolak'])->name('jasa.tolak');
        Route::post('/jasa/{pesanan}/mulai', [Admin\PesananJasaController::class, 'mulai'])->name('jasa.mulai');
        Route::post('/jasa/{pesanan}/jadwal', [Admin\PesananJasaController::class, 'jadwal'])->name('jasa.jadwal');
        Route::post('/jasa/{pesanan}/selesai', [Admin\PesananJasaController::class, 'selesai'])->name('jasa.selesai');
        Route::post('/jasa/{pesanan}/batal', [Admin\PesananJasaController::class, 'batal'])->name('jasa.batal');

        Route::resource('layanan', Admin\LayananController::class)->except('show')->parameters(['layanan' => 'jasa']);
        Route::resource('ruangan', Admin\RuanganController::class)->except('show');
        Route::delete('/ruangan/{ruangan}/foto/{foto}', [Admin\RuanganController::class, 'hapusFoto'])->name('ruangan.foto.destroy');
        Route::resource('jadwal-tutup', Admin\JadwalTutupController::class)->except(['show', 'create', 'edit'])->parameters(['jadwal-tutup' => 'jadwalTutup']);
        Route::resource('tutorial', Admin\TutorialController::class)->except('show');

        Route::get('/pengguna', [Admin\PenggunaController::class, 'index'])->name('pengguna.index');
        Route::post('/pengguna', [Admin\PenggunaController::class, 'store'])->name('pengguna.store');
        Route::put('/pengguna/{user}', [Admin\PenggunaController::class, 'update'])->name('pengguna.update');
        Route::post('/pengguna/{user}/status', [Admin\PenggunaController::class, 'status'])->name('pengguna.status');

        Route::get('/pengaturan', [Admin\PengaturanController::class, 'edit'])->name('pengaturan.edit');
        Route::put('/pengaturan', [Admin\PengaturanController::class, 'update'])->name('pengaturan.update');

        Route::get('/log-aktivitas', [Admin\LogAktivitasController::class, 'index'])->name('log.index');
        Route::get('/log-aktivitas/ekspor', [Admin\LogAktivitasController::class, 'ekspor'])->name('log.ekspor');
    });
});
