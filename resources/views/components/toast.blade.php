@php
    $flash = session('toast');
    if (! $flash && session('status')) {
        $flash = ['type' => 'success', 'message' => session('status')];
    }
@endphp
<div x-data
    @if ($flash) x-init="$nextTick(() => $store.toast.tampil(@js($flash['message']), @js($flash['type'] ?? 'info'), 6000))" @endif
    class="pointer-events-none fixed inset-x-0 top-0 z-[100] flex flex-col items-center gap-2 p-3 sm:top-auto sm:bottom-0 sm:left-auto sm:items-end sm:p-6"
    aria-live="polite" role="status">
    <template x-for="t in $store.toast.items" :key="t.id">
        <div x-show="t.tampil" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-2 sm:translate-y-2 sm:translate-x-0"
            x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border bg-white/95 p-3.5 pr-2.5 shadow-lift backdrop-blur dark:bg-zinc-900/95"
            :class="{
                'border-emerald-200 dark:border-emerald-500/30': t.jenis === 'success',
                'border-rose-200 dark:border-rose-500/30': t.jenis === 'error',
                'border-amber-200 dark:border-amber-500/30': t.jenis === 'warning',
                'border-zinc-200 dark:border-zinc-700': t.jenis === 'info',
            }">
            <span class="mt-0.5 shrink-0">
                <template x-if="t.jenis === 'success'"><x-heroicon-s-check-circle class="size-5 text-emerald-500" /></template>
                <template x-if="t.jenis === 'error'"><x-heroicon-s-x-circle class="size-5 text-rose-500" /></template>
                <template x-if="t.jenis === 'warning'"><x-heroicon-s-exclamation-triangle class="size-5 text-amber-500" /></template>
                <template x-if="t.jenis === 'info'"><x-heroicon-s-information-circle class="size-5 text-brand-500" /></template>
            </span>
            <p class="flex-1 text-sm leading-snug text-zinc-700 dark:text-zinc-200" x-text="t.pesan"></p>
            <button type="button" @click="$store.toast.tutup(t.id)"
                class="rounded-md p-1 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800" aria-label="Tutup">
                <x-heroicon-m-x-mark class="size-4" />
            </button>
        </div>
    </template>
</div>
