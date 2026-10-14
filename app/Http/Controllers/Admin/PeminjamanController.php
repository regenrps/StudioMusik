<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPeminjaman;
use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use App\Models\Ruangan;
use App\Services\KetersediaanService;
use App\Services\PeminjamanService;
use App\Support\EksporCsv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PeminjamanController extends Controller
{
    public function __construct(private readonly PeminjamanService $service)
    {
    }

    public function index(Request $request): View
    {
        $filter = $this->filter($request);

        $peminjaman = $this->query($filter)
            ->with('user:id_user,username,email,no_wa', 'ruangan:id_ruangan,nama_ruangan')
            ->when($filter['status'] === 'pending',
                fn ($q) => $q->urutJadwal(),
                fn ($q) => $q->urutJadwal('desc'))
            ->paginate(15)->withQueryString();

        $hitung = Peminjaman::query()->selectRaw('status, COUNT(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status');

        return view('admin.peminjaman.index', [
            'peminjaman' => $peminjaman,
            'filter' => $filter,
            'hitung' => $hitung,
            'ruangan' => Ruangan::query()->orderBy('nama_ruangan')->get(['id_ruangan', 'nama_ruangan', 'slug']),
        ]);
    }

    public function kalender(KetersediaanService $ketersediaan): View
    {
        return view('admin.peminjaman.kalender', [
            'ruangan' => Ruangan::query()->orderBy('nama_ruangan')->get(['id_ruangan', 'nama_ruangan', 'slug']),
            'aturan' => $ketersediaan->aturan(),
        ]);
    }

    public function kalenderData(Request $request, KetersediaanService $ketersediaan): JsonResponse
    {
        $data = $request->validate([
            'mulai' => ['required', 'date_format:Y-m-d'],
            'selesai' => ['required', 'date_format:Y-m-d', 'after_or_equal:mulai'],
            'ruangan' => ['nullable', 'string', 'exists:data_ruangan,slug'],
        ]);

        $mulai = Carbon::parse($data['mulai']);
        $selesai = Carbon::parse($data['selesai'])->min($mulai->copy()->addDays(42));
        $ruangan = filled($data['ruangan'] ?? null) ? Ruangan::where('slug', $data['ruangan'])->first() : null;

        $status = [...StatusPeminjaman::blockingValues(), StatusPeminjaman::Selesai->value, StatusPeminjaman::TidakHadir->value];

        return response()->json([
            'events' => $ketersediaan->jadwal($mulai, $selesai, $ruangan, anonim: false, status: $status),
            'aturan' => $ketersediaan->aturan(),
        ]);
    }

    public function ekspor(Request $request): StreamedResponse
    {
        $filter = $this->filter($request);
        $baris = $this->query($filter)->with('user', 'ruangan', 'pemroses')->urutJadwal('desc')->lazy(200)
            ->map(fn (Peminjaman $p) => [
                $p->kode,
                $p->status->label(),
                $p->ruangan?->nama_ruangan,
                $p->tgl_pinjam->format('Y-m-d'),
                substr($p->waktu_mulai, 0, 5),
                substr($p->waktu_selesai, 0, 5),
                $p->durasi_menit,
                $p->user?->username,
                $p->user?->nim_nip,
                $p->user?->email,
                $p->user?->no_wa,
                $p->jumlah_orang,
                $p->ket_keperluan,
                $p->ket_admin,
                $p->alasan_batal,
                $p->pemroses?->username,
                $p->checkin_at?->format('Y-m-d H:i'),
                $p->checkout_at?->format('Y-m-d H:i'),
                $p->rating,
                $p->review,
                $p->created_at?->format('Y-m-d H:i'),
            ]);

        return EksporCsv::unduh('peminjaman-studio-'.now()->format('Ymd-His').'.csv', [
            'Kode', 'Status', 'Ruangan', 'Tanggal', 'Mulai', 'Selesai', 'Durasi (menit)', 'Peminjam', 'NIM/NIP',
            'Email', 'WhatsApp', 'Jumlah Orang', 'Keperluan', 'Catatan Admin', 'Alasan Batal', 'Diproses Oleh',
            'Check-in', 'Check-out', 'Rating', 'Ulasan', 'Diajukan',
        ], $baris);
    }

    public function show(Peminjaman $peminjaman): View
    {
        Gate::authorize('view', $peminjaman);

        $peminjaman->load(['user', 'ruangan', 'pemroses:id_user,username', 'log.user:id_user,username,user_role']);

        // Peminjaman lain yang bertumpuk (seharusnya tidak ada; ditampilkan sebagai peringatan data lama).
        $bentrok = Peminjaman::query()->with('user:id_user,username')
            ->where('id_ruangan', $peminjaman->id_ruangan)
            ->whereDate('tgl_pinjam', $peminjaman->tgl_pinjam)
            ->menempatiSlot()
            ->tumpangTindih($peminjaman->waktu_mulai, $peminjaman->waktu_selesai)
            ->whereKeyNot($peminjaman->getKey())
            ->get();

        $riwayatUser = $peminjaman->user->peminjaman()->selectRaw('status, COUNT(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status');

        return view('admin.peminjaman.show', [
            'peminjaman' => $peminjaman,
            'bentrok' => $bentrok,
            'riwayatUser' => $riwayatUser,
        ]);
    }

    public function setujui(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        $data = $request->validate(['catatan' => ['nullable', 'string', 'max:500']]);
        $this->service->setujui($peminjaman, $request->user(), $data['catatan'] ?? null);

        return $this->kembali($request, 'Peminjaman '.$peminjaman->kode.' disetujui. Peminjam telah diberi tahu.');
    }

    public function tolak(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        $data = $request->validate(['alasan' => ['required', 'string', 'min:5', 'max:500']]);
        $this->service->tolak($peminjaman, $request->user(), $data['alasan']);

        return $this->kembali($request, 'Peminjaman '.$peminjaman->kode.' ditolak. Peminjam telah diberi tahu.');
    }

    public function batal(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        $data = $request->validate(['alasan' => ['required', 'string', 'min:5', 'max:500']]);
        $this->service->batalkan($peminjaman, $request->user(), $data['alasan']);

        return $this->kembali($request, 'Peminjaman '.$peminjaman->kode.' dibatalkan.');
    }

    public function selesai(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        $data = $request->validate(['catatan' => ['nullable', 'string', 'max:500']]);
        $this->service->selesaikan($peminjaman, $request->user(), $data['catatan'] ?? null);

        return $this->kembali($request, 'Peminjaman '.$peminjaman->kode.' ditandai selesai.');
    }

    public function tidakHadir(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        $this->service->tandaiTidakHadir($peminjaman, $request->user());

        return $this->kembali($request, 'Peminjaman '.$peminjaman->kode.' ditandai tidak hadir.');
    }

    // ------------------------------------------------------------------

    /** @return array<string, mixed> */
    private function filter(Request $request): array
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['semua', ...array_column(StatusPeminjaman::cases(), 'value')])],
            'ruangan' => ['nullable', 'string'],
            'dari' => ['nullable', 'date_format:Y-m-d'],
            'sampai' => ['nullable', 'date_format:Y-m-d'],
        ]);

        return [
            'q' => trim((string) ($data['q'] ?? '')),
            'status' => $data['status'] ?? 'semua',
            'ruangan' => $data['ruangan'] ?? null,
            'dari' => $data['dari'] ?? null,
            'sampai' => $data['sampai'] ?? null,
        ];
    }

    private function query(array $filter): Builder
    {
        return Peminjaman::query()
            ->when($filter['status'] !== 'semua', fn ($q) => $q->where('status', $filter['status']))
            ->when($filter['ruangan'], fn ($q, $slug) => $q->whereHas('ruangan', fn ($r) => $r->where('slug', $slug)))
            ->when($filter['dari'], fn ($q, $d) => $q->whereDate('tgl_pinjam', '>=', $d))
            ->when($filter['sampai'], fn ($q, $d) => $q->whereDate('tgl_pinjam', '<=', $d))
            ->when($filter['q'] !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('kode', 'like', '%'.$filter['q'].'%')
                ->orWhere('ket_keperluan', 'like', '%'.$filter['q'].'%')
                ->orWhereHas('user', fn ($u) => $u->where('username', 'like', '%'.$filter['q'].'%')
                    ->orWhere('email', 'like', '%'.$filter['q'].'%')
                    ->orWhere('nim_nip', 'like', '%'.$filter['q'].'%'))));
    }

    private function kembali(Request $request, string $pesan): RedirectResponse
    {
        $sebelumnya = tujuan_aman(url()->previous(), route('admin.peminjaman.index'));

        return redirect()->to(tujuan_aman($request->input('kembali'), $sebelumnya))
            ->with('toast', ['type' => 'success', 'message' => $pesan]);
    }
}
