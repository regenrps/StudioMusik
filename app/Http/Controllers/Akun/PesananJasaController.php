<?php

namespace App\Http\Controllers\Akun;

use App\Enums\StatusPesananJasa;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePesananJasaRequest;
use App\Models\JasaMusik;
use App\Models\PesananJasa;
use App\Services\PesananJasaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PesananJasaController extends Controller
{
    public function __construct(private readonly PesananJasaService $service)
    {
    }

    public function index(Request $request): View
    {
        $tab = in_array($request->query('tab'), ['aktif', 'riwayat'], true) ? $request->query('tab') : 'aktif';
        $user = $request->user();

        $pesanan = $user->pesananJasa()->with('jasa')
            ->when($tab === 'aktif',
                fn ($q) => $q->whereIn('status', StatusPesananJasa::activeValues())->orderBy('tenggat_produksi'),
                fn ($q) => $q->whereNotIn('status', StatusPesananJasa::activeValues())->latest())
            ->paginate(10)->withQueryString();

        return view('akun.jasa.index', [
            'pesanan' => $pesanan,
            'tab' => $tab,
            'jumlah' => [
                'aktif' => $user->pesananJasa()->whereIn('status', StatusPesananJasa::activeValues())->count(),
                'riwayat' => $user->pesananJasa()->whereNotIn('status', StatusPesananJasa::activeValues())->count(),
            ],
            'layanan' => JasaMusik::query()->aktif()->urut()->get(),
        ]);
    }

    public function create(JasaMusik $jasa): View
    {
        abort_unless($jasa->is_active, 404);
        $jasa->load('field');

        return view('akun.jasa.create', [
            'jasa' => $jasa,
            'tenggatMin' => today()->addDays((int) pengaturan('jasa_tenggat_min_hari', 3)),
            'tenggatMaks' => today()->addDays((int) pengaturan('jasa_tenggat_maks_hari', 90)),
            'tenggatSaran' => today()->addDays(max((int) pengaturan('jasa_tenggat_min_hari', 3), $jasa->estimasi_hari)),
        ]);
    }

    public function store(StorePesananJasaRequest $request, JasaMusik $jasa): RedirectResponse
    {
        $pesanan = $this->service->buat($request->user(), $jasa, $request->validated(), $request->berkasField());

        return redirect()->route('akun.jasa.show', $pesanan)
            ->with('toast', ['type' => 'success', 'message' => 'Pesanan terkirim! Admin akan meninjau dan mengabari Anda.'])
            ->with('baru', true);
    }

    public function show(PesananJasa $pesanan): View
    {
        Gate::authorize('view', $pesanan);

        $pesanan->load(['jasa', 'informasi', 'log.user:id_user,username,user_role']);

        return view('akun.jasa.show', ['pesanan' => $pesanan]);
    }

    public function batal(Request $request, PesananJasa $pesanan): RedirectResponse
    {
        Gate::authorize('batalkan', $pesanan);
        $data = $request->validate(['alasan' => ['required', 'string', 'min:5', 'max:500']]);

        $this->service->batalkan($pesanan, $request->user(), $data['alasan']);

        return back()->with('toast', ['type' => 'success', 'message' => 'Pesanan dibatalkan.']);
    }

    public function ulasan(Request $request, PesananJasa $pesanan): RedirectResponse
    {
        Gate::authorize('ulas', $pesanan);
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'review' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->service->beriUlasan($pesanan, (int) $data['rating'], $data['review'] ?? null);

        return back()->with('toast', ['type' => 'success', 'message' => 'Terima kasih atas ulasannya!']);
    }
}
