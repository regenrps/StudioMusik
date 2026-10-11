@props(['name', 'label' => null, 'checked' => false, 'value' => '1', 'hint' => null, 'bag' => 'default'])
@php
    $kunci = str_replace(['[]', '[', ']'], ['', '.', ''], $name);
    $id = $attributes->get('id', 'f-'.str_replace('.', '-', $kunci).'-'.md5((string) $value));
    $galat = $errors->getBag($bag)->first($kunci);
    $tercentang = old() ? (bool) old($kunci) : $checked;
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="group flex cursor-pointer items-start gap-3">
        <input id="{{ $id }}" name="{{ $name }}" type="checkbox" value="{{ $value }}" @checked($tercentang)
            {{ $attributes->except(['class', 'id'])->class(['checkbox mt-0.5', 'border-rose-400' => $galat]) }}>
        <span class="text-sm text-zinc-700 dark:text-zinc-300">
            {{ $label ?? $slot }}
            @if ($hint)
                <span class="mt-0.5 block text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</span>
            @endif
        </span>
    </label>
    @if ($galat)
        <p class="mt-1.5 ml-7 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $galat }}</p>
    @endif
</div>
