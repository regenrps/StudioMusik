@props(['label', 'value', 'icon' => null, 'tone' => 'brand', 'hint' => null, 'href' => null])
@php
    $warna = [
        'brand' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
        'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
        'sky' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300',
        'rose' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300',
        'violet' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300',
    ][$tone] ?? '';
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class(['card group flex items-start gap-4 p-5', 'transition hover:-translate-y-0.5 hover:shadow-lift' => $href]) }}>
    @if ($icon)
        <span class="grid size-11 shrink-0 place-items-center rounded-xl {{ $warna }}">
            <x-dynamic-component :component="$icon" class="size-[1.375rem]" />
        </span>
    @endif
    <div class="min-w-0 flex-1">
        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
        <p class="mt-1 text-2xl font-bold tracking-tight text-zinc-900 tabular-nums dark:text-white">{{ $value }}</p>
        @if ($hint)
            <p class="mt-1 truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
        @endif
    </div>
    @if ($href)
        <x-heroicon-m-arrow-up-right class="size-4 text-zinc-300 transition group-hover:text-brand-500 dark:text-zinc-600" />
    @endif
</{{ $tag }}>
