<x-layouts.app title="Jasa Musik">
    <x-page-header title="Jasa musik" description="Pesan jingle, aransemen, atau minus one dan pantau progres produksinya." />

    @if ($layanan->isNotEmpty())
        <div class="mb-8">
            <p class="mb-3 text-sm font-semibold text-zinc-900 dark:text-white">Buat pesanan baru</p>
            <div class="-mx-4 flex snap-x gap-3 overflow-x-auto px-4 pb-2 scrollbar-tipis sm:mx-0 sm:grid sm:grid-cols-2 sm:px-0 lg:grid-cols-3">
                @foreach ($layanan as $l)
                    <a href="{{ route('akun.jasa.create', $l) }}" class="card group flex w-64 shrink-0 snap-start items-center gap-3 p-3 transition hover:shadow-lift sm:w-auto">
                        <img src="{{ $l->gambar_url }}" alt="" loading="lazy" class="size-14 rounded-xl object-cover">
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-zinc-900 dark:text-white">{{ $l->nama_jenis_jasa }}</p>
                            <p class="text-xs text-zinc-500">± {{ $l->estimasi_hari }} hari · {{ $l->harga_label }}</p>
                        </div>
                        <span class="grid size-8 place-items-center rounded-lg bg-brand-50 text-brand-600 transition group-hover:bg-brand-600 group-hover:text-white dark:bg-brand-500/10 dark:text-brand-300">
                            <x-heroicon-m-plus class="size-4" />
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <x-tabs class="mb-5" :items="[
        ['label' => 'Berjalan', 'href' => route('akun.jasa.index', ['tab' => 'aktif']), 'aktif' => $tab === 'aktif', 'jumlah' => $jumlah['aktif']],
        ['label' => 'Riwayat', 'href' => route('akun.jasa.index', ['tab' => 'riwayat']), 'aktif' => $tab === 'riwayat', 'jumlah' => $jumlah['riwayat']],
    ]" />

    <div class="card overflow-hidden">
        @forelse ($pesanan as $j)
            <a href="{{ route('akun.jasa.show', $j) }}" class="group flex items-center gap-4 border-b border-zinc-100 px-4 py-4 last:border-0 hover:bg-zinc-50 sm:px-6 dark:border-zinc-800 dark:hover:bg-zinc-800/40">
                <img src="{{ $j->jasa->gambar_url }}" alt="" loading="lazy" class="size-12 shrink-0 rounded-xl object-cover">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="truncate font-semibold text-zinc-900 dark:text-white">{{ $j->judul }}</p>
                        <x-status :status="$j->status" size="sm" pendek />
                        @if ($j->terlambat())
                            <x-badge tone="rose" size="sm">Lewat tenggat</x-badge>
                        @endif
                    </div>
                    <p class="mt-0.5 text-sm text-zinc-500">{{ $j->jasa->nama_jenis_jasa }} · tenggat {{ tanggal($j->tenggat_produksi) }}</p>
                    @if ($j->status->isActive())
                        <div class="mt-2 flex max-w-xs gap-1">
                            @for ($i = 1; $i <= 4; $i++)
                                <span @class(['h-1 flex-1 rounded-full', 'bg-brand-500' => $i <= $j->status->step(), 'bg-zinc-200 dark:bg-zinc-700' => $i > $j->status->step()])></span>
                            @endfor
                        </div>
                    @endif
                </div>
                @if ($j->bisaDiulas())
                    <x-badge tone="amber" class="hidden sm:inline-flex" icon="heroicon-m-star">Beri ulasan</x-badge>
                @endif
                <x-heroicon-m-chevron-right class="size-5 shrink-0 text-zinc-300 transition group-hover:translate-x-0.5" />
            </a>
        @empty
            <x-empty icon="heroicon-o-musical-note" :title="$tab === 'aktif' ? 'Tidak ada pesanan berjalan' : 'Belum ada riwayat'"
                description="Pilih layanan di atas untuk membuat pesanan pertama Anda." />
        @endforelse
    </div>

    @if ($pesanan->hasPages())
        <div class="mt-6">{{ $pesanan->links() }}</div>
    @endif
</x-layouts.app>
