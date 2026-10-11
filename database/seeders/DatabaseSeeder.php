<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder aman untuk produksi: hanya membuat akun admin pertama bila
 * belum ada admin sama sekali. Untuk data contoh gunakan DemoSeeder.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->where('user_role', Role::Admin->value)->exists()) {
            $this->command?->info('Akun admin sudah ada — tidak ada yang dibuat.');

            return;
        }

        $email = env('ADMIN_EMAIL', 'admin@studio.test');
        $password = env('ADMIN_PASSWORD') ?: Str::password(14, symbols: false);

        User::create([
            'username' => env('ADMIN_NAMA', 'Admin Studio'),
            'email' => strtolower($email),
            'no_wa' => config('studio.kontak.whatsapp'),
            'password' => $password,
            'user_role' => Role::Admin,
            'is_active' => true,
        ]);

        $this->command?->info("Akun admin dibuat: {$email}");
        if (! env('ADMIN_PASSWORD')) {
            $this->command?->warn("Kata sandi sementara: {$password} — segera ganti setelah masuk.");
        }
    }
}
