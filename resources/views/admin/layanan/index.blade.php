<x-layouts.app title="Layanan Jasa">
    <x-page-header title="Layanan jasa musik" description="Atur katalog layanan dan formulir pemesanan masing-masing.">
        <x-button :href="route('admin.layanan.create')" icon="heroicon-m-plus">Tambah layanan</x-button>
    </x-page-header>

    @if ($layanan->isEmpty())
        <x-card><x-empty icon="heroicon-o-sparkles" title="Belum ada layanan" description="Tambahkan layanan pertama seperti Jingle atau Minus One."><x-button :href="route('admin.layanan.create')" icon="heroicon-m-plus">Tambah layanan</x-button></x-empty></x-card>
    @else
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($layanan as $l)
                <article class="card flex flex-col overflow-hidden">
                    <div class="relative">
                        <img src="{{ $l->gambar_url }}" alt="" loading="lazy" class="aspect-[16/9] w-full object-cover {{ $l->is_active ? '' : 'grayscale' }}">
                        <x-badge :tone="$l->is_active ? 'emerald' : 'zinc'" class="absolute top-3 left-3" dot>{{ $l->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                    </div>
                    <div class="flex flex-1 flex-col p-5">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ $l->nama_jenis_jasa }}</h2>
                        <p class="mt-1 line-clamp-2 text-sm text-zinc-500">{{ $l->ringkasan ?? str(strip_tags((string) $l->deskripsi))->limit(110) }}</p>
                        <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
                            <div class="rounded-lg bg-zinc-50 p-2 dark:bg-zinc-800/60"><dt class="text-[11px] text-zinc-500">Pesanan</dt><dd class="font-bold tabular-nums">{{ $l->pesanan_count }}</dd></div>
                            <div class="rounded-lg bg-zinc-50 p-2 dark:bg-zinc-800/60"><dt class="text-[11px] text-zinc-500">Aktif</dt><dd class="font-bold tabular-nums">{{ $l->aktif_count }}</dd></div>
                            <div class="rounded-lg bg-zinc-50 p-2 dark:bg-zinc-800/60"><dt class="text-[11px] text-zinc-500">Rating</dt><dd class="font-bold tabular-nums">{{ $l->rating_avg ? number_format($l->rating_avg, 1, ',', '.') : '—' }}</dd></div>
                        </dl>
                        <p class="mt-3 text-xs text-zinc-500">{{ $l->field_count }} field formulir · ± {{ $l->estimasi_hari }} hari</p>
                        <div class="mt-4 flex gap-2 pt-1">
                            <x-button :href="route('admin.layanan.edit', $l)" variant="secondary" size="sm" class="flex-1" icon="heroicon-o-pencil-square">Ubah</x-button>
                            <x-button :href="route('layanan.show', $l)" variant="ghost" size="icon-sm" icon="heroicon-o-eye" target="_blank" aria-label="Lihat halaman publik" />
                            <form method="POST" action="{{ route('admin.layanan.destroy', $l) }}" data-konfirmasi="{{ $l->pesanan_count ? 'Layanan memiliki riwayat pesanan sehingga akan dinonaktifkan. Lanjutkan?' : 'Hapus layanan ini secara permanen?' }}">
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
