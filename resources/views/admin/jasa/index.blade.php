@php
    $q = fn (array $ubah = []) => route('admin.jasa.index', array_filter(array_merge(request()->only(['q', 'layanan']), $ubah), fn ($v) => $v !== null && $v !== ''));
    $tab = fn (string $label, string $status) => ['label' => $label, 'href' => $q(['status' => $status === 'semua' ? null : $status]), 'aktif' => $filter['status'] === $status, 'jumlah' => $status === 'semua' ? $hitung->sum() : (int) ($hitung[$status] ?? 0)];
@endphp
<x-layouts.app title="Pesanan Jasa">
    <x-page-header title="Pesanan jasa musik" description="Kelola antrean produksi, jadwal, dan pengiriman hasil.">
        <x-button :href="route('admin.layanan.index')" variant="secondary" icon="heroicon-o-sparkles">Kelola layanan</x-button>
        <x-button :href="route('admin.jasa.ekspor', request()->query())" variant="secondary" icon="heroicon-o-arrow-down-tray">Ekspor CSV</x-button>
    </x-page-header>

    <x-tabs class="mb-4" :items="[
        $tab('Menunggu', 'pending'),
        $tab('Antrean', 'approved'),
        $tab('Diproduksi', 'in_production'),
        $tab('Selesai', 'completed'),
        $tab('Semua', 'semua'),
    ]" />

    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row" x-data>
        @if ($filter['status'] !== 'semua')<input type="hidden" name="status" value="{{ $filter['status'] }}">@endif
        <div class="relative flex-1">
            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-zinc-400" />
            <input type="search" name="q" value="{{ $filter['q'] }}" placeholder="Cari kode, judul, pemesan…" class="input pl-11" aria-label="Cari">
        </div>
        <select name="layanan" class="input sm:w-52" @change="$el.form.submit()" aria-label="Layanan">
            <option value="">Semua layanan</option>
            @foreach ($layanan as $l)
                <option value="{{ $l->slug }}" @selected($filter['layanan'] === $l->slug)>{{ $l->nama_jenis_jasa }}</option>
            @endforeach
        </select>
        <x-button type="submit" variant="secondary">Cari</x-button>
    </form>

    <div class="card overflow-hidden">
        @forelse ($pesanan as $j)
            @php $sisa = $j->sisaHari(); @endphp
            <a href="{{ route('admin.jasa.show', $j) }}" class="group flex flex-col gap-2 border-b border-zinc-100 px-4 py-4 last:border-0 hover:bg-zinc-50/70 sm:flex-row sm:items-center sm:gap-4 sm:px-6 dark:border-zinc-800 dark:hover:bg-zinc-800/30">
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <x-avatar :user="$j->user" size="sm" />
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-zinc-900 group-hover:text-brand-600 dark:text-white">{{ $j->judul }}</p>
                        <p class="truncate text-sm text-zinc-500">{{ $j->jasa->nama_jenis_jasa }} · {{ $j->user->username }} · <span class="font-mono text-xs">{{ $j->kode }}</span></p>
                    </div>
                </div>
                <div class="flex items-center gap-2 pl-11 sm:pl-0">
                    <x-status :status="$j->status" size="sm" pendek />
                    <span class="text-xs text-zinc-500 tabular-nums">Tenggat {{ tanggal($j->tenggat_produksi, 'd M') }}</span>
                    @if (! is_null($sisa))
                        <x-badge size="sm" :tone="$sisa < 0 ? 'rose' : ($sisa <= 3 ? 'amber' : 'zinc')">{{ $sisa < 0 ? 'Telat '.abs($sisa).' hari' : ($sisa === 0 ? 'Hari ini' : $sisa.' hari') }}</x-badge>
                    @endif
                </div>
            </a>
        @empty
            <x-empty icon="heroicon-o-musical-note" title="Tidak ada pesanan" description="Belum ada pesanan untuk filter ini." />
        @endforelse
    </div>

    @if ($pesanan->hasPages())
        <div class="mt-6">{{ $pesanan->links() }}</div>
    @endif
</x-layouts.app>
