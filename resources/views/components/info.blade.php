@props(['label', 'icon' => null])
<div {{ $attributes->class('flex items-start gap-3 py-3') }}>
    @if ($icon)
        <x-dynamic-component :component="$icon" class="mt-0.5 size-5 shrink-0 text-zinc-400" />
    @endif
    <div class="min-w-0 flex-1 sm:flex sm:items-start sm:justify-between sm:gap-4">
        <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
        <dd class="mt-0.5 text-sm font-medium break-words text-zinc-900 sm:mt-0 sm:max-w-[65%] sm:text-right dark:text-zinc-100">{{ $slot }}</dd>
    </div>
</div>
