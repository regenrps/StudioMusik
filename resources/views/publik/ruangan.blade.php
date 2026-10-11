@php
    $user = auth()->user();
    $ctaPinjam = $user && ! $user->isStaff() ? route('akun.peminjaman.create', ['ruangan' => $ruangan->slug]) : ($user ? route('admin.dashboard') : route('login'));
    $galeri = collect([$ruangan->thumbnail_url])->merge($ruangan->foto->pluck('url'))->unique()->values();
@endphp
<x-layouts.publik :title="$ruangan->nama_ruangan">
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <a href="{{ route('beranda') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-zinc-500 hover:text-zinc-800 dark:hover:text-white">
            <x-heroicon-m-chevron-left class="size-4" /> Beranda
        </a>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_380px]">
            <div class="min-w-0 space-y-8">
                {{-- Galeri --}}
                <div x-data="{ aktif: 0, foto: @js($galeri) }">
                    <div class="relative overflow-hidden rounded-3xl bg-zinc-200 dark:bg-zinc-800">
                        <img :src="foto[aktif]" alt="{{ $ruangan->nama_ruangan }}" class="aspect-[16/9] w-full object-cover">
                        @if ($galeri->count() > 1)
                            <button type="button" @click="aktif = (aktif - 1 + foto.length) % foto.length" class="absolute top-1/2 left-3 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-white/80 text-zinc-800 shadow backdrop-blur hover:bg-white" aria-label="Foto sebelumnya"><x-heroicon-m-chevron-left class="size-5" /></button>
                            <button type="button" @click="aktif = (aktif + 1) % foto.length" class="absolute top-1/2 right-3 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-white/80 text-zinc-800 shadow backdrop-blur hover:bg-white" aria-label="Foto berikutnya"><x-heroicon-m-chevron-right class="size-5" /></button>
                            <span class="absolute right-3 bottom-3 rounded-full bg-zinc-950/60 px-2.5 py-1 text-xs font-semibold text-white" x-text="`${aktif + 1} / ${foto.length}`"></span>
                        @endif
                    </div>
                    @if ($galeri->count() > 1)
                        <div class="mt-3 flex gap-2 overflow-x-auto pb-1 scrollbar-tipis">
                            @foreach ($galeri as $i => $url)
                                <button type="button" @click="aktif = {{ $i }}" class="shrink-0 overflow-hidden rounded-xl ring-2 transition" :class="aktif === {{ $i }} ? 'ring-brand-500' : 'ring-transparent opacity-70 hover:opacity-100'">
                                    <img src="{{ $url }}" alt="" loading="lazy" class="h-16 w-24 object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        @unless ($ruangan->is_active)
                            <x-badge tone="zinc">Nonaktif</x-badge>
                        @endunless
                        <x-badge tone="emerald" dot>Gratis untuk civitas ITERA</x-badge>
                        @if ($ringkasan['rata'])
                            <x-badge tone="amber" icon="heroicon-s-star">{{ number_format($ringkasan['rata'], 1, ',', '.') }} · {{ $ringkasan['jumlah'] }} ulasan</x-badge>
                        @endif
                    </div>
                    <h1 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $ruangan->nama_ruangan }}</h1>
                    <p class="mt-2 flex items-center gap-1.5 text-zinc-500"><x-heroicon-m-map-pin class="size-4" /> {{ $ruangan->lokasi ?? config('studio.lokasi') }}</p>
                    @if ($ruangan->deskripsi)
                        <div class="prose-konten mt-5 max-w-3xl text-base">{!! konten($ruangan->deskripsi) !!}</div>
                    @endif
                </div>

                @if ($ruangan->daftar_fasilitas)
                    <x-card title="Fasilitas" icon="heroicon-o-musical-note">
                        <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @foreach ($ruangan->daftar_fasilitas as $f)
                                <li class="flex items-start gap-2.5 text-sm text-zinc-700 dark:text-zinc-300">
                                    <x-heroicon-s-check-circle class="mt-px size-5 shrink-0 text-emerald-500" /> {{ $f }}
                                </li>
                            @endforeach
                        </ul>
                    </x-card>
                @endif

                <x-card title="Ulasan peminjam" icon="heroicon-o-chat-bubble-left-ellipsis" :padding="false">
                    @forelse ($ulasan as $u)
                        <div class="flex gap-4 border-b border-zinc-100 px-5 py-4 last:border-0 sm:px-6 dark:border-zinc-800">
                            <x-avatar :user="$u->user" size="sm" />
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $u->user?->username ?? 'Pengguna' }}</p>
                                    <x-rating :nilai="$u->rating" ukuran="size-3.5" />
                                    <span class="text-xs text-zinc-400">{{ relatif($u->diulas_at ?? $u->updated_at) }}</span>
                                </div>
                                @if ($u->review)
                                    <p class="mt-1.5 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">{{ $u->review }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <x-empty compact icon="heroicon-o-chat-bubble-left-ellipsis" title="Belum ada ulasan" description="Ulasan muncul setelah peminjam menyelesaikan sesi." />
                    @endforelse
                    @if ($ulasan->hasPages())
                        <x-slot:footer>{{ $ulasan->fragment('ulasan')->links() }}</x-slot:footer>
                    @endif
                </x-card>
            </div>

            {{-- Panel samping --}}
            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                <div class="card p-6">
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">Informasi peminjaman</p>
                    <dl class="mt-3 divide-y divide-zinc-100 dark:divide-zinc-800">
                        <x-info label="Hari" icon="heroicon-o-calendar">{{ $aturan['label_hari'] }}</x-info>
                        <x-info label="Jam" icon="heroicon-o-clock">{{ jam($aturan['jam_buka']) }}–{{ jam($aturan['jam_tutup']) }}</x-info>
                        <x-info label="Durasi" icon="heroicon-o-arrows-right-left">{{ durasi_label($aturan['durasi_min_menit']) }} – {{ durasi_label($aturan['durasi_maks_menit']) }}</x-info>
                        <x-info label="Kapasitas" icon="heroicon-o-user-group">{{ min($ruangan->kapasitas, $aturan['maks_orang']) }} orang</x-info>
                        <x-info label="Jaminan" icon="heroicon-o-identification">KTM / KTP</x-info>
                    </dl>
                    <x-button :href="$ctaPinjam" size="lg" class="mt-5 w-full" icon="heroicon-m-calendar-days">Pinjam ruangan ini</x-button>
                    <x-button :href="route('jadwal', ['ruangan' => $ruangan->slug])" variant="ghost" class="mt-2 w-full">Lihat jadwal minggu ini</x-button>
                </div>
                <x-alert tone="info" title="Datang tepat waktu">
                    Check-in dibuka 15 menit sebelum jadwal. Siapkan ponsel untuk memotret kondisi studio saat datang dan pulang.
                </x-alert>
            </aside>
        </div>
    </section>
</x-layouts.publik>
