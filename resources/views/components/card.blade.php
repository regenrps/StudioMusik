@props(['title' => null, 'description' => null, 'padding' => true, 'icon' => null])
<section {{ $attributes->class(['card']) }}>
    @if ($title || isset($actions))
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-zinc-100 px-5 py-4 dark:border-zinc-800 sm:px-6">
            <div class="flex min-w-0 items-start gap-3">
                @if ($icon)
                    <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                        <x-dynamic-component :component="$icon" class="size-[1.125rem]" />
                    </span>
                @endif
                <div class="min-w-0">
                    @if ($title)
                        <h2 class="text-base font-semibold text-zinc-900 dark:text-white">{{ $title }}</h2>
                    @endif
                    @if ($description)
                        <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
                    @endif
                </div>
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div @class(['px-5 py-5 sm:px-6' => $padding])>
        {{ $slot }}
    </div>

    @isset($footer)
        <footer class="rounded-b-2xl border-t border-zinc-100 bg-zinc-50/60 px-5 py-3.5 dark:border-zinc-800 dark:bg-zinc-900/60 sm:px-6">
            {{ $footer }}
        </footer>
    @endisset
</section>
