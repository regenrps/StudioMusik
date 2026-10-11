@props(['title' => null, 'lebar' => 'max-w-7xl'])
@php
    $user = auth()->user();
    $badgeMenu = $badgeMenu ?? [];
    $notifBelumDibaca = $notifBelumDibaca ?? 0;

    if ($user->isStaff()) {
        $grupMenu = [
            null => [
                ['Dasbor', 'admin.dashboard', 'heroicon-o-squares-2x2', 'admin.dashboard', null],
            ],
            'Operasional' => array_values(array_filter([
                ['Peminjaman', 'admin.peminjaman.index', 'heroicon-o-calendar-days', ['admin.peminjaman.index', 'admin.peminjaman.show'], $badgeMenu['peminjaman'] ?? null],
                ['Kalender', 'admin.peminjaman.kalender', 'heroicon-o-calendar', 'admin.peminjaman.kalender', null],
                $user->isAdmin() ? ['Pesanan Jasa', 'admin.jasa.index', 'heroicon-o-musical-note', 'admin.jasa.*', $badgeMenu['jasa'] ?? null] : null,
                ['Laporan Masalah', 'admin.laporan.index', 'heroicon-o-exclamation-triangle', 'admin.laporan.*', $badgeMenu['laporan'] ?? null],
            ])),
        ];
        if ($user->isAdmin()) {
            $grupMenu['Data Master'] = [
                ['Ruangan', 'admin.ruangan.index', 'heroicon-o-building-office-2', 'admin.ruangan.*', null],
                ['Layanan Jasa', 'admin.layanan.index', 'heroicon-o-sparkles', 'admin.layanan.*', null],
                ['Jadwal Tutup', 'admin.jadwal-tutup.index', 'heroicon-o-no-symbol', 'admin.jadwal-tutup.*', null],
                ['Tutorial Alat', 'admin.tutorial.index', 'heroicon-o-book-open', 'admin.tutorial.*', null],
                ['Pengguna', 'admin.pengguna.index', 'heroicon-o-users', 'admin.pengguna.*', null],
            ];
            $grupMenu['Sistem'] = [
                ['Pengaturan', 'admin.pengaturan.edit', 'heroicon-o-cog-6-tooth', 'admin.pengaturan.*', null],
                ['Log Aktivitas', 'admin.log.index', 'heroicon-o-clipboard-document-list', 'admin.log.*', null],
            ];
        }
    } else {
        $grupMenu = [
            null => [
                ['Beranda', 'akun.dashboard', 'heroicon-o-home', 'akun.dashboard', null],
                ['Pinjam Studio', 'akun.peminjaman.create', 'heroicon-o-plus-circle', 'akun.peminjaman.create', null],
                ['Peminjaman Saya', 'akun.peminjaman.index', 'heroicon-o-calendar-days', ['akun.peminjaman.index', 'akun.peminjaman.show'], null],
                ['Jasa Musik', 'akun.jasa.index', 'heroicon-o-musical-note', 'akun.jasa.*', null],
            ],
            'Informasi' => [
                ['Jadwal Studio', 'jadwal', 'heroicon-o-calendar', 'jadwal', null],
                ['Tutorial Alat', 'tutorial.index', 'heroicon-o-book-open', 'tutorial.*', null],
            ],
        ];
    }

    $navBawah = [
        ['Beranda', 'akun.dashboard', 'heroicon-o-home', 'heroicon-s-home', 'akun.dashboard'],
        ['Peminjaman', 'akun.peminjaman.index', 'heroicon-o-calendar-days', 'heroicon-s-calendar-days', ['akun.peminjaman.index', 'akun.peminjaman.show']],
        ['Pinjam', 'akun.peminjaman.create', 'heroicon-o-plus', 'heroicon-s-plus', 'akun.peminjaman.create'],
        ['Jasa', 'akun.jasa.index', 'heroicon-o-musical-note', 'heroicon-s-musical-note', 'akun.jasa.*'],
        ['Profil', 'profil.edit', 'heroicon-o-user-circle', 'heroicon-s-user-circle', 'profil.*'],
    ];
@endphp
<x-layouts.base :title="$title">
    <div x-data="{ sidebar: false }" class="min-h-full">
        {{-- Latar sidebar (ponsel) --}}
        <div x-show="sidebar" x-cloak x-transition.opacity @click="sidebar = false" class="fixed inset-0 z-40 bg-zinc-950/50 backdrop-blur-[2px] lg:hidden"></div>

        {{-- Sidebar --}}
        <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-zinc-200 bg-white transition-transform duration-300 ease-out dark:border-zinc-800 dark:bg-zinc-950 lg:w-64">
            <div class="flex h-16 shrink-0 items-center justify-between px-5">
                <x-logo :href="$user->homeRoute()" />
                <button type="button" @click="sidebar = false" class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 lg:hidden dark:hover:bg-zinc-800" aria-label="Tutup menu">
                    <x-heroicon-o-x-mark class="size-5" />
                </button>
            </div>

            @if ($user->isStaff())
                <div class="mx-4 mb-2 rounded-xl bg-zinc-100 px-3 py-2 text-xs font-semibold text-zinc-600 dark:bg-zinc-900 dark:text-zinc-400">
                    Panel {{ $user->user_role->label() }}
                </div>
            @endif

            <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-3 scrollbar-tipis" aria-label="Navigasi aplikasi">
                @foreach ($grupMenu as $grup => $items)
                    <div>
                        @if ($grup)
                            <p class="mb-2 px-3 text-[11px] font-bold tracking-wider text-zinc-400 uppercase dark:text-zinc-500">{{ $grup }}</p>
                        @endif
                        <div class="space-y-0.5">
                            @foreach ($items as [$label, $route, $icon, $pola, $badge])
                                <x-nav-item :href="route($route)" :icon="$icon" :aktif="request()->routeIs(...(array) $pola)" :badge="$badge">{{ $label }}</x-nav-item>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>

            @unless ($user->isStaff())
                <div class="m-4 overflow-hidden rounded-2xl bg-gradient-to-br from-brand-600 to-brand-800 p-4 text-white">
                    <p class="text-sm font-semibold">Butuh bantuan?</p>
                    <p class="mt-1 text-xs text-white/75">Hubungi pengelola studio lewat WhatsApp.</p>
                    <a href="{{ wa_link(config('studio.kontak.whatsapp'), 'Halo admin Studio Musik ITERA, saya ingin bertanya.') }}" target="_blank" rel="noopener"
                        class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-white/15 px-3 py-1.5 text-xs font-semibold hover:bg-white/25">
                        <x-heroicon-m-chat-bubble-left-right class="size-4" /> Chat admin
                    </a>
                </div>
            @endunless

            <div class="border-t border-zinc-200 p-3 dark:border-zinc-800">
                <a href="{{ route('profil.edit') }}" class="flex items-center gap-3 rounded-xl p-2 hover:bg-zinc-100 dark:hover:bg-zinc-900">
                    <x-avatar :user="$user" size="sm" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $user->username }}</p>
                        <p class="truncate text-xs text-zinc-500">{{ $user->email }}</p>
                    </div>
                </a>
            </div>
        </aside>

        <div class="lg:pl-64">
            {{-- Topbar --}}
            <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-zinc-200/80 bg-white/80 px-4 backdrop-blur-xl sm:px-6 lg:px-8 dark:border-zinc-800/80 dark:bg-zinc-950/80">
                <button type="button" @click="sidebar = true" class="-ml-1 rounded-lg p-2 text-zinc-500 hover:bg-zinc-100 lg:hidden dark:hover:bg-zinc-800" aria-label="Buka menu">
                    <x-heroicon-o-bars-3 class="size-6" />
                </button>
                <x-logo compact class="lg:hidden" :href="$user->homeRoute()" />

                <div class="flex-1"></div>

                <x-button :href="route('beranda')" variant="ghost" size="sm" icon="heroicon-o-globe-alt" class="hidden md:inline-flex">Situs publik</x-button>
                <x-theme-toggle />

                {{-- Notifikasi --}}
                <div x-data="notifikasi({ url: @js(route('notifikasi.terbaru')), urlBacaSemua: @js(route('notifikasi.baca-semua')), belum: {{ $notifBelumDibaca }} })"
                    @click.outside="buka = false" @keydown.escape.window="buka = false" class="relative">
                    <button type="button" @click="alihkan()" class="relative grid size-9 place-items-center rounded-xl text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white"
                        aria-label="Notifikasi" :aria-expanded="buka">
                        <x-heroicon-o-bell class="size-5" />
                        <span x-show="belum > 0" x-cloak x-text="belum > 9 ? '9+' : belum"
                            class="absolute -top-0.5 -right-0.5 grid h-[18px] min-w-[18px] place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white ring-2 ring-white dark:ring-zinc-950"></span>
                    </button>
                    <div x-show="buka" x-cloak x-transition.origin.top.right
                        class="fixed inset-x-3 top-16 z-50 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-lift sm:absolute sm:inset-x-auto sm:top-auto sm:right-0 sm:mt-2 sm:w-96 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">Notifikasi</p>
                            <button type="button" x-show="belum > 0" @click="bacaSemua()" class="text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400">Tandai semua dibaca</button>
                        </div>
                        <div class="max-h-[60vh] overflow-y-auto scrollbar-tipis">
                            <template x-if="memuat">
                                <div class="space-y-3 p-4">
                                    <div class="h-12 animate-pulse rounded-lg bg-zinc-100 dark:bg-zinc-800"></div>
                                    <div class="h-12 animate-pulse rounded-lg bg-zinc-100 dark:bg-zinc-800"></div>
                                </div>
                            </template>
                            <template x-if="! memuat && items.length === 0">
                                <div class="px-6 py-10 text-center">
                                    <x-heroicon-o-bell-slash class="mx-auto size-8 text-zinc-300 dark:text-zinc-600" />
                                    <p class="mt-2 text-sm text-zinc-500">Belum ada notifikasi.</p>
                                </div>
                            </template>
                            <template x-for="n in items" :key="n.id">
                                <a :href="n.url" class="flex gap-3 border-b border-zinc-100 px-4 py-3 last:border-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/60"
                                    :class="! n.dibaca && 'bg-brand-50/50 dark:bg-brand-500/5'">
                                    <span class="mt-1.5 size-2 shrink-0 rounded-full" :class="n.dibaca ? 'bg-transparent' : 'bg-brand-500'"></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-semibold text-zinc-900 dark:text-white" x-text="n.judul"></span>
                                        <span class="mt-0.5 line-clamp-2 block text-xs text-zinc-500 dark:text-zinc-400" x-text="n.pesan"></span>
                                        <span class="mt-1 block text-[11px] text-zinc-400" x-text="n.waktu"></span>
                                    </span>
                                </a>
                            </template>
                        </div>
                        <a href="{{ route('notifikasi.index') }}" class="block border-t border-zinc-100 px-4 py-2.5 text-center text-sm font-semibold text-brand-600 hover:bg-zinc-50 dark:border-zinc-800 dark:text-brand-400 dark:hover:bg-zinc-800/60">
                            Lihat semua
                        </a>
                    </div>
                </div>

                {{-- Menu pengguna --}}
                <x-dropdown width="w-60">
                    <x-slot:trigger>
                        <button type="button" class="flex items-center gap-2 rounded-xl p-1 pr-2 hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Menu akun">
                            <x-avatar :user="$user" size="sm" />
                            <x-heroicon-m-chevron-down class="hidden size-4 text-zinc-400 sm:block" />
                        </button>
                    </x-slot:trigger>
                    <div class="border-b border-zinc-100 px-3 pt-1.5 pb-3 dark:border-zinc-800">
                        <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $user->username }}</p>
                        <p class="truncate text-xs text-zinc-500">{{ $user->email }}</p>
                        <x-badge size="sm" :tone="$user->isStaff() ? 'brand' : 'zinc'" class="mt-2">{{ $user->user_role->label() }}</x-badge>
                    </div>
                    <div class="py-1">
                        <x-dropdown-link :href="route('profil.edit')" icon="heroicon-o-user-circle">Profil & keamanan</x-dropdown-link>
                        <x-dropdown-link :href="route('notifikasi.index')" icon="heroicon-o-bell">Notifikasi</x-dropdown-link>
                        <x-dropdown-link :href="route('beranda')" icon="heroicon-o-globe-alt">Situs publik</x-dropdown-link>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="border-t border-zinc-100 pt-1 dark:border-zinc-800">
                        @csrf
                        <x-dropdown-link icon="heroicon-o-arrow-right-on-rectangle" danger>Keluar</x-dropdown-link>
                    </form>
                </x-dropdown>
            </header>

            <main id="konten" @class(['mx-auto w-full px-4 py-6 sm:px-6 sm:py-8 lg:px-8', $lebar, 'pb-28 lg:pb-8' => ! $user->isStaff()])>
                {{ $slot }}
            </main>
        </div>

        {{-- Navigasi bawah (ponsel, peminjam) --}}
        @unless ($user->isStaff())
            <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white/90 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl lg:hidden dark:border-zinc-800 dark:bg-zinc-950/90" aria-label="Navigasi bawah">
                <div class="grid grid-cols-5">
                    @foreach ($navBawah as [$label, $route, $ikon, $ikonAktif, $pola])
                        @php $aktif = request()->routeIs(...(array) $pola); @endphp
                        @if ($route === 'akun.peminjaman.create')
                            <a href="{{ route($route) }}" class="flex flex-col items-center justify-center py-1.5" aria-label="Pinjam studio">
                                <span class="-mt-5 grid size-12 place-items-center rounded-2xl bg-brand-600 text-white shadow-lg shadow-brand-600/40 ring-4 ring-white dark:ring-zinc-950">
                                    <x-heroicon-m-plus class="size-6" />
                                </span>
                                <span class="mt-0.5 text-[11px] font-semibold text-brand-600 dark:text-brand-400">{{ $label }}</span>
                            </a>
                        @else
                            <a href="{{ route($route) }}" @class(['flex flex-col items-center justify-center gap-0.5 py-2.5 text-[11px] font-semibold', 'text-brand-600 dark:text-brand-400' => $aktif, 'text-zinc-500 dark:text-zinc-400' => ! $aktif])>
                                <x-dynamic-component :component="$aktif ? $ikonAktif : $ikon" class="size-6" />
                                {{ $label }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </nav>
        @endunless
    </div>
</x-layouts.base>
