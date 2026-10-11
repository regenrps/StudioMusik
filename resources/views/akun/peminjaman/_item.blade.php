{{-- Satu baris peminjaman pada daftar milik peminjam --}}
<a href="{{ route('akun.peminjaman.show', $p) }}" class="group flex items-center gap-4 px-4 py-4 transition hover:bg-zinc-50 sm:px-6 dark:hover:bg-zinc-800/40">
    <div @class([
        'flex w-14 shrink-0 flex-col items-center rounded-xl py-2 text-center ring-1 ring-inset',
        'bg-brand-50 text-brand-700 ring-brand-200 dark:bg-brand-500/10 dark:text-brand-300 dark:ring-brand-500/20' => $p->status->blocksSlot(),
        'bg-zinc-50 text-zinc-500 ring-zinc-200 dark:bg-zinc-800/60 dark:ring-zinc-700' => ! $p->status->blocksSlot(),
    ])>
        <span class="text-[10px] font-bold uppercase">{{ tanggal($p->tgl_pinjam, 'M') }}</span>
        <span class="text-xl leading-none font-extrabold">{{ $p->tgl_pinjam->format('d') }}</span>
        <span class="text-[10px] font-medium">{{ tanggal($p->tgl_pinjam, 'D') }}</span>
    </div>
    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <p class="font-semibold text-zinc-900 dark:text-white">{{ jam($p->waktu_mulai) }}–{{ jam($p->waktu_selesai) }}</p>
            <x-status :status="$p->status" size="sm" pendek />
        </div>
        <p class="mt-0.5 truncate text-sm text-zinc-500">{{ $p->ruangan?->nama_ruangan }} · {{ $p->ket_keperluan }}</p>
        <p class="mt-0.5 font-mono text-[11px] text-zinc-400">{{ $p->kode }}</p>
    </div>
    @if ($p->bisaCheckIn())
        <x-badge tone="violet" class="hidden sm:inline-flex" icon="heroicon-m-camera">Check-in</x-badge>
    @elseif ($p->bisaDiulas())
        <x-badge tone="amber" class="hidden sm:inline-flex" icon="heroicon-m-star">Beri ulasan</x-badge>
    @endif
    <x-heroicon-m-chevron-right class="size-5 shrink-0 text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-zinc-500" />
</a>
