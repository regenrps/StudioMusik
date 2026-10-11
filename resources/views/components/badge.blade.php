@props(['tone' => 'zinc', 'icon' => null, 'size' => 'md', 'dot' => false])
@php
    $warna = [
        'zinc' => 'bg-zinc-100 text-zinc-700 ring-zinc-500/15 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-400/20',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/25',
        'sky' => 'bg-sky-50 text-sky-800 ring-sky-600/20 dark:bg-sky-400/10 dark:text-sky-300 dark:ring-sky-400/25',
        'violet' => 'bg-violet-50 text-violet-800 ring-violet-600/20 dark:bg-violet-400/10 dark:text-violet-300 dark:ring-violet-400/25',
        'emerald' => 'bg-emerald-50 text-emerald-800 ring-emerald-600/20 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/25',
        'rose' => 'bg-rose-50 text-rose-800 ring-rose-600/20 dark:bg-rose-400/10 dark:text-rose-300 dark:ring-rose-400/25',
        'orange' => 'bg-orange-50 text-orange-800 ring-orange-600/20 dark:bg-orange-400/10 dark:text-orange-300 dark:ring-orange-400/25',
        'brand' => 'bg-brand-50 text-brand-800 ring-brand-600/20 dark:bg-brand-400/10 dark:text-brand-300 dark:ring-brand-400/25',
        'indigo' => 'bg-indigo-50 text-indigo-800 ring-indigo-600/20 dark:bg-indigo-400/10 dark:text-indigo-300 dark:ring-indigo-400/25',
    ];
    $titik = [
        'zinc' => 'bg-zinc-400', 'amber' => 'bg-amber-500', 'sky' => 'bg-sky-500', 'violet' => 'bg-violet-500',
        'emerald' => 'bg-emerald-500', 'rose' => 'bg-rose-500', 'orange' => 'bg-orange-500', 'brand' => 'bg-brand-500', 'indigo' => 'bg-indigo-500',
    ];
    $ukuran = $size === 'sm' ? 'gap-1 px-2 py-0.5 text-[11px]' : 'gap-1.5 px-2.5 py-1 text-xs';
@endphp
@php $tampil = in_array('hidden', preg_split('/\s+/', (string) $attributes->get('class')), true) ? '' : 'inline-flex'; @endphp
<span {{ $attributes->class([$tampil.' items-center rounded-full font-semibold whitespace-nowrap ring-1 ring-inset', $warna[$tone] ?? $warna['zinc'], $ukuran]) }}>
    @if ($dot)
        <span class="size-1.5 shrink-0 rounded-full {{ $titik[$tone] ?? $titik['zinc'] }}"></span>
    @endif
    @if ($icon)
        <x-dynamic-component :component="$icon" class="{{ $size === 'sm' ? 'size-3' : 'size-3.5' }} -ml-0.5 shrink-0" aria-hidden="true" />
    @endif
    {{ $slot }}
</span>
