<x-layouts.publik title="Tutorial Alat" description="Panduan penggunaan alat musik dan perangkat di Studio Musik ITERA.">
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
        <x-page-header title="Tutorial penggunaan alat" description="Pelajari cara menyalakan, mengatur, dan merapikan kembali peralatan studio agar tetap awet." />

        <form method="GET" class="mb-6 flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-zinc-400" />
                <input type="search" name="q" value="{{ $cari }}" placeholder="Cari alat, mis. mixer, ampli bass…" class="input pl-11" aria-label="Cari tutorial">
            </div>
            @if ($kategori->isNotEmpty())
                <select name="kategori" class="input sm:w-56" onchange="this.form.submit()" aria-label="Kategori">
                    <option value="">Semua kategori</option>
                    @foreach ($kategori as $k)
                        <option value="{{ $k }}" @selected($kategoriAktif === $k)>{{ $k }}</option>
                    @endforeach
                </select>
            @endif
            <x-button type="submit" variant="secondary">Cari</x-button>
        </form>

        @if ($tutorial->isEmpty())
            <x-empty class="card" icon="heroicon-o-book-open" title="Tutorial tidak ditemukan" :description="$cari ? 'Coba kata kunci lain.' : 'Belum ada tutorial yang ditambahkan.'" />
        @else
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($tutorial as $t)
                    <a href="{{ route('tutorial.show', $t) }}" class="card group overflow-hidden transition hover:-translate-y-1 hover:shadow-lift">
                        <div class="relative overflow-hidden bg-zinc-100 dark:bg-zinc-800">
                            <img src="{{ $t->gambar_url }}" alt="{{ $t->nama_alat }}" loading="lazy" class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-105">
                            @if ($t->video_url)
                                <span class="absolute right-3 bottom-3 inline-flex items-center gap-1 rounded-full bg-zinc-950/70 px-2 py-1 text-[11px] font-semibold text-white"><x-heroicon-s-play class="size-3" /> Video</span>
                            @endif
                        </div>
                        <div class="p-4">
                            @if ($t->kategori)
                                <p class="text-[11px] font-bold tracking-wider text-brand-600 uppercase dark:text-brand-400">{{ $t->kategori }}</p>
                            @endif
                            <h2 class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ $t->nama_alat }}</h2>
                            <p class="mt-1.5 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $t->ringkasan }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-8">{{ $tutorial->links() }}</div>
        @endif
    </section>
</x-layouts.publik>
