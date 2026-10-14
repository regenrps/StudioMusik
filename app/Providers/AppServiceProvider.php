<?php

namespace App\Providers;

use App\Enums\StatusLaporan;
use App\Enums\StatusPeminjaman;
use App\Enums\StatusPesananJasa;
use App\Models\JasaMusik;
use App\Models\Laporan;
use App\Models\Peminjaman;
use App\Models\PesananJasa;
use App\Models\Ruangan;
use App\Models\User;
use App\Services\KetersediaanService;
use App\Services\PengaturanService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PengaturanService::class);
        $this->app->singleton(KetersediaanService::class);
    }

    public function boot(): void
    {
        Carbon::setLocale('id');

        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        if ($this->app->isProduction()) {
            // Semua URL yang dibuat (termasuk tautan email reset sandi) memakai HTTPS.
            if (str_starts_with((string) config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }

            // Halaman debug membocorkan path, query, dan kode sumber.
            if (config('app.debug')) {
                Log::channel('keamanan')->critical('APP_DEBUG aktif di lingkungan produksi. Set APP_DEBUG=false.');
            }
        }

        // Kata sandi: minimal 8 karakter, huruf & angka (dipakai Password::defaults()).
        Password::defaults(fn () => Password::min(8)->letters()->numbers()->max(72));

        Relation::morphMap([
            'user' => User::class,
            'peminjaman' => Peminjaman::class,
            'pesanan_jasa' => PesananJasa::class,
            'laporan' => Laporan::class,
            'ruangan' => Ruangan::class,
            'jasa_musik' => JasaMusik::class,
        ]);

        Paginator::defaultView('components.pagination');
        Paginator::defaultSimpleView('components.pagination');

        // Data bersama untuk kerangka aplikasi (badge sidebar & lonceng notifikasi).
        View::composer('components.layouts.app', function ($view) {
            $user = auth()->user();
            if (! $user) {
                return;
            }

            $badge = [];
            if ($user->isAdmin()) {
                $badge['peminjaman'] = Peminjaman::query()->status(StatusPeminjaman::Menunggu)->count();
                $badge['jasa'] = PesananJasa::query()->status(StatusPesananJasa::Menunggu)->count();
            }
            if ($user->isStaff()) {
                $badge['laporan'] = Laporan::query()->where('status', StatusLaporan::Baru->value)->count();
            }

            $view->with([
                'badgeMenu' => $badge,
                'notifBelumDibaca' => $user->unreadNotifications()->count(),
            ]);
        });
    }
}
