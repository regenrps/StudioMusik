<?php

namespace App\Policies;

use App\Models\PesananJasa;
use App\Models\User;

class PesananJasaPolicy
{
    public function view(User $user, PesananJasa $pesanan): bool
    {
        return $user->isAdmin() || $this->pemilik($user, $pesanan);
    }

    public function kelola(User $user, ?PesananJasa $pesanan = null): bool
    {
        return $user->isAdmin();
    }

    public function batalkan(User $user, PesananJasa $pesanan): bool
    {
        return $this->pemilik($user, $pesanan);
    }

    public function ulas(User $user, PesananJasa $pesanan): bool
    {
        return $this->pemilik($user, $pesanan);
    }

    public function lihatBerkas(User $user, PesananJasa $pesanan): bool
    {
        return $user->isAdmin() || $this->pemilik($user, $pesanan);
    }

    private function pemilik(User $user, PesananJasa $pesanan): bool
    {
        return (int) $pesanan->id_user === (int) $user->getKey();
    }
}
