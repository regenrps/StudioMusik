<x-layouts.app title="Tutorial Alat">
    <x-page-header title="Tutorial penggunaan alat" description="Panduan yang tampil di halaman publik untuk membantu peminjam memakai alat dengan benar.">
        <x-button :href="route('admin.tutorial.create')" icon="heroicon-m-plus">Tambah tutorial</x-button>
    </x-page-header>

    <form method="GET" class="mb-4 flex gap-2">
        <div class="relative flex-1">
            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-zinc-400" />
            <input type="search" name="q" value="{{ $q }}" placeholder="Cari nama alat…" class="input pl-11" aria-label="Cari">
        </div>
        <x-button type="submit" variant="secondary">Cari</x-button>
    </form>

    @if ($tutorial->isEmpty())
        <x-card><x-empty icon="heroicon-o-book-open" title="Belum ada tutorial"><x-button :href="route('admin.tutorial.create')" icon="heroicon-m-plus">Tambah tutorial</x-button></x-empty></x-card>
    @else
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($tutorial as $t)
                <article class="card flex flex-col overflow-hidden">
                    <img src="{{ $t->gambar_url }}" alt="" loading="lazy" class="aspect-[4/3] w-full object-cover">
                    <div class="flex flex-1 flex-col p-4">
                        @if ($t->kategori)<p class="text-[11px] font-bold tracking-wider text-brand-600 uppercase dark:text-brand-400">{{ $t->kategori }}</p>@endif
                        <h2 class="mt-0.5 font-semibold text-zinc-900 dark:text-white">{{ $t->nama_alat }}</h2>
                        <p class="mt-1 line-clamp-2 flex-1 text-sm text-zinc-500">{{ $t->ringkasan }}</p>
                        <div class="mt-4 flex gap-2">
                            <x-button :href="route('admin.tutorial.edit', $t)" variant="secondary" size="sm" class="flex-1" icon="heroicon-o-pencil-square">Ubah</x-button>
                            <x-button :href="route('tutorial.show', $t)" target="_blank" variant="ghost" size="icon-sm" icon="heroicon-o-eye" aria-label="Lihat" />
                            <form method="POST" action="{{ route('admin.tutorial.destroy', $t) }}" data-konfirmasi="Hapus tutorial {{ $t->nama_alat }}?">
                                @csrf @method('DELETE')
                                <x-button type="submit" variant="ghost" size="icon-sm" icon="heroicon-o-trash" class="hover:text-rose-600" aria-label="Hapus" />
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-6">{{ $tutorial->links() }}</div>
    @endif
</x-layouts.app>
