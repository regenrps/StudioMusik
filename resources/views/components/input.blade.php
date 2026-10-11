@props([
    'name',
    'label' => null,
    'type' => 'text',
    'hint' => null,
    'value' => null,
    'bag' => 'default',
    'inputClass' => '',
    'prefix' => null,
])
@php
    $kunci = str_replace(['[]', '[', ']'], ['', '.', ''], $name);
    $id = $attributes->get('id', 'f-'.str_replace('.', '-', $kunci));
    $galat = $errors->getBag($bag)->first($kunci);
    $wajib = (bool) $attributes->get('required');
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">
            {{ $label }}@if ($wajib)<span class="text-rose-500" aria-hidden="true"> *</span>@endif
        </label>
    @endif
    <div class="relative">
        @if ($prefix)
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm text-zinc-500">{{ $prefix }}</span>
        @endif
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
            @if ($type !== 'password' && $type !== 'file') value="{{ old($kunci, $value) }}" @endif
            @if ($galat) aria-invalid="true" aria-describedby="{{ $id }}-galat" @elseif ($hint) aria-describedby="{{ $id }}-hint" @endif
            {{ $attributes->except(['class', 'id'])->class(['input', 'input-error' => $galat, 'pl-10' => $prefix, $inputClass]) }}>
    </div>
    @if ($galat)
        <p id="{{ $id }}-galat" class="mt-1.5 flex items-start gap-1 text-xs font-medium text-rose-600 dark:text-rose-400">
            <x-heroicon-m-exclamation-circle class="mt-px size-3.5 shrink-0" />{{ $galat }}
        </p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
    @endif
</div>
