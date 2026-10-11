<x-layouts.publik title="Jasa Musik" description="Pesan jingle, aransemen, dan minus one dari tim komposer UKMBSM ITERA.">
    <section class="relative overflow-hidden border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900/40">
        <div class="pointer-events-none absolute -top-32 right-0 h-80 w-[40rem] rounded-full bg-brand-300/20 blur-3xl dark:bg-brand-600/15"></div>
        <div class="relative mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <p class="text-sm font-bold tracking-widest text-brand-600 uppercase dark:text-brand-400">Jasa musik</p>
            <h1 class="mt-2 max-w-2xl text-4xl font-extrabold tracking-tight text-zinc-900 dark:text-white">Karya musik khusus untuk kebutuhan civitas ITERA</h1>
            <p class="mt-4 max-w-2xl text-lg text-zinc-500 dark:text-zinc-400">Ceritakan kebutuhanmu, unggah referensi, tentukan tenggat — tim komposer UKMBSM akan mengerjakannya dan mengabari setiap progres.</p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        @if ($layanan->isEmpty())
            <x-empty icon="heroicon-o-musical-note" title="Belum ada layanan" description="Layanan jasa musik akan tampil di sini." />
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($layanan as $jasa)
                    <article class="card group flex flex-col overflow-hidden">
                        <a href="{{ route('layanan.show', $jasa) }}" class="block overflow-hidden">
                            <img src="{{ $jasa->gambar_url }}" alt="{{ $jasa->nama_jenis_jasa }}" loading="lazy" class="aspect-[16/10] w-full object-cover transition duration-500 group-hover:scale-105">
                        </a>
                        <div class="flex flex-1 flex-col p-6">
                            <div class="flex items-start justify-between gap-3">
                                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $jasa->nama_jenis_jasa }}</h2>
                                @if ($jasa->rating_avg)
                                    <span class="inline-flex items-center gap-1 text-sm font-semibold text-zinc-700 dark:text-zinc-300"><x-heroicon-s-star class="size-4 text-amber-400" />{{ number_format($jasa->rating_avg, 1, ',', '.') }}</span>
                                @endif
                            </div>
                            <p class="mt-2 line-clamp-3 flex-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $jasa->ringkasan ?? str(strip_tags((string) $jasa->deskripsi))->limit(150) }}</p>
                            <div class="mt-5 flex flex-wrap gap-2 text-xs">
                                <x-badge tone="zinc" icon="heroicon-m-clock">± {{ $jasa->estimasi_hari }} hari kerja</x-badge>
                                <x-badge tone="emerald">{{ $jasa->harga_label }}</x-badge>
                                @if ($jasa->selesai_count)
                                    <x-badge tone="brand">{{ $jasa->selesai_count }} karya selesai</x-badge>
                                @endif
                            </div>
                            <x-button :href="route('layanan.show', $jasa)" variant="secondary" class="mt-6 w-full" icon-right="heroicon-m-arrow-right">Lihat detail & pesan</x-button>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.publik>
