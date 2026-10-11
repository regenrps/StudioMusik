<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\ResetPasswordNotifikasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AutentikasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_pendaftaran_membuat_akun_peminjam(): void
    {
        $this->post(route('register'), [
            'username' => 'Budi Santoso',
            'nim_nip' => '119140182',
            'email' => 'Budi@Student.itera.ac.id',
            'no_wa' => '0812-3456-7890',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'setuju' => '1',
        ])->assertRedirect(route('akun.dashboard'));

        $user = User::firstOrFail();
        $this->assertSame(Role::User, $user->user_role);
        $this->assertSame('budi@student.itera.ac.id', $user->email);
        $this->assertSame('081234567890', $user->no_wa);
        $this->assertAuthenticatedAs($user);
    }

    public function test_pendaftaran_menolak_kata_sandi_lemah_dan_nomor_tidak_valid(): void
    {
        $this->post(route('register'), [
            'username' => 'Budi',
            'email' => 'budi@example.com',
            'no_wa' => '12345',
            'password' => '123',
            'password_confirmation' => '123',
        ])->assertSessionHasErrors(['no_wa', 'password', 'setuju']);
    }

    public function test_masuk_diarahkan_sesuai_peran(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@contoh.test']);
        $peminjam = User::factory()->create(['email' => 'mhs@contoh.test']);

        $this->post(route('login'), ['email' => 'admin@contoh.test', 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
        $this->post(route('logout'));
        $this->post(route('login'), ['email' => 'mhs@contoh.test', 'password' => 'password'])->assertRedirect(route('akun.dashboard'));
        $this->assertNotNull($peminjam->refresh()->last_login_at);
    }

    public function test_kata_sandi_salah_ditolak(): void
    {
        User::factory()->create(['email' => 'mhs@contoh.test']);

        $this->post(route('login'), ['email' => 'mhs@contoh.test', 'password' => 'salah'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_akun_nonaktif_tidak_dapat_masuk(): void
    {
        User::factory()->nonaktif()->create(['email' => 'off@contoh.test']);

        $this->post(route('login'), ['email' => 'off@contoh.test', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_tautan_atur_ulang_kata_sandi_dikirim(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPasswordNotifikasi::class);
    }

    public function test_percobaan_masuk_dibatasi(): void
    {
        User::factory()->create(['email' => 'mhs@contoh.test']);

        foreach (range(1, 6) as $_) {
            $this->post(route('login'), ['email' => 'mhs@contoh.test', 'password' => 'salah']);
        }

        $this->post(route('login'), ['email' => 'mhs@contoh.test', 'password' => 'salah'])->assertStatus(429);
    }
}
