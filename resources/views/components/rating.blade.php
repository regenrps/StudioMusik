@props(['nilai' => 0, 'ukuran' => 'size-4'])
<span {{ $attributes->class('inline-flex items-center gap-0.5') }} aria-label="{{ $nilai }} dari 5 bintang" role="img">
    @for ($i = 1; $i <= 5; $i++)
        <x-heroicon-s-star @class([$ukuran, 'text-amber-400' => $i <= round($nilai), 'text-zinc-200 dark:text-zinc-700' => $i > round($nilai)]) />
    @endfor
</span>
