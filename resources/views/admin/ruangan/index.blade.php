<x-layouts.app title="Ruangan">
    <x-page-header title="Ruangan studio" description="Kelola ruangan yang dapat dipinjam beserta foto dan fasilitasnya.">
        <x-button :href="route('admin.ruangan.create')" icon="heroicon-m-plus">Tambah ruangan</x-button>
    </x-page-header>

    @if ($ruangan->isEmpty())
        <x-card><x-empty icon="heroicon-o-building-office-2" title="Belum ada ruangan"><x-button :href="route('admin.ruangan.create')" icon="heroicon-m-plus">Tambah ruangan</x-button></x-empty></x-card>
    @else
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            @foreach ($ruangan as $r)
                <article class="card flex flex-col overflow-hidden sm:flex-row">
                    <img src="{{ $r->thumbnail_url }}" alt="" loading="lazy" class="aspect-video w-full object-cover sm:aspect-auto sm:w-48 {{ $r->is_active ? '' : 'grayscale' }}">
                    <div class="flex flex-1 flex-col p-5">
                        <div class="flex items-start justify-between gap-2">
                            <h2 class="font-semibold text-zinc-900 dark:text-white">{{ $r->nama_ruangan }}</h2>
                            <x-badge :tone="$r->is_active ? 'emerald' : 'zinc'" size="sm" dot>{{ $r->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                        </div>
                        <p class="mt-1 text-sm text-zinc-500">{{ $r->lokasi ?? '—' }} · {{ $r->kapasitas }} orang · {{ $r->foto->count() + 1 }} foto</p>
                        <div class="mt-3 flex flex-wrap gap-3 text-xs text-zinc-500">
                            <span><strong class="text-zinc-800 dark:text-zinc-200">{{ $r->peminjaman_count }}</strong> peminjaman</span>
                            <span><strong class="text-zinc-800 dark:text-zinc-200">{{ $r->aktif_count }}</strong> aktif</span>
                            <span><strong class="text-zinc-800 dark:text-zinc-200">{{ $r->rating_avg ? number_format($r->rating_avg, 1, ',', '.') : '—' }}</strong> rating</span>
                        </div>
                        <div class="mt-auto flex gap-2 pt-4">
                            <x-button :href="route('admin.ruangan.edit', $r)" variant="secondary" size="sm" class="flex-1" icon="heroicon-o-pencil-square">Ubah</x-button>
                            <x-button :href="route('ruangan.show', $r)" target="_blank" variant="ghost" size="icon-sm" icon="heroicon-o-eye" aria-label="Lihat halaman publik" />
                            <form method="POST" action="{{ route('admin.ruangan.destroy', $r) }}" data-konfirmasi="{{ $r->peminjaman_count ? 'Ruangan memiliki riwayat sehingga akan dinonaktifkan. Lanjutkan?' : 'Hapus ruangan ini secara permanen?' }}">
                                @csrf @method('DELETE')
                                <x-button type="submit" variant="ghost" size="icon-sm" icon="heroicon-o-trash" class="hover:text-rose-600" aria-label="Hapus" />
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</x-layouts.app>
