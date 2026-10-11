<?php

namespace App\Http\Controllers\Akun;

use App\Enums\StatusPeminjaman;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePeminjamanRequest;
use App\Models\Peminjaman;
use App\Models\Ruangan;
use App\Services\KetersediaanService;
use App\Services\PeminjamanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PeminjamanController extends Controller
{
    public function __construct(private readonly PeminjamanService $service)
    {
    }

    public function index(Request $request): View
    {
        $tab = in_array($request->query('tab'), ['aktif', 'riwayat'], true) ? $request->query('tab') : 'aktif';
        $user = $request->user();

        $aktif = fn ($q) => $q->whereIn('status', StatusPeminjaman::blockingValues());

        $peminjaman = $user->peminjaman()->with('ruangan')
            ->when($tab === 'aktif', $aktif, fn ($q) => $q->whereNotIn('status', StatusPeminjaman::blockingValues()))
            ->when($tab === 'aktif', fn ($q) => $q->urutJadwal(), fn ($q) => $q->urutJadwal('desc'))
            ->paginate(10)->withQueryString();

        return view('akun.peminjaman.index', [
            'peminjaman' => $peminjaman,
            'tab' => $tab,
            'jumlah' => [
                'aktif' => $user->peminjaman()->whereIn('status', StatusPeminjaman::blockingValues())->count(),
                'riwayat' => $user->peminjaman()->whereNotIn('status', StatusPeminjaman::blockingValues())->count(),
            ],
        ]);
    }

    public function create(Request $request, KetersediaanService $ketersediaan): View
    {
        $user = $request->user();
        $aturan = $ketersediaan->aturan();
        $ruangan = Ruangan::query()->aktif()->with('foto')->orderBy('nama_ruangan')->get();

        $dipilih = $ruangan->firstWhere('slug', $request->query('ruangan')) ?? $ruangan->first();
        $tanggal = $request->query('tanggal');

        $penghalang = null;
        if ($aturan['maks_booking_aktif'] > 0 && $user->jumlahPeminjamanAktif() >= $aturan['maks_booking_aktif']) {
            $penghalang = $user->peminjaman()->whereIn('status', StatusPeminjaman::blockingValues())->urutJadwal()->first();
        }

        return view('akun.peminjaman.create', [
            'ruangan' => $ruangan,
            'dipilih' => $dipilih,
            'tanggalAwal' => is_string($tanggal) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) ? $tanggal : null,
            'aturan' => $aturan,
            'penghalang' => $penghalang,
            'user' => $user,
        ]);
    }

    public function store(StorePeminjamanRequest $request): RedirectResponse
    {
        $ruangan = Ruangan::query()->where('slug', $request->validated('ruangan'))->firstOrFail();

        $peminjaman = $this->service->ajukan($request->user(), $ruangan, $request->validated(), $request->file('jaminan'));

        return redirect()->route('akun.peminjaman.show', $peminjaman)
            ->with('toast', ['type' => 'success', 'message' => 'Pengajuan terkirim! Kami akan memberi tahu Anda setelah admin meninjau.'])
            ->with('baru', true);
    }

    public function show(Peminjaman $peminjaman): View
    {
        Gate::authorize('view', $peminjaman);

        $peminjaman->load(['ruangan', 'pemroses:id_user,username', 'log.user:id_user,username,user_role']);

        return view('akun.peminjaman.show', ['peminjaman' => $peminjaman]);
    }

    public function batal(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        Gate::authorize('batalkan', $peminjaman);
        $data = $request->validate(['alasan' => ['required', 'string', 'min:5', 'max:500']]);

        $this->service->batalkan($peminjaman, $request->user(), $data['alasan']);

        return back()->with('toast', ['type' => 'success', 'message' => 'Peminjaman dibatalkan. Slot kini tersedia untuk orang lain.']);
    }

    public function checkIn(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        Gate::authorize('checkIn', $peminjaman);
        $request->validate(['foto' => $this->aturanFoto()]);

        $this->service->checkIn($peminjaman, $request->file('foto'));

        return back()->with('toast', ['type' => 'success', 'message' => 'Check-in berhasil. Selamat berlatih!']);
    }

    public function checkOut(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        Gate::authorize('checkOut', $peminjaman);
        $data = $request->validate([
            'foto' => $this->aturanFoto(),
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $this->service->checkOut($peminjaman, $request->file('foto'), $data['catatan'] ?? null);

        return back()->with('toast', ['type' => 'success', 'message' => 'Check-out berhasil. Terima kasih telah menjaga studio!']);
    }

    public function ulasan(Request $request, Peminjaman $peminjaman): RedirectResponse
    {
        Gate::authorize('ulas', $peminjaman);
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'review' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->service->beriUlasan($peminjaman, (int) $data['rating'], $data['review'] ?? null);

        return back()->with('toast', ['type' => 'success', 'message' => 'Terima kasih atas ulasannya!']);
    }

    /** @return array<int, string> */
    private function aturanFoto(): array
    {
        $upload = config('studio.upload');

        return ['required', 'image', 'mimes:'.implode(',', $upload['gambar_mimes']), 'max:'.$upload['gambar_maks_kb']];
    }
}
