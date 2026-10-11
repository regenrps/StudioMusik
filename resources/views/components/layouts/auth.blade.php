@props(['title' => null, 'heading' => null, 'subheading' => null])
<x-layouts.base :title="$title">
    <div class="flex min-h-full">
        {{-- Panel visual (desktop) --}}
        <div class="relative hidden w-[46%] overflow-hidden lg:block">
            <img src="{{ asset('assets/img/ruang-studio.jpg') }}" alt="" class="absolute inset-0 size-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-br from-brand-950/90 via-brand-900/70 to-zinc-950/80"></div>
            <div class="relative flex h-full flex-col justify-between p-12 text-white">
                <x-logo inverse />
                <div>
                    <p class="text-sm font-semibold tracking-[0.2em] text-brand-200 uppercase">Studio Musik ITERA</p>
                    <h2 class="mt-3 max-w-md text-4xl leading-tight font-bold tracking-tight">Ruang untuk berlatih, merekam, dan berkarya.</h2>
                    <ul class="mt-8 space-y-3 text-sm text-white/80">
                        <li class="flex items-center gap-3"><x-heroicon-s-check-circle class="size-5 text-emerald-300" /> Lihat slot kosong secara real-time</li>
                        <li class="flex items-center gap-3"><x-heroicon-s-check-circle class="size-5 text-emerald-300" /> Ajukan peminjaman kurang dari 2 menit</li>
                        <li class="flex items-center gap-3"><x-heroicon-s-check-circle class="size-5 text-emerald-300" /> Notifikasi otomatis via email & dasbor</li>
                    </ul>
                </div>
                <p class="text-xs text-white/50">&copy; {{ date('Y') }} {{ config('studio.nama') }}</p>
            </div>
        </div>

        {{-- Formulir --}}
        <div class="flex flex-1 flex-col">
            <div class="flex items-center justify-between px-4 py-4 sm:px-8">
                <x-logo class="lg:invisible" />
                <div class="flex items-center gap-1">
                    <x-theme-toggle />
                    <x-button :href="route('beranda')" variant="ghost" size="sm" icon="heroicon-m-arrow-left">Beranda</x-button>
                </div>
            </div>
            <main id="konten" class="flex flex-1 items-center justify-center px-4 pb-12 sm:px-8">
                <div class="w-full max-w-md animate-slide-up">
                    @if ($heading)
                        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">{{ $heading }}</h1>
                    @endif
                    @if ($subheading)
                        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $subheading }}</p>
                    @endif
                    <div class="mt-8">
                        {{ $slot }}
                    </div>
                </div>
            </main>
        </div>
    </div>
</x-layouts.base>
