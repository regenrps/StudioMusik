<?php

namespace App\Providers;

use App\Models\Peminjaman;
use App\Models\PesananJasa;
use App\Models\User;
use App\Policies\PeminjamanPolicy;
use App\Policies\PesananJasaPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Peminjaman::class => PeminjamanPolicy::class,
        PesananJasa::class => PesananJasaPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Admin: akses penuh ke pengelolaan data & pengaturan.
        Gate::define('admin', fn (User $user) => $user->isAdmin());

        // Staf (admin & K3L): memantau jadwal, kondisi ruangan, dan laporan.
        Gate::define('staf', fn (User $user) => $user->isStaff());

        // Peminjam (civitas).
        Gate::define('peminjam', fn (User $user) => ! $user->isStaff());
    }
}
