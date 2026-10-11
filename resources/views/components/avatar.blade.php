@props(['user' => null, 'nama' => null, 'size' => 'md'])
@php
    $nama = $nama ?? $user?->username ?? '?';
    $inisial = $user?->inisial ?? collect(preg_split('/\s+/', trim($nama)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $palet = ['bg-brand-500', 'bg-sky-500', 'bg-emerald-500', 'bg-amber-500', 'bg-rose-500', 'bg-violet-500', 'bg-teal-500', 'bg-orange-500'];
    $warna = $palet[crc32($nama) % count($palet)];
    $ukuran = ['xs' => 'size-6 text-[10px]', 'sm' => 'size-8 text-xs', 'md' => 'size-10 text-sm', 'lg' => 'size-14 text-lg', 'xl' => 'size-20 text-2xl'][$size] ?? 'size-10 text-sm';
@endphp
<span {{ $attributes->class(['inline-grid shrink-0 place-items-center rounded-full font-semibold text-white ring-2 ring-white dark:ring-zinc-900', $warna, $ukuran]) }} aria-hidden="true">
    {{ $inisial ?: '?' }}
</span>
