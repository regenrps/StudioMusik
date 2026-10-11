@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => null, 'hint' => null, 'bag' => 'default', 'inputClass' => ''])
@php
    $kunci = str_replace(['[]', '[', ']'], ['', '.', ''], $name);
    $id = $attributes->get('id', 'f-'.str_replace('.', '-', $kunci));
    $galat = $errors->getBag($bag)->first($kunci);
    $terpilih = (string) old($kunci, $value instanceof \BackedEnum ? $value->value : $value);
    $wajib = (bool) $attributes->get('required');
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">
            {{ $label }}@if ($wajib)<span class="text-rose-500" aria-hidden="true"> *</span>@endif
        </label>
    @endif
    <select id="{{ $id }}" name="{{ $name }}" @if ($galat) aria-invalid="true" @endif
        {{ $attributes->except(['class', 'id'])->class(['input pr-10', 'input-error' => $galat, $inputClass]) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $nilai => $teks)
            <option value="{{ $nilai }}" @selected($terpilih === (string) $nilai)>{{ $teks }}</option>
        @endforeach
        {{ $slot }}
    </select>
    @if ($galat)
        <p class="mt-1.5 flex items-start gap-1 text-xs font-medium text-rose-600 dark:text-rose-400">
            <x-heroicon-m-exclamation-circle class="mt-px size-3.5 shrink-0" />{{ $galat }}
        </p>
    @elseif ($hint)
        <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
    @endif
</div>
