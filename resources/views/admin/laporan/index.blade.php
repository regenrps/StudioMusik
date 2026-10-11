@php
    use App\Enums\StatusLaporan;
    $tab = fn (string $label, ?string $status) => ['label' => $label, 'href' => route('admin.laporan.index', array_filter(['status' => $status, 'jenis' => $filter['jenis']])), 'aktif' => $filter['status'] === $status, 'jumlah' => $status ? (int) ($hitung[$status] ?? 0) : $hitung->sum()];
@endphp
<x-layouts.app title="Laporan Masalah">
    <x-page-header title="Laporan masalah" description="Catat kerusakan alat, kebersihan, atau insiden lain beserta tindak lanjutnya.">
        <x-button :href="route('admin.laporan.create')" icon="heroicon-m-plus">Buat laporan</x-button>
    </x-page-header>

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-tabs :items="[
            $tab('Baru', 'open'),
            $tab('Ditangani', 'in_progress'),
            $tab('Selesai', 'resolved'),
            $tab('Semua', null),
        ]" />
        <form method="GET" x-data>
            @if ($filter['status'])<input type="hidden" name="status" value="{{ $filter['status'] }}">@endif
            <select name="jenis" class="input sm:w-56" @change="$el.form.submit()" aria-label="Jenis laporan">
                <option value="">Semua jenis</option>
                @foreach (\App\Models\Laporan::JENIS as $j)
                    <option value="{{ $j }}" @selected($filter['jenis'] === $j)>{{ $j }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="card overflow-hidden">
        @forelse ($laporan as $l)
            <a href="{{ route('admin.laporan.show', $l) }}" class="group flex items-start gap-4 border-b border-zinc-100 px-4 py-4 last:border-0 hover:bg-zinc-50/70 sm:px-6 dark:border-zinc-800 dark:hover:bg-zinc-800/30">
                @php $gambar = $l->daftarGambar(); @endphp
                @if (count($gambar))
                    <img src="{{ route('berkas.laporan', [$l, 0]) }}" alt="" loading="lazy" class="size-14 shrink-0 rounded-xl object-cover">
                @else
                    <span class="grid size-14 shrink-0 place-items-center rounded-xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800"><x-heroicon-o-exclamation-triangle class="size-6" /></span>
                @endif
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-semibold text-zinc-900 group-hover:text-brand-600 dark:text-white">{{ $l->jenis_laporan }}</p>
                        <x-badge :tone="$l->status->tone()" size="sm" dot>{{ $l->status->label() }}</x-badge>
                    </div>
                    <p class="mt-0.5 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $l->keterangan }}</p>
                    <p class="mt-1 text-xs text-zinc-400">
                        {{ tanggal($l->tgl_laporan) }} · {{ $l->pelapor?->username ?? '—' }}
                        @if ($l->ruangan) · {{ $l->ruangan->nama_ruangan }} @endif
                        @if ($l->peminjaman) · <span class="font-mono">{{ $l->peminjaman->kode }}</span> @endif
                    </p>
                </div>
                <x-heroicon-m-chevron-right class="mt-4 size-5 shrink-0 text-zinc-300" />
            </a>
        @empty
            <x-empty icon="heroicon-o-shield-check" title="Tidak ada laporan" description="Belum ada laporan untuk filter ini." />
        @endforelse
    </div>
    @if ($laporan->hasPages())<div class="mt-6">{{ $laporan->links() }}</div>@endif
</x-layouts.app>
