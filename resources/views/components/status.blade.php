@props(['status', 'pendek' => false, 'size' => 'md'])
<x-badge :tone="$status->tone()" :icon="$status->icon()" :size="$size" {{ $attributes }}>
    {{ $pendek ? $status->shortLabel() : $status->label() }}
</x-badge>
