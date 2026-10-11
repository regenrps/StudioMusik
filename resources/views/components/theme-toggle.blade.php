<button type="button" x-data @click="$store.tema.alihkan()"
    {{ $attributes->class('grid size-9 place-items-center rounded-xl text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-white') }}
    :aria-label="$store.tema.gelap ? 'Gunakan tema terang' : 'Gunakan tema gelap'" title="Ganti tema">
    <x-heroicon-o-moon class="size-5 dark:hidden" />
    <x-heroicon-o-sun class="hidden size-5 dark:block" />
</button>
