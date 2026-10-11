@props(['icon' => 'heroicon-o-inbox', 'title' => 'Belum ada data', 'description' => null, 'compact' => false])
<div {{ $attributes->class(['flex flex-col items-center text-center', 'px-6 py-12' => ! $compact, 'px-4 py-8' => $compact]) }}>
    <span class="relative grid size-14 place-items-center rounded-2xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500">
        <x-dynamic-component :component="$icon" class="size-7" />
    </span>
    <h3 class="mt-4 text-sm font-semibold text-zinc-900 dark:text-white">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-5 flex flex-wrap justify-center gap-2">{{ $slot }}</div>
    @endif
</div>
