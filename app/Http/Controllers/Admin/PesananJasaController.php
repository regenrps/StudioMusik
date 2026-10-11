<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPesananJasa;
use App\Http\Controllers\Controller;
use App\Models\JasaMusik;
use App\Models\PesananJasa;
use App\Services\PesananJasaService;
use App\Support\EksporCsv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PesananJasaController extends Controller
{
    public function __construct(private readonly PesananJasaService $service)
    {
    }

    public function index(Request $request): View
    {
        $filter = $this->filter($request);

        $pesanan = $this->query($filter)->with('user:id_user,username,email', 'jasa:id_jasa_musik,nama_jenis_jasa')
            ->when(in_array($filter['status'], ['semua', ...StatusPesananJasa::activeValues()], true),
                fn ($q) => $q->orderByRaw("FIELD(status, 'pending', 'in_production', 'approved') = 0")->orderBy('tenggat_produksi'),
                fn ($q) => $q->latest())
            ->paginate(15)->withQueryString();

        return view('admin.jasa.index', [
            'pesanan' => $pesanan,
            'filter' => $filter,
            'hitung' => PesananJasa::query()->selectRaw('status, COUNT(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status'),
            'layanan' => JasaMusik::query()->urut()->get(['id_jasa_musik', 'nama_jenis_jasa', 'slug']),
        ]);
    }

    public function ekspor(Request $request): StreamedResponse
    {
        $baris = $this->query($this->filter($request))->with('user', 'jasa', 'informasi')->latest()->lazy(200)
            ->map(fn (PesananJasa $p) => [
                $p->kode,
                $p->status->label(),
                $p->jasa?->nama_jenis_jasa,
                $p->judul,
                $p->user?->username,
                $p->user?->email,
                $p->user?->no_wa,
                $p->tgl_produksi?->format('Y-m-d'),
                $p->tenggat_produksi?->format('Y-m-d'),
                $p->informasi->where('tipe_field', '!=', 'file')->map(fn ($i) => $i->nama_field.': '.$i->value_field)->implode(' | '),
                $p->keterangan,
                $p->keterangan_admin,
                $p->selesai_at?->format('Y-m-d H:i'),
                $p->rating,
                $p->review,
                $p->created_at?->format('Y-m-d H:i'),
            ]);

        return EksporCsv::unduh('pesanan-jasa-musik-'.now()->format('Ymd-His').'.csv', [
            'Kode', 'Status', 'Layanan', 'Judul', 'Pemesan', 'Email', 'WhatsApp', 'Mulai Produksi', 'Tenggat',
            'Detail', 'Catatan Pemesan', 'Catatan Admin', 'Selesai', 'Rating', 'Ulasan', 'Dipesan',
        ], $baris);
    }

    public function show(PesananJasa $pesanan): View
    {
        $pesanan->load(['user', 'jasa', 'informasi', 'pemroses:id_user,username', 'log.user:id_user,username,user_role']);

        $antrean = PesananJasa::query()->aktif()->whereKeyNot($pesanan->getKey())
            ->whereBetween('tenggat_produksi', [today()->toDateString(), today()->addDays(30)->toDateString()])->count();

        return view('admin.jasa.show', ['pesanan' => $pesanan, 'antreanLain' => $antrean]);
    }

    public function setujui(Request $request, PesananJasa $pesanan): RedirectResponse
    {
        $data = $request->validate([
            'tgl_produksi' => ['required', 'date_format:Y-m-d'],
            'tenggat_produksi' => ['required', 'date_format:Y-m-d', 'after_or_equal:tgl_produksi'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'langsung_produksi' => ['nullable', 'boolean'],
        ]);

        $this->service->setujui($pesanan, $request->user(), Carbon::parse($data['tgl_produksi']),
            Carbon::parse($data['tenggat_produksi']), $data['catatan'] ?? null, (bool) ($data['langsung_produksi'] ?? false));

        return back()->with('toast', ['type' => 'success', 'message' => 'Pesanan '.$pesanan->kode.' disetujui.']);
    }

    public function jadwal(Request $request, PesananJasa $pesanan): RedirectResponse
    {
        $data = $request->validate([
            'tgl_produksi' => ['required', 'date_format:Y-m-d'],
            'tenggat_produksi' => ['required', 'date_format:Y-m-d', 'after_or_equal:tgl_produksi'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->service->perbaruiJadwal($pesanan, $request->user(), Carbon::parse($data['tgl_produksi']),
            Carbon::parse($data['tenggat_produksi']), $data['catatan'] ?? null);

        return back()->with('toast', ['type' => 'success', 'message' => 'Jadwal produksi diperbarui.']);
    }

    public function tolak(Request $request, PesananJasa $pesanan): RedirectResponse
    {
        $data = $request->validate(['alasan' => ['required', 'string', 'min:5', 'max:1000']]);
        $this->service->tolak($pesanan, $request->user(), $data['alasan']);

        return back()->with('toast', ['type' => 'success', 'message' => 'Pesanan '.$pesanan->kode.' ditolak.']);
    }

    public function mulai(Request $request, PesananJasa $pesanan): RedirectResponse
    {
        $this->service->mulaiProduksi($pesanan, $request->user());

        return back()->with('toast', ['type' => 'success', 'message' => 'Produksi dimulai. Pemesan telah diberi tahu.']);
    }

    public function selesai(Request $request, PesananJasa $pesanan): RedirectResponse
    {
        $upload = config('studio.upload');
        $data = $request->validate([
            'hasil' => ['nullable', 'file', 'mimes:'.implode(',', $upload['hasil_mimes']), 'max:'.($upload['audio_maks_kb'] * 5)],
            'tautan' => ['nullable', 'url', 'max:500', 'required_without:hasil'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ], ['tautan.required_without' => 'Unggah berkas hasil atau cantumkan tautan hasil produksi.']);

        $this->service->selesaikan($pesanan, $request->user(), $request->file('hasil'), $data['tautan'] ?? null, $data['catatan'] ?? null);

        return back()->with('toast', ['type' => 'success', 'message' => 'Pesanan selesai. Hasil telah dikirim ke pemesan.']);
    }

    public function batal(Request $request, PesananJasa $pesanan): RedirectResponse
    {
        $data = $request->validate(['alasan' => ['required', 'string', 'min:5', 'max:1000']]);
        $this->service->batalkan($pesanan, $request->user(), $data['alasan']);

        return back()->with('toast', ['type' => 'success', 'message' => 'Pesanan '.$pesanan->kode.' dibatalkan.']);
    }

    /** @return array<string, mixed> */
    private function filter(Request $request): array
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['semua', ...array_column(StatusPesananJasa::cases(), 'value')])],
            'layanan' => ['nullable', 'string'],
        ]);

        return [
            'q' => trim((string) ($data['q'] ?? '')),
            'status' => $data['status'] ?? 'semua',
            'layanan' => $data['layanan'] ?? null,
        ];
    }

    private function query(array $filter): Builder
    {
        return PesananJasa::query()
            ->when($filter['status'] !== 'semua', fn ($q) => $q->where('status', $filter['status']))
            ->when($filter['layanan'], fn ($q, $slug) => $q->whereHas('jasa', fn ($j) => $j->where('slug', $slug)))
            ->when($filter['q'] !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('kode', 'like', '%'.$filter['q'].'%')
                ->orWhere('judul', 'like', '%'.$filter['q'].'%')
                ->orWhereHas('user', fn ($u) => $u->where('username', 'like', '%'.$filter['q'].'%')
                    ->orWhere('email', 'like', '%'.$filter['q'].'%'))));
    }
}
