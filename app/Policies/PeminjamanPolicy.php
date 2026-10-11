<?php

namespace App\Policies;

use App\Models\Peminjaman;
use App\Models\User;

class PeminjamanPolicy
{
    public function view(User $user, Peminjaman $peminjaman): bool
    {
        return $user->isStaff() || $this->pemilik($user, $peminjaman);
    }

    /** Menyetujui, menolak, membatalkan, menyelesaikan (admin). */
    public function kelola(User $user, ?Peminjaman $peminjaman = null): bool
    {
        return $user->isAdmin();
    }

    public function batalkan(User $user, Peminjaman $peminjaman): bool
    {
        return $this->pemilik($user, $peminjaman);
    }

    public function checkIn(User $user, Peminjaman $peminjaman): bool
    {
        return $this->pemilik($user, $peminjaman);
    }

    public function checkOut(User $user, Peminjaman $peminjaman): bool
    {
        return $this->pemilik($user, $peminjaman);
    }

    public function ulas(User $user, Peminjaman $peminjaman): bool
    {
        return $this->pemilik($user, $peminjaman);
    }

    /** KTP/KTM jaminan & foto kondisi. */
    public function lihatBerkas(User $user, Peminjaman $peminjaman): bool
    {
        return $user->isStaff() || $this->pemilik($user, $peminjaman);
    }

    private function pemilik(User $user, Peminjaman $peminjaman): bool
    {
        return (int) $peminjaman->id_user === (int) $user->getKey();
    }
}
