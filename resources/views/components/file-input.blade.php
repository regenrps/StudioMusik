@props([
    'name',
    'label' => null,
    'accept' => 'image/*',
    'maksKb' => config('studio.upload.gambar_maks_kb'),
    'hint' => null,
    'kompres' => true,
    'existing' => null,
    'existingLabel' => 'Berkas saat ini',
    'bag' => 'default',
    'ikon' => 'heroicon-o-photo',
    'judul' => 'Pilih foto atau seret ke sini',
])
@php
    $kunci = str_replace(['[]', '[', ']'], ['', '.', ''], $name);
    $id = $attributes->get('id', 'f-'.str_replace('.', '-', $kunci));
    $galat = $errors->getBag($bag)->first($kunci);
    $wajib = (bool) $attributes->get('required');
@endphp
<div {{ $attributes->only('class') }} x-data="unggahBerkas({ nama: @js($kunci), maksKb: {{ (int) $maksKb }}, kompres: @js((bool) $kompres) })">
    @if ($label)
        <label for="{{ $id }}" class="label">
            {{ $label }}@if ($wajib)<span class="text-rose-500" aria-hidden="true"> *</span>@endif
        </label>
    @endif

    <div @dragover.prevent="seret = true" @dragleave.prevent="seret = false" @drop.prevent="jatuh($event)"
        :class="seret ? 'border-brand-500 bg-brand-50/60 dark:bg-brand-500/10' : '{{ $galat ? 'border-rose-400' : 'border-zinc-300 dark:border-zinc-700' }}'"
        class="relative rounded-xl border-2 border-dashed bg-white transition-colors dark:bg-zinc-900">

        <input x-ref="input" id="{{ $id }}" name="{{ $name }}" type="file" accept="{{ $accept }}" @change="pilih($event)"
            class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0" :class="berkas && 'pointer-events-none'"
            {{ $attributes->except(['class', 'id']) }}>

        {{-- Belum ada berkas --}}
        <div x-show="!berkas && !memproses" class="flex flex-col items-center px-4 py-6 text-center">
            @if ($existing)
                <img src="{{ $existing }}" alt="{{ $existingLabel }}" class="mb-3 h-24 rounded-lg object-cover shadow-sm">
                <p class="text-xs text-zinc-500">{{ $existingLabel }} · pilih berkas baru untuk mengganti</p>
            @else
                <span class="grid size-11 place-items-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                    <x-dynamic-component :component="$ikon" class="size-5" />
                </span>
                <p class="mt-3 text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ $judul }}</p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ $hint ?? 'Maksimal '.\App\Support\Berkas::ukuranLabel($maksKb * 1024).'.' }}
                </p>
            @endif
        </div>

        {{-- Memproses (kompresi) --}}
        <div x-show="memproses" x-cloak class="flex items-center justify-center gap-2 px-4 py-8 text-sm text-zinc-500">
            <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
            Mengoptimalkan ukuran foto…
        </div>

        {{-- Sudah ada berkas --}}
        <div x-show="berkas && !memproses" x-cloak class="relative z-20 flex items-center gap-3 p-3">
            <template x-if="pratinjau">
                <img :src="pratinjau" alt="Pratinjau" class="size-16 shrink-0 rounded-lg object-cover ring-1 ring-zinc-200 dark:ring-zinc-700">
            </template>
            <template x-if="!pratinjau">
                <span class="grid size-12 shrink-0 place-items-center rounded-lg bg-zinc-100 text-zinc-500 dark:bg-zinc-800">
                    <x-heroicon-o-document class="size-6" />
                </span>
            </template>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-200" x-text="berkas?.nama"></p>
                <p class="text-xs text-zinc-500" x-text="berkas?.ukuran"></p>
            </div>
            <button type="button" @click="hapus()" class="rounded-lg p-2 text-zinc-400 hover:bg-zinc-100 hover:text-rose-600 dark:hover:bg-zinc-800" aria-label="Hapus berkas">
                <x-heroicon-o-trash class="size-5" />
            </button>
        </div>
    </div>

    <p x-show="galat" x-cloak x-text="galat" class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400"></p>
    @if ($galat)
        <p class="mt-1.5 flex items-start gap-1 text-xs font-medium text-rose-600 dark:text-rose-400">
            <x-heroicon-m-exclamation-circle class="mt-px size-3.5 shrink-0" />{{ $galat }}
        </p>
    @endif
</div>
