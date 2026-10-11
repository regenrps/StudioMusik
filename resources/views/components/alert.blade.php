@props(['tone' => 'info', 'title' => null, 'icon' => null])
@php
    $gaya = [
        'info' => ['bg-sky-50 text-sky-900 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-100 dark:ring-sky-500/20', 'text-sky-500', 'heroicon-s-information-circle'],
        'success' => ['bg-emerald-50 text-emerald-900 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-100 dark:ring-emerald-500/20', 'text-emerald-500', 'heroicon-s-check-circle'],
        'warning' => ['bg-amber-50 text-amber-900 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-100 dark:ring-amber-500/20', 'text-amber-500', 'heroicon-s-exclamation-triangle'],
        'danger' => ['bg-rose-50 text-rose-900 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-100 dark:ring-rose-500/20', 'text-rose-500', 'heroicon-s-x-circle'],
        'brand' => ['bg-brand-50 text-brand-900 ring-brand-200 dark:bg-brand-500/10 dark:text-brand-100 dark:ring-brand-500/20', 'text-brand-500', 'heroicon-s-sparkles'],
    ][$tone] ?? ['', '', 'heroicon-s-information-circle'];
@endphp
<div {{ $attributes->class(['flex gap-3 rounded-xl p-4 text-sm ring-1 ring-inset', $gaya[0]]) }} role="alert">
    <x-dynamic-component :component="$icon ?? $gaya[2]" class="mt-px size-5 shrink-0 {{ $gaya[1] }}" />
    <div class="min-w-0 flex-1 leading-relaxed">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div @class(['mt-1 opacity-90' => $title])>{{ $slot }}</div>
    </div>
</div>
