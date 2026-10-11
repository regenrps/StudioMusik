<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\StatusPeminjaman;
use App\Enums\StatusPesananJasa;
use App\Notifications\ResetPasswordNotifikasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $primaryKey = 'id_user';

    protected $fillable = [
        'username',
        'nim_nip',
        'email',
        'no_wa',
        'password',
        'user_role',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'user_role' => Role::class,
        'is_active' => 'boolean',
    ];

    // ---------------------------------------------------------------- Relasi

    public function peminjaman(): HasMany
    {
        return $this->hasMany(Peminjaman::class, 'id_user', 'id_user');
    }

    public function pesananJasa(): HasMany
    {
        return $this->hasMany(PesananJasa::class, 'id_user', 'id_user');
    }

    // ----------------------------------------------------------------- Scope

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRole(Builder $query, Role ...$roles): Builder
    {
        return $query->whereIn('user_role', array_map(fn (Role $r) => $r->value, $roles));
    }

    // ---------------------------------------------------------------- Helper

    public function isAdmin(): bool
    {
        return $this->user_role === Role::Admin;
    }

    public function isK3l(): bool
    {
        return $this->user_role === Role::K3L;
    }

    public function isStaff(): bool
    {
        return $this->user_role->isStaff();
    }

    public function homeRoute(): string
    {
        return $this->isStaff() ? route('admin.dashboard') : route('akun.dashboard');
    }

    public function getInisialAttribute(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->username)) ?: [];
        $inisial = collect($parts)->filter()->take(2)->map(fn ($p) => Str::upper(Str::substr($p, 0, 1)))->implode('');

        return $inisial !== '' ? $inisial : 'U';
    }

    public function getWhatsappUrlAttribute(): ?string
    {
        $nomor = preg_replace('/\D+/', '', (string) $this->no_wa);
        if ($nomor === '') {
            return null;
        }
        if (str_starts_with($nomor, '0')) {
            $nomor = '62'.substr($nomor, 1);
        }

        return 'https://wa.me/'.$nomor;
    }

    /**
     * Peminjaman yang masih berjalan: sedang dipakai, atau menunggu/disetujui
     * dengan jadwal yang belum berakhir.
     */
    public function jumlahPeminjamanAktif(): int
    {
        return $this->peminjaman()
            ->where(fn ($q) => $q
                ->where('status', StatusPeminjaman::Berlangsung->value)
                ->orWhere(fn ($q) => $q
                    ->whereIn('status', [StatusPeminjaman::Menunggu->value, StatusPeminjaman::Disetujui->value])
                    ->where(fn ($q) => $q
                        ->whereDate('tgl_pinjam', '>', today()->toDateString())
                        ->orWhere(fn ($q) => $q
                            ->whereDate('tgl_pinjam', today()->toDateString())
                            ->where('waktu_selesai', '>', now()->format('H:i:s'))))))
            ->count();
    }

    public function jumlahPesananJasaAktif(): int
    {
        return $this->pesananJasa()
            ->whereIn('status', StatusPesananJasa::activeValues())
            ->count();
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotifikasi($token));
    }
}
