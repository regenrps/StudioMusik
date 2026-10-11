<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotifikasiController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter') === 'belum' ? 'belum' : 'semua';

        $notifikasi = $request->user()->notifications()
            ->when($filter === 'belum', fn ($q) => $q->whereNull('read_at'))
            ->paginate(15)->withQueryString();

        return view('akun.notifikasi', ['notifikasi' => $notifikasi, 'filter' => $filter]);
    }

    /** Untuk dropdown lonceng di topbar. */
    public function terbaru(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'belum_dibaca' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()->limit(8)->get()->map(fn (DatabaseNotification $n) => [
                'id' => $n->id,
                'judul' => $n->data['judul'] ?? 'Notifikasi',
                'pesan' => $n->data['pesan'] ?? '',
                'tone' => $n->data['tone'] ?? 'indigo',
                'url' => route('notifikasi.buka', $n->id),
                'waktu' => $n->created_at?->locale('id')->diffForHumans(),
                'dibaca' => $n->read_at !== null,
            ]),
        ]);
    }

    /** Tandai dibaca lalu arahkan ke halaman terkait. */
    public function buka(Request $request, string $id): RedirectResponse
    {
        $notifikasi = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notifikasi->markAsRead();

        $url = $notifikasi->data['url'] ?? null;

        // Hanya izinkan tujuan internal.
        if (! $url || ! str_starts_with($url, url('/'))) {
            return redirect()->route('notifikasi.index');
        }

        return redirect()->to($url);
    }

    public function bacaSemua(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : back()->with('toast', ['type' => 'success', 'message' => 'Semua notifikasi ditandai sudah dibaca.']);
    }
}
