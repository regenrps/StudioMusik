@props(['kode', 'judul', 'pesan', 'ikon' => 'heroicon-o-exclamation-triangle'])
<x-layouts.base :title="$judul">
    <main id="konten" class="relative flex min-h-full items-center justify-center overflow-hidden px-6 py-16">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top,var(--color-brand-100),transparent_60%)] dark:bg-[radial-gradient(ellipse_at_top,color-mix(in_oklab,var(--color-brand-900)_40%,transparent),transparent_60%)]"></div>
        <div class="w-full max-w-md text-center">
            <x-logo class="justify-center" />
            <div class="mt-10 inline-grid size-16 place-items-center rounded-2xl bg-white text-brand-600 shadow-soft ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-brand-400 dark:ring-zinc-800">
                <x-dynamic-component :component="$ikon" class="size-8" />
            </div>
            <p class="mt-6 text-sm font-bold tracking-widest text-brand-600 dark:text-brand-400">ERROR {{ $kode }}</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $judul }}</h1>
            <p class="mt-3 text-zinc-500 dark:text-zinc-400">{{ $pesan }}</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <x-button href="javascript:history.back()" variant="secondary" icon="heroicon-m-arrow-left">Kembali</x-button>
                <x-button :href="auth()->check() ? route('dashboard') : route('beranda')" icon="heroicon-m-home">
                    {{ auth()->check() ? 'Ke dasbor' : 'Ke beranda' }}
                </x-button>
            </div>
            {{ $slot }}
        </div>
    </main>
</x-layouts.base>
