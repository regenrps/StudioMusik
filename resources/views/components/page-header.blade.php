@props(['title', 'description' => null, 'kembali' => null, 'kembaliLabel' => 'Kembali'])
<div {{ $attributes->class('mb-6 flex flex-col gap-4 sm:mb-8 sm:flex-row sm:items-end sm:justify-between') }}>
    <div class="min-w-0">
        @if ($kembali)
            <a href="{{ $kembali }}" class="mb-2 inline-flex items-center gap-1 text-sm font-medium text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white">
                <x-heroicon-m-chevron-left class="size-4" /> {{ $kembaliLabel }}
            </a>
        @endif
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-[1.75rem]">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1.5 max-w-2xl text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
        @endif
        {{ $meta ?? '' }}
    </div>
    @if (! $slot->isEmpty())
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
