@props(['href' => null, 'compact' => false, 'inverse' => false])
<a href="{{ $href ?? route('beranda') }}" {{ $attributes->class('group inline-flex items-center gap-2.5') }}>
    <span class="relative grid size-9 shrink-0 place-items-center overflow-hidden rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-md shadow-brand-600/30">
        <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true">
            <path d="M9 18V6l11-2v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="6" cy="18" r="3" fill="currentColor"/>
            <circle cx="17" cy="16" r="3" fill="currentColor"/>
        </svg>
    </span>
    @unless ($compact)
        <span class="leading-tight">
            <span @class(['block text-[0.9375rem] font-bold tracking-tight', 'text-white' => $inverse, 'text-zinc-900 dark:text-white' => ! $inverse])>Studio Musik</span>
            <span @class(['block text-[11px] font-semibold tracking-[0.18em] uppercase', 'text-white/70' => $inverse, 'text-brand-600 dark:text-brand-400' => ! $inverse])>ITERA</span>
        </span>
    @endunless
</a>
