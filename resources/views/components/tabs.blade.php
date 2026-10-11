@props(['items' => []])
{{-- items: [['label' => .., 'href' => .., 'aktif' => bool, 'jumlah' => int|null]] --}}
<nav {{ $attributes->class('-mx-4 overflow-x-auto px-4 scrollbar-tipis sm:mx-0 sm:px-0') }} aria-label="Tab">
    <div class="inline-flex min-w-full gap-1 rounded-xl bg-zinc-100 p-1 dark:bg-zinc-800/70 sm:min-w-0">
        @foreach ($items as $item)
            <a href="{{ $item['href'] }}" @if ($item['aktif']) aria-current="page" @endif
                @class([
                    'inline-flex flex-1 items-center justify-center gap-2 rounded-lg px-3.5 py-2 text-sm font-semibold whitespace-nowrap transition sm:flex-none',
                    'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-white' => $item['aktif'],
                    'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' => ! $item['aktif'],
                ])>
                {{ $item['label'] }}
                @if (isset($item['jumlah']) && $item['jumlah'] !== null)
                    <span @class([
                        'rounded-full px-1.5 py-px text-[11px] tabular-nums',
                        'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300' => $item['aktif'],
                        'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300' => ! $item['aktif'],
                    ])>{{ $item['jumlah'] }}</span>
                @endif
            </a>
        @endforeach
    </div>
</nav>
