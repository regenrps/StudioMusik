@props(['title' => null, 'description' => null, 'transparan' => false])
@php
    $menu = [
        ['label' => 'Beranda', 'route' => 'beranda', 'aktif' => request()->routeIs('beranda')],
        ['label' => 'Jadwal Studio', 'route' => 'jadwal', 'aktif' => request()->routeIs('jadwal')],
        ['label' => 'Jasa Musik', 'route' => 'layanan.index', 'aktif' => request()->routeIs('layanan.*')],
        ['label' => 'Tutorial Alat', 'route' => 'tutorial.index', 'aktif' => request()->routeIs('tutorial.*')],
    ];
    $user = auth()->user();
    $ctaPinjam = $user && ! $user->isStaff() ? route('akun.peminjaman.create') : ($user ? route('admin.dashboard') : route('login'));
    $kontak = config('studio.kontak');
@endphp
<x-layouts.base :title="$title" :description="$description">
    <div class="flex min-h-full flex-col">
        <header x-data="{ buka: false, gulir: false }" @scroll.window="gulir = window.scrollY > 8"
            :class="gulir || buka ? 'bg-white/85 shadow-sm ring-1 ring-zinc-900/5 dark:bg-zinc-950/85 dark:ring-white/5' : '{{ $transparan ? 'bg-transparent' : 'bg-white/60 dark:bg-zinc-950/60' }}'"
            class="sticky top-0 z-50 backdrop-blur-xl transition-colors">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <x-logo />

                <nav class="hidden items-center gap-1 lg:flex" aria-label="Menu utama">
                    @foreach ($menu as $item)
                        <a href="{{ route($item['route']) }}" @class([
                            'rounded-lg px-3.5 py-2 text-sm font-semibold transition',
                            'text-brand-700 dark:text-white' => $item['aktif'],
                            'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' => ! $item['aktif'],
                        ])>{{ $item['label'] }}</a>
                    @endforeach
                </nav>

                <div class="flex items-center gap-2">
                    <x-theme-toggle />
                    @auth
                        <x-button :href="route('dashboard')" variant="secondary" size="sm" class="hidden sm:inline-flex" icon="heroicon-o-squares-2x2">Dasbor</x-button>
                    @else
                        <x-button :href="route('login')" variant="ghost" size="sm" class="hidden sm:inline-flex">Masuk</x-button>
                    @endauth
                    <x-button :href="$ctaPinjam" size="sm" class="hidden sm:inline-flex" icon="heroicon-m-calendar-days">Pinjam Studio</x-button>
                    <button type="button" @click="buka = ! buka" class="grid size-9 place-items-center rounded-xl text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800 lg:hidden"
                        :aria-expanded="buka" aria-label="Buka menu">
                        <x-heroicon-o-bars-3 class="size-6" x-show="! buka" />
                        <x-heroicon-o-x-mark class="size-6" x-show="buka" x-cloak />
                    </button>
                </div>
            </div>

            <div x-show="buka" x-collapse x-cloak class="border-t border-zinc-200/70 lg:hidden dark:border-zinc-800">
                <nav class="space-y-1 px-4 py-4">
                    @foreach ($menu as $item)
                        <a href="{{ route($item['route']) }}" @class([
                            'block rounded-xl px-4 py-3 text-base font-semibold',
                            'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-white' => $item['aktif'],
                            'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' => ! $item['aktif'],
                        ])>{{ $item['label'] }}</a>
                    @endforeach
                    <div class="grid grid-cols-2 gap-2 pt-3">
                        @auth
                            <x-button :href="route('dashboard')" variant="secondary">Dasbor</x-button>
                        @else
                            <x-button :href="route('login')" variant="secondary">Masuk</x-button>
                        @endauth
                        <x-button :href="$ctaPinjam">Pinjam Studio</x-button>
                    </div>
                </nav>
            </div>
        </header>

        <main id="konten" class="flex-1">
            {{ $slot }}
        </main>

        <footer class="border-t border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
            <div class="mx-auto grid grid-cols-1 max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-12 lg:px-8">
                <div class="md:col-span-5">
                    <x-logo />
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">
                        Fasilitas studio musik full band untuk civitas akademika ITERA. Latihan, rekaman, dan kolaborasi — kini cukup pesan secara online.
                    </p>
                    <p class="mt-4 flex items-start gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                        <x-heroicon-o-map-pin class="mt-0.5 size-4 shrink-0 text-zinc-400" />
                        <a href="{{ config('studio.maps_url') }}" target="_blank" rel="noopener" class="hover:text-brand-600">{{ config('studio.lokasi') }}</a>
                    </p>
                </div>
                <div class="md:col-span-3">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Jelajahi</h3>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        @foreach ($menu as $item)
                            <li><a href="{{ route($item['route']) }}" class="text-zinc-500 hover:text-brand-600 dark:text-zinc-400 dark:hover:text-white">{{ $item['label'] }}</a></li>
                        @endforeach
                        <li><a href="{{ route('beranda') }}#sop" class="text-zinc-500 hover:text-brand-600 dark:text-zinc-400 dark:hover:text-white">SOP Peminjaman</a></li>
                    </ul>
                </div>
                <div class="md:col-span-4">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Hubungi kami</h3>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li>
                            <a href="{{ wa_link($kontak['whatsapp']) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2.5 text-zinc-600 hover:text-emerald-600 dark:text-zinc-300">
                                <span class="grid size-8 place-items-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10"><x-heroicon-o-chat-bubble-left-right class="size-4" /></span>
                                WhatsApp {{ '0'.substr($kontak['whatsapp'], 2) }}
                            </a>
                        </li>
                        <li>
                            <a href="mailto:{{ $kontak['email'] }}" class="inline-flex items-center gap-2.5 text-zinc-600 hover:text-brand-600 dark:text-zinc-300">
                                <span class="grid size-8 place-items-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/10"><x-heroicon-o-envelope class="size-4" /></span>
                                {{ $kontak['email'] }}
                            </a>
                        </li>
                        @if ($kontak['instagram'])
                            <li>
                                <a href="https://instagram.com/{{ $kontak['instagram'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2.5 text-zinc-600 hover:text-rose-600 dark:text-zinc-300">
                                    <span class="grid size-8 place-items-center rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-500/10"><x-heroicon-o-camera class="size-4" /></span>
                                    {{ '@'.$kontak['instagram'] }}
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
            <div class="border-t border-zinc-100 dark:border-zinc-900">
                <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-5 text-xs text-zinc-500 sm:flex-row sm:px-6 lg:px-8">
                    <p>&copy; {{ date('Y') }} {{ config('studio.nama') }} · UKMBSM ITERA</p>
                    <p>Dikembangkan oleh <a href="{{ config('studio.pengembang.url') }}" target="_blank" rel="noopener" class="font-medium hover:text-brand-600">{{ config('studio.pengembang.nama') }}</a></p>
                </div>
            </div>
        </footer>
    </div>
</x-layouts.base>
