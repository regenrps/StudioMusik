@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'icon' => null,
    'iconRight' => null,
    'type' => 'button',
])
@php
    $base = 'inline-flex items-center justify-center gap-2 font-semibold whitespace-nowrap transition duration-150 select-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 disabled:pointer-events-none disabled:opacity-50';

    $ukuran = [
        'xs' => 'h-7 rounded-lg px-2.5 text-xs',
        'sm' => 'h-9 rounded-lg px-3 text-sm',
        'md' => 'h-10 rounded-xl px-4 text-sm',
        'lg' => 'h-12 rounded-xl px-6 text-base',
        'icon' => 'size-10 rounded-xl',
        'icon-sm' => 'size-8 rounded-lg',
    ][$size] ?? 'h-10 rounded-xl px-4 text-sm';

    $varian = [
        'primary' => 'bg-brand-600 text-white shadow-sm shadow-brand-600/20 hover:bg-brand-700 active:bg-brand-800 dark:bg-brand-500 dark:hover:bg-brand-400 dark:active:bg-brand-600',
        'secondary' => 'border border-zinc-300 bg-white text-zinc-700 shadow-xs hover:bg-zinc-50 active:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800 [--spinner-color:var(--color-zinc-500)]',
        'ghost' => 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white [--spinner-color:var(--color-zinc-500)]',
        'soft' => 'bg-brand-50 text-brand-700 hover:bg-brand-100 dark:bg-brand-500/10 dark:text-brand-300 dark:hover:bg-brand-500/20 [--spinner-color:var(--color-brand-500)]',
        'danger' => 'bg-rose-600 text-white shadow-sm shadow-rose-600/20 hover:bg-rose-700 active:bg-rose-800',
        'danger-soft' => 'bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/20 [--spinner-color:var(--color-rose-500)]',
        'success' => 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/20 hover:bg-emerald-700 active:bg-emerald-800',
        'white' => 'bg-white text-zinc-900 shadow-sm hover:bg-zinc-100 [--spinner-color:var(--color-zinc-500)]',
    ][$variant] ?? '';

    $ikon = in_array($size, ['xs', 'sm', 'icon-sm'], true) ? 'size-4' : 'size-[1.125rem]';
    // `hidden` dari pemanggil harus menang atas display bawaan (inline-flex),
    // mis. class="hidden sm:inline-flex" untuk menyembunyikan di ponsel.
    if (in_array('hidden', preg_split('/\s+/', (string) $attributes->get('class')), true)) {
        $base = str_replace('inline-flex ', '', $base);
    }
    $kelas = $base.' '.$ukuran.' '.$varian;
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($kelas) }}>
        @if ($icon)<x-dynamic-component :component="$icon" class="{{ $ikon }} shrink-0" aria-hidden="true" />@endif
        @if (! $slot->isEmpty())<span>{{ $slot }}</span>@endif
        @if ($iconRight)<x-dynamic-component :component="$iconRight" class="{{ $ikon }} shrink-0" aria-hidden="true" />@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($kelas) }}>
        @if ($icon)<x-dynamic-component :component="$icon" class="{{ $ikon }} shrink-0" aria-hidden="true" />@endif
        @if (! $slot->isEmpty())<span>{{ $slot }}</span>@endif
        @if ($iconRight)<x-dynamic-component :component="$iconRight" class="{{ $ikon }} shrink-0" aria-hidden="true" />@endif
    </button>
@endif
