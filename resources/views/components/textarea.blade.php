@props(['name', 'label' => null, 'hint' => null, 'value' => null, 'bag' => 'default', 'rows' => 4, 'inputClass' => ''])
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
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}"
        @if ($galat) aria-invalid="true" @endif
        {{ $attributes->except(['class', 'id'])->class(['input resize-y leading-relaxed', 'input-error' => $galat, $inputClass]) }}>{{ old($kunci, $value) }}</textarea>
    @if ($galat)
        <p class="mt-1.5 flex items-start gap-1 text-xs font-medium text-rose-600 dark:text-rose-400">
            <x-heroicon-m-exclamation-circle class="mt-px size-3.5 shrink-0" />{{ $galat }}
        </p>
    @elseif ($hint)
        <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
    @endif
</div>
