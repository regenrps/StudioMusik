@props(['name', 'title' => null, 'description' => null, 'maxWidth' => 'lg', 'show' => false, 'icon' => null, 'tone' => 'brand'])
@php
    $lebar = ['sm' => 'sm:max-w-sm', 'md' => 'sm:max-w-md', 'lg' => 'sm:max-w-lg', 'xl' => 'sm:max-w-xl', '2xl' => 'sm:max-w-2xl', '3xl' => 'sm:max-w-3xl'][$maxWidth] ?? 'sm:max-w-lg';
    $warnaIkon = [
        'brand' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300',
        'rose' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300',
        'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
        'violet' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300',
    ][$tone] ?? '';
@endphp
<div x-data="{ show: @js((bool) $show) }"
    x-on:buka-modal.window="if ($event.detail === @js($name)) show = true"
    x-on:tutup-modal.window="if (! $event.detail || $event.detail === @js($name)) show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show" x-cloak
    class="fixed inset-0 z-[90]" role="dialog" aria-modal="true" @if ($title) aria-label="{{ $title }}" @endif>

    <div x-show="show" x-transition.opacity.duration.200ms class="fixed inset-0 bg-zinc-950/60 backdrop-blur-[2px]" @click="show = false"></div>

    <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center sm:items-center sm:p-6">
            <div x-show="show" x-trap.inert.noscroll="show"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-2 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
                {{ $attributes->class(['relative w-full rounded-t-2xl bg-white shadow-2xl ring-1 ring-zinc-900/5 dark:bg-zinc-900 dark:ring-white/10 sm:rounded-2xl', $lebar]) }}>

                <div class="mx-auto mt-2.5 h-1 w-10 rounded-full bg-zinc-300 dark:bg-zinc-700 sm:hidden"></div>

                @if ($title)
                    <div class="flex items-start gap-3.5 px-5 pt-5 sm:px-6 sm:pt-6">
                        @if ($icon)
                            <span class="grid size-10 shrink-0 place-items-center rounded-full {{ $warnaIkon }}">
                                <x-dynamic-component :component="$icon" class="size-5" />
                            </span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-white">{{ $title }}</h3>
                            @if ($description)
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
                            @endif
                        </div>
                        <button type="button" @click="show = false" class="-mt-1 -mr-1 rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800" aria-label="Tutup">
                            <x-heroicon-o-x-mark class="size-5" />
                        </button>
                    </div>
                @endif

                <div class="px-5 pt-4 pb-5 sm:px-6 sm:pb-6">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</div>
