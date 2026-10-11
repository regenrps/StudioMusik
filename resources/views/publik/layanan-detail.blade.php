@php
    $user = auth()->user();
    $ctaPesan = $user && ! $user->isStaff() ? route('akun.jasa.create', $jasa) : ($user ? route('admin.layanan.edit', $jasa) : route('login'));
@endphp
<x-layouts.publik :title="$jasa->nama_jenis_jasa">
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <a href="{{ route('layanan.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-zinc-500 hover:text-zinc-800 dark:hover:text-white">
            <x-heroicon-m-chevron-left class="size-4" /> Semua layanan
        </a>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1fr_360px]">
            <div class="min-w-0 space-y-8">
                <img src="{{ $jasa->gambar_url }}" alt="{{ $jasa->nama_jenis_jasa }}" class="aspect-[16/8] w-full rounded-3xl object-cover shadow-soft">

                <div>
                    <div class="flex flex-wrap gap-2">
                        @unless ($jasa->is_active)<x-badge tone="zinc">Nonaktif</x-badge>@endunless
                        <x-badge tone="zinc" icon="heroicon-m-clock">± {{ $jasa->estimasi_hari }} hari kerja</x-badge>
                        @if ($ringkasan['rata'])
                            <x-badge tone="amber" icon="heroicon-s-star">{{ number_format($ringkasan['rata'], 1, ',', '.') }} · {{ $ringkasan['jumlah'] }} ulasan</x-badge>
                        @endif
                    </div>
                    <h1 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $jasa->nama_jenis_jasa }}</h1>
                    <div class="prose-konten mt-4 text-base">{!! konten($jasa->deskripsi) !!}</div>
                </div>

                @if ($jasa->field->isNotEmpty())
                    <x-card title="Yang perlu Anda siapkan" description="Informasi ini akan diminta saat memesan." icon="heroicon-o-clipboard-document-list">
                        <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @foreach ($jasa->field as $field)
                                <li class="flex items-start gap-3 rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800/50">
                                    <x-dynamic-component :component="match ($field->jenis_field) { \App\Enums\TipeField::Berkas => 'heroicon-o-paper-clip', \App\Enums\TipeField::Tautan => 'heroicon-o-link', \App\Enums\TipeField::Angka => 'heroicon-o-hashtag', default => 'heroicon-o-pencil-square' }" class="mt-0.5 size-5 shrink-0 text-brand-500" />
                                    <div>
                                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $field->nama_field }} @unless ($field->is_required)<span class="text-xs font-normal text-zinc-400">(opsional)</span>@endunless</p>
                                        <p class="text-xs text-zinc-500">{{ $field->bantuan ?: $field->jenis_field->label() }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </x-card>
                @endif

                <x-card title="Syarat & ketentuan" icon="heroicon-o-document-check">
                    <div class="prose-konten">{!! konten($jasa->sk) !!}</div>
                </x-card>

                <x-card title="Ulasan pemesan" icon="heroicon-o-chat-bubble-left-ellipsis" :padding="false">
                    @forelse ($ulasan as $u)
                        <div class="flex gap-4 border-b border-zinc-100 px-5 py-4 last:border-0 sm:px-6 dark:border-zinc-800">
                            <x-avatar :user="$u->user" size="sm" />
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $u->user?->username ?? 'Pengguna' }}</p>
                                    <x-rating :nilai="$u->rating" ukuran="size-3.5" />
                                </div>
                                @if ($u->review)
                                    <p class="mt-1.5 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">{{ $u->review }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <x-empty compact icon="heroicon-o-chat-bubble-left-ellipsis" title="Belum ada ulasan" />
                    @endforelse
                    @if ($ulasan->hasPages())
                        <x-slot:footer>{{ $ulasan->links() }}</x-slot:footer>
                    @endif
                </x-card>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                <div class="card p-6">
                    <p class="text-xs font-medium text-zinc-500">Biaya</p>
                    <p class="mt-1 text-xl font-bold text-zinc-900 dark:text-white">{{ $jasa->harga_label }}</p>
                    <dl class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800">
                        <x-info label="Estimasi pengerjaan" icon="heroicon-o-clock">± {{ $jasa->estimasi_hari }} hari</x-info>
                        <x-info label="Tenggat minimal" icon="heroicon-o-calendar">{{ pengaturan('jasa_tenggat_min_hari') }} hari dari pemesanan</x-info>
                        <x-info label="Revisi & progres" icon="heroicon-o-bell">Notifikasi otomatis</x-info>
                    </dl>
                    <x-button :href="$ctaPesan" size="lg" class="mt-5 w-full" icon="heroicon-m-sparkles" :disabled="! $jasa->is_active">
                        {{ $user?->isStaff() ? 'Kelola layanan' : 'Pesan sekarang' }}
                    </x-button>
                    @guest
                        <p class="mt-3 text-center text-xs text-zinc-500">Masuk atau daftar terlebih dahulu untuk memesan.</p>
                    @endguest
                </div>

                @if ($lainnya->isNotEmpty())
                    <div class="card p-5">
                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">Layanan lainnya</p>
                        <ul class="mt-3 space-y-2">
                            @foreach ($lainnya as $l)
                                <li>
                                    <a href="{{ route('layanan.show', $l) }}" class="flex items-center gap-3 rounded-xl p-2 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                        <img src="{{ $l->gambar_url }}" alt="" class="size-12 rounded-lg object-cover" loading="lazy">
                                        <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $l->nama_jenis_jasa }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>
        </div>
    </section>
</x-layouts.publik>
