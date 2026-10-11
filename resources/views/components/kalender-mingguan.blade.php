@props(['url', 'ruangan', 'aturan', 'admin' => false])
<div x-data="jadwalMingguan({ url: @js($url), aturan: @js($aturan), ruangan: @js(request('ruangan', '')) })" {{ $attributes->class('card overflow-hidden') }}>
    {{-- Toolbar --}}
    <div class="flex flex-col gap-3 border-b border-zinc-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5 dark:border-zinc-800">
        <div class="flex items-center gap-2">
            <div class="flex items-center rounded-xl ring-1 ring-zinc-200 dark:ring-zinc-700">
                <button type="button" @click="geser(-1)" class="grid size-9 place-items-center rounded-l-xl text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-800" aria-label="Minggu sebelumnya">
                    <x-heroicon-m-chevron-left class="size-5" />
                </button>
                <button type="button" @click="mingguIni()" :disabled="diMingguIni"
                    class="h-9 border-x border-zinc-200 px-3 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 disabled:text-zinc-400 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                    Minggu ini
                </button>
                <button type="button" @click="geser(1)" class="grid size-9 place-items-center rounded-r-xl text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-800" aria-label="Minggu berikutnya">
                    <x-heroicon-m-chevron-right class="size-5" />
                </button>
            </div>
            <p class="text-sm font-semibold text-zinc-900 dark:text-white" x-text="labelRentang"></p>
            <svg x-show="memuat" x-cloak class="size-4 animate-spin text-brand-500" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        </div>
        <div class="flex items-center gap-2">
            @if ($ruangan->count() > 1)
                <select x-model="ruangan" class="input h-9 py-0 text-sm sm:w-48" aria-label="Filter ruangan">
                    <option value="">Semua ruangan</option>
                    @foreach ($ruangan as $r)
                        <option value="{{ $r->slug }}">{{ $r->nama_ruangan }}</option>
                    @endforeach
                </select>
            @endif
            <div class="hidden rounded-xl bg-zinc-100 p-0.5 md:flex dark:bg-zinc-800">
                <button type="button" @click="tampilan = 'minggu'" :class="tampilan === 'minggu' ? 'bg-white shadow-sm dark:bg-zinc-900 text-zinc-900 dark:text-white' : 'text-zinc-500'" class="rounded-lg px-3 py-1.5 text-xs font-semibold">Grid</button>
                <button type="button" @click="tampilan = 'daftar'" :class="tampilan === 'daftar' ? 'bg-white shadow-sm dark:bg-zinc-900 text-zinc-900 dark:text-white' : 'text-zinc-500'" class="rounded-lg px-3 py-1.5 text-xs font-semibold">Daftar</button>
            </div>
        </div>
    </div>

    <template x-if="galat">
        <div class="p-5"><x-alert tone="danger"><span x-text="galat"></span></x-alert></div>
    </template>

    {{-- Tampilan grid (desktop) --}}
    <div x-show="tampilan === 'minggu'" class="overflow-x-auto scrollbar-tipis">
        <div class="min-w-[720px]">
            <div class="grid border-b border-zinc-100 dark:border-zinc-800" :style="`grid-template-columns: 3.5rem repeat(${hariTampil.length}, minmax(0, 1fr))`">
                <div></div>
                <template x-for="h in hariTampil" :key="h.iso">
                    <div class="border-l border-zinc-100 px-2 py-3 text-center dark:border-zinc-800" :class="! h.operasional && 'bg-zinc-50 dark:bg-zinc-900/60'">
                        <p class="text-[11px] font-semibold tracking-wide text-zinc-500 uppercase" x-text="h.nama"></p>
                        <p class="mx-auto mt-1 grid size-8 place-items-center rounded-full text-sm font-bold"
                            :class="h.hariIni ? 'bg-brand-600 text-white' : (h.lewat ? 'text-zinc-400' : 'text-zinc-900 dark:text-white')" x-text="h.tgl"></p>
                    </div>
                </template>
            </div>
            <div class="relative grid" :style="`grid-template-columns: 3.5rem repeat(${hariTampil.length}, minmax(0, 1fr))`">
                {{-- Label jam --}}
                <div>
                    <template x-for="j in labelJamGrid" :key="j">
                        <div class="h-16 pr-2 text-right text-[11px] font-medium text-zinc-400 tabular-nums" x-text="j"></div>
                    </template>
                </div>
                <template x-for="h in hariTampil" :key="h.iso">
                    <div class="relative border-l border-zinc-100 dark:border-zinc-800" :class="[! h.operasional && 'bg-zinc-50 dark:bg-zinc-900/60', h.lewat && 'bg-zinc-50/60 dark:bg-zinc-900/30']">
                        <template x-for="j in labelJamGrid" :key="j">
                            <div class="h-16 border-b border-dashed border-zinc-100 dark:border-zinc-800/80"></div>
                        </template>
                        <template x-for="ev in eventsHari(h.iso)" :key="ev.id">
                            <div class="absolute inset-x-1 overflow-hidden rounded-lg border-l-[3px] px-2 py-1 text-left text-xs leading-tight shadow-xs"
                                :class="warna(ev)" :style="gaya(ev)">
                                @if ($admin)
                                    <a :href="ev.url" x-show="ev.url" class="absolute inset-0" :aria-label="ev.judul"></a>
                                @endif
                                <p class="font-semibold tabular-nums" x-text="`${labelJam(ev.mulai)}–${labelJam(ev.selesai)}`"></p>
                                <p class="truncate" x-text="ev.judul"></p>
                                <p x-show="ruangan === '' && {{ $ruangan->count() > 1 ? 'true' : 'false' }}" class="truncate opacity-70" x-text="ev.ruangan"></p>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Tampilan daftar (ponsel) --}}
    <div x-show="tampilan === 'daftar'" x-cloak class="divide-y divide-zinc-100 dark:divide-zinc-800">
        <template x-for="h in hariTampil" :key="h.iso">
            <div class="flex gap-4 px-4 py-4 sm:px-5">
                <div class="w-12 shrink-0 text-center">
                    <p class="text-[11px] font-semibold text-zinc-500 uppercase" x-text="h.nama"></p>
                    <p class="mx-auto mt-0.5 grid size-9 place-items-center rounded-full text-base font-bold"
                        :class="h.hariIni ? 'bg-brand-600 text-white' : 'text-zinc-900 dark:text-white'" x-text="h.tgl"></p>
                </div>
                <div class="min-w-0 flex-1 space-y-2">
                    <template x-if="eventsHari(h.iso).length === 0">
                        <p class="py-2 text-sm" :class="h.operasional && ! h.lewat ? 'font-medium text-emerald-600 dark:text-emerald-400' : 'text-zinc-400'"
                            x-text="! h.operasional ? 'Tidak beroperasi' : (h.lewat ? 'Tidak ada jadwal' : 'Semua slot kosong')"></p>
                    </template>
                    <template x-for="ev in eventsHari(h.iso)" :key="ev.id">
                        <a :href="ev.url" class="flex items-center gap-3 rounded-xl border-l-[3px] px-3 py-2" :class="warna(ev)">
                            <span class="text-sm font-semibold tabular-nums" x-text="`${labelJam(ev.mulai)}–${labelJam(ev.selesai)}`"></span>
                            <span class="min-w-0 flex-1 truncate text-sm" x-text="ev.judul"></span>
                            <span class="hidden text-xs opacity-70 sm:inline" x-text="ev.status_label"></span>
                        </a>
                    </template>
                </div>
            </div>
        </template>
    </div>

    {{-- Legenda --}}
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-zinc-100 bg-zinc-50/60 px-4 py-3 text-xs text-zinc-500 sm:px-5 dark:border-zinc-800 dark:bg-zinc-900/40">
        <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-sky-400"></span>Disetujui</span>
        <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-amber-400"></span>Menunggu konfirmasi</span>
        <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-violet-400"></span>Sedang berlangsung</span>
        @if ($admin)
            <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-emerald-400"></span>Selesai</span>
            <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-orange-400"></span>Tidak hadir</span>
        @endif
        <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-zinc-300 dark:bg-zinc-600"></span>Tutup</span>
        <span class="ml-auto" x-show="! memuat" x-text="`${jumlahEvent} jadwal minggu ini`"></span>
    </div>
</div>
