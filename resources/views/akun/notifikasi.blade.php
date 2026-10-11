@php
    $warna = [
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
        'sky' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300',
        'violet' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300',
        'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
        'rose' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300',
        'orange' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/10 dark:text-orange-300',
        'zinc' => 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
        'indigo' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300',
    ];
    $belum = auth()->user()->unreadNotifications()->count();
@endphp
<x-layouts.app title="Notifikasi" lebar="max-w-3xl">
    <x-page-header title="Notifikasi" description="Kabar terbaru tentang peminjaman dan pesanan Anda.">
        @if ($belum)
            <form method="POST" action="{{ route('notifikasi.baca-semua') }}">
                @csrf
                <x-button type="submit" variant="secondary" icon="heroicon-o-check">Tandai semua dibaca</x-button>
            </form>
        @endif
    </x-page-header>

    <x-tabs class="mb-5" :items="[
        ['label' => 'Semua', 'href' => route('notifikasi.index'), 'aktif' => $filter === 'semua', 'jumlah' => null],
        ['label' => 'Belum dibaca', 'href' => route('notifikasi.index', ['filter' => 'belum']), 'aktif' => $filter === 'belum', 'jumlah' => $belum],
    ]" />

    <div class="card overflow-hidden">
        @forelse ($notifikasi as $n)
            <a href="{{ route('notifikasi.buka', $n->id) }}" @class([
                'flex gap-4 border-b border-zinc-100 px-4 py-4 last:border-0 transition hover:bg-zinc-50 sm:px-6 dark:border-zinc-800 dark:hover:bg-zinc-800/40',
                'bg-brand-50/40 dark:bg-brand-500/5' => ! $n->read_at,
            ])>
                <span class="grid size-10 shrink-0 place-items-center rounded-xl {{ $warna[$n->data['tone'] ?? 'indigo'] ?? $warna['indigo'] }}">
                    <x-dynamic-component :component="$n->data['ikon'] ?? 'heroicon-o-bell'" class="size-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $n->data['judul'] ?? 'Notifikasi' }}</p>
                        <span class="shrink-0 text-xs text-zinc-400" title="{{ tanggal_waktu($n->created_at) }}">{{ relatif($n->created_at) }}</span>
                    </div>
                    <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-400">{{ $n->data['pesan'] ?? '' }}</p>
                </div>
                @unless ($n->read_at)
                    <span class="mt-2 size-2 shrink-0 rounded-full bg-brand-500" aria-label="Belum dibaca"></span>
                @endunless
            </a>
        @empty
            <x-empty icon="heroicon-o-bell-slash" :title="$filter === 'belum' ? 'Semua sudah dibaca' : 'Belum ada notifikasi'" description="Notifikasi tentang status peminjaman dan pesanan akan muncul di sini." />
        @endforelse
    </div>

    @if ($notifikasi->hasPages())
        <div class="mt-6">{{ $notifikasi->links() }}</div>
    @endif
</x-layouts.app>
