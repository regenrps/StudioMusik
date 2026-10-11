@props(['href' => null, 'icon' => null, 'danger' => false, 'type' => 'submit'])
@php
    $kelas = 'flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm font-medium transition '
        .($danger ? 'text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800');
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($kelas) }}>
        @if ($icon)<x-dynamic-component :component="$icon" class="size-[1.125rem] opacity-70" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($kelas) }}>
        @if ($icon)<x-dynamic-component :component="$icon" class="size-[1.125rem] opacity-70" />@endif
        {{ $slot }}
    </button>
@endif
