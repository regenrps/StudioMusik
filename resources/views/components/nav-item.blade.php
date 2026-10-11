@props(['href', 'icon', 'aktif' => false, 'badge' => null])
<a href="{{ $href }}" @if ($aktif) aria-current="page" @endif
    {{ $attributes->class([
        'group relative flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition',
        'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-white' => $aktif,
        'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/70 dark:hover:text-white' => ! $aktif,
    ]) }}>
    @if ($aktif)
        <span class="absolute inset-y-2 -left-3 w-1 rounded-r-full bg-brand-600 dark:bg-brand-400" aria-hidden="true"></span>
    @endif
    <x-dynamic-component :component="$icon" @class([
        'size-5 shrink-0',
        'text-brand-600 dark:text-brand-400' => $aktif,
        'text-zinc-400 group-hover:text-zinc-600 dark:text-zinc-500 dark:group-hover:text-zinc-300' => ! $aktif,
    ]) />
    <span class="flex-1 truncate">{{ $slot }}</span>
    @if ($badge)
        <span class="rounded-full bg-amber-500 px-1.5 py-px text-[11px] font-bold text-white tabular-nums">{{ $badge > 99 ? '99+' : $badge }}</span>
    @endif
</a>
