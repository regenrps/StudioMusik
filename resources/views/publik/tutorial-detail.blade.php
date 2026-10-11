<x-layouts.publik :title="$tutorial->nama_alat">
    <article class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:py-12">
        <a href="{{ route('tutorial.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-zinc-500 hover:text-zinc-800 dark:hover:text-white">
            <x-heroicon-m-chevron-left class="size-4" /> Semua tutorial
        </a>
        @if ($tutorial->kategori)
            <p class="text-xs font-bold tracking-widest text-brand-600 uppercase dark:text-brand-400">{{ $tutorial->kategori }}</p>
        @endif
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">{{ $tutorial->nama_alat }}</h1>

        @if ($tutorial->video_embed_url)
            <div class="mt-8 aspect-video overflow-hidden rounded-2xl bg-zinc-900 shadow-soft">
                <iframe src="{{ $tutorial->video_embed_url }}" title="Video tutorial {{ $tutorial->nama_alat }}" class="size-full" loading="lazy"
                    allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        @else
            <img src="{{ $tutorial->gambar_url }}" alt="{{ $tutorial->nama_alat }}" class="mt-8 max-h-[28rem] w-full rounded-2xl object-cover shadow-soft">
        @endif

        <div class="prose-konten card mt-8 p-6 text-base sm:p-8">{!! konten($tutorial->deskripsi) !!}</div>

        @if ($tutorial->video_url && ! $tutorial->video_embed_url)
            <x-button :href="$tutorial->video_url" target="_blank" rel="noopener" variant="secondary" class="mt-4" icon="heroicon-o-play-circle">Tonton video tutorial</x-button>
        @endif

        @if ($lainnya->isNotEmpty())
            <h2 class="mt-12 text-lg font-semibold text-zinc-900 dark:text-white">Tutorial lainnya</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($lainnya as $l)
                    <a href="{{ route('tutorial.show', $l) }}" class="card group overflow-hidden">
                        <img src="{{ $l->gambar_url }}" alt="" loading="lazy" class="aspect-[4/3] w-full object-cover">
                        <p class="p-3 text-sm font-semibold text-zinc-800 group-hover:text-brand-600 dark:text-zinc-200">{{ $l->nama_alat }}</p>
                    </a>
                @endforeach
            </div>
        @endif
    </article>
</x-layouts.publik>
