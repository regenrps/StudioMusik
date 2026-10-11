@props(['name' => 'rating', 'value' => 0])
@php
    $label = [1 => 'Buruk', 2 => 'Kurang', 3 => 'Cukup', 4 => 'Bagus', 5 => 'Luar biasa'];
@endphp
<div x-data="{ nilai: {{ (int) old($name, $value) }}, hover: 0 }" {{ $attributes->class('flex flex-col items-center gap-2') }}>
    <input type="hidden" name="{{ $name }}" :value="nilai">
    <div class="flex items-center gap-1" @mouseleave="hover = 0" role="radiogroup" aria-label="Rating">
        @foreach ($label as $n => $teks)
            <button type="button" @click="nilai = {{ $n }}" @mouseenter="hover = {{ $n }}" role="radio" :aria-checked="nilai === {{ $n }}"
                aria-label="{{ $n }} bintang – {{ $teks }}" class="rounded-md p-0.5 transition-transform hover:scale-110 focus-visible:scale-110">
                <x-heroicon-s-star class="size-9 transition-colors"
                    x-bind:class="(hover || nilai) >= {{ $n }} ? 'text-amber-400' : 'text-zinc-200 dark:text-zinc-700'" />
            </button>
        @endforeach
    </div>
    <p class="h-5 text-sm font-medium text-zinc-600 dark:text-zinc-400"
        x-text="{{ json_encode(array_merge([0 => 'Pilih rating'], $label)) }}[hover || nilai]"></p>
    @error($name)
        <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>
