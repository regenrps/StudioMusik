@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex items-center justify-between gap-3">
        <p class="hidden text-sm text-zinc-500 sm:block dark:text-zinc-400">
            @if (method_exists($paginator, 'total'))
                Menampilkan <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
                dari <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ $paginator->total() }}</span>
            @endif
        </p>

        <div class="flex flex-1 items-center justify-between gap-1 sm:flex-none sm:justify-end">
            @php $btn = 'inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-3 text-sm font-medium transition'; @endphp

            @if ($paginator->onFirstPage())
                <span class="{{ $btn }} cursor-not-allowed text-zinc-300 dark:text-zinc-600"><x-heroicon-m-chevron-left class="size-4" /><span class="sm:sr-only">Sebelumnya</span></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $btn }} text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"><x-heroicon-m-chevron-left class="size-4" /><span class="sm:sr-only">Sebelumnya</span></a>
            @endif

            @isset($elements)
                <div class="hidden items-center gap-1 sm:flex">
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span class="{{ $btn }} text-zinc-400">{{ $element }}</span>
                        @endif
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page" class="{{ $btn }} bg-brand-600 text-white shadow-sm">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="{{ $btn }} text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                </div>
            @endisset

            <span class="text-sm text-zinc-500 sm:hidden">Hal. {{ $paginator->currentPage() }}</span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $btn }} text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"><span class="sm:sr-only">Berikutnya</span><x-heroicon-m-chevron-right class="size-4" /></a>
            @else
                <span class="{{ $btn }} cursor-not-allowed text-zinc-300 dark:text-zinc-600"><span class="sm:sr-only">Berikutnya</span><x-heroicon-m-chevron-right class="size-4" /></span>
            @endif
        </div>
    </nav>
@endif
