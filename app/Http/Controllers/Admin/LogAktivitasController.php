<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Support\EksporCsv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogAktivitasController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $this->filter($request);

        return view('admin.log', [
            'log' => $this->query($filter)->with('user:id_user,username,user_role', 'subjek')
                ->latest('created_at')->latest('id')
                ->paginate(30)->withQueryString(),
            'daftarAksi' => LogAktivitas::query()->distinct()->orderBy('aksi')->pluck('aksi'),
            'filter' => $filter,
            'gagalMasuk24Jam' => LogAktivitas::query()->where('aksi', 'gagal_masuk')->where('created_at', '>=', now()->subDay())->count(),
        ]);
    }

    public function ekspor(Request $request): StreamedResponse
    {
        $baris = $this->query($this->filter($request))->with('user:id_user,username,user_role')
            ->latest('created_at')->latest('id')->lazy(500)
            ->map(fn (LogAktivitas $l) => [
                $l->created_at?->format('Y-m-d H:i:s'),
                $l->user?->username ?? 'Sistem',
                $l->user?->user_role?->label(),
                $l->aksi,
                $l->deskripsi,
                $l->subjek_type ? $l->subjek_type.'#'.$l->subjek_id : null,
                $l->properti ? json_encode($l->properti, JSON_UNESCAPED_UNICODE) : null,
                $l->ip_address,
            ]);

        return EksporCsv::unduh('log-aktivitas-'.now()->format('Ymd-His').'.csv',
            ['Waktu', 'Pelaku', 'Peran', 'Aksi', 'Deskripsi', 'Subjek', 'Detail', 'IP'], $baris);
    }

    /** @return array<string, mixed> */
    private function filter(Request $request): array
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'aksi' => ['nullable', 'string', 'max:50'],
            'kategori' => ['nullable', 'in:keamanan,data'],
            'dari' => ['nullable', 'date_format:Y-m-d'],
            'sampai' => ['nullable', 'date_format:Y-m-d'],
        ]);

        return [
            'q' => trim((string) ($data['q'] ?? '')),
            'aksi' => $data['aksi'] ?? null,
            'kategori' => $data['kategori'] ?? null,
            'dari' => $data['dari'] ?? null,
            'sampai' => $data['sampai'] ?? null,
        ];
    }

    private function query(array $filter): Builder
    {
        return LogAktivitas::query()
            ->when($filter['q'] !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('deskripsi', 'like', '%'.$filter['q'].'%')
                ->orWhere('ip_address', 'like', $filter['q'].'%')
                ->orWhereHas('user', fn ($u) => $u->where('username', 'like', '%'.$filter['q'].'%'))))
            ->when($filter['aksi'], fn ($query, $aksi) => $query->where('aksi', $aksi))
            ->when($filter['kategori'] === 'keamanan', fn ($query) => $query->whereIn('aksi', LogAktivitas::AKSI_KEAMANAN))
            ->when($filter['kategori'] === 'data', fn ($query) => $query->whereNotIn('aksi', LogAktivitas::AKSI_KEAMANAN))
            ->when($filter['dari'], fn ($query, $d) => $query->whereDate('created_at', '>=', $d))
            ->when($filter['sampai'], fn ($query, $d) => $query->whereDate('created_at', '<=', $d));
    }
}
