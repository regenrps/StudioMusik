@php
    use App\Enums\StatusPeminjaman as S;
    $q = fn (array $ubah = []) => route('admin.peminjaman.index', array_filter(array_merge(request()->only(['q', 'ruangan', 'dari', 'sampai']), $ubah), fn ($v) => $v !== null && $v !== ''));
    $tab = fn (string $label, string $status) => ['label' => $label, 'href' => $q(['status' => $status === 'semua' ? null : $status]), 'aktif' => $filter['status'] === $status, 'jumlah' => $status === 'semua' ? $hitung->sum() : (int) ($hitung[$status] ?? 0)];
    $adaFilter = $filter['q'] !== '' || $filter['ruangan'] || $filter['dari'] || $filter['sampai'];
@endphp
<x-layouts.app title="Peminjaman">
    <x-page-header title="Peminjaman studio" description="Tinjau pengajuan, pantau pemakaian, dan ekspor data peminjaman.">
        <x-button :href="route('admin.peminjaman.kalender')" variant="secondary" icon="heroicon-o-calendar">Kalender</x-button>
        <x-button :href="route('admin.peminjaman.ekspor', request()->query())" variant="secondary" icon="heroicon-o-arrow-down-tray">Ekspor CSV</x-button>
    </x-page-header>

    <x-tabs class="mb-4" :items="[
        $tab('Menunggu', 'pending'),
        $tab('Disetujui', 'approved'),
        $tab('Berlangsung', 'in_use'),
        $tab('Selesai', 'completed'),
        $tab('Semua', 'semua'),
    ]" />

    <form method="GET" class="card mb-4 grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-[1fr_180px_160px_160px_auto]" x-data>
        @if ($filter['status'] !== 'semua')<input type="hidden" name="status" value="{{ $filter['status'] }}">@endif
        <div class="relative sm:col-span-2 lg:col-span-1">
            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-zinc-400" />
            <input type="search" name="q" value="{{ $filter['q'] }}" placeholder="Cari kode, nama, email, NIM, keperluan…" class="input pl-11" aria-label="Cari">
        </div>
        <select name="ruangan" class="input" aria-label="Ruangan" @change="$el.form.submit()">
            <option value="">Semua ruangan</option>
            @foreach ($ruangan as $r)
                <option value="{{ $r->slug }}" @selected($filter['ruangan'] === $r->slug)>{{ $r->nama_ruangan }}</option>
            @endforeach
        </select>
        <input type="date" name="dari" value="{{ $filter['dari'] }}" class="input" aria-label="Dari tanggal" title="Dari tanggal">
        <input type="date" name="sampai" value="{{ $filter['sampai'] }}" class="input" aria-label="Sampai tanggal" title="Sampai tanggal">
        <div class="flex gap-2">
            <x-button type="submit" variant="secondary" class="flex-1">Terapkan</x-button>
            @if ($adaFilter)
                <x-button :href="$q(['q' => null, 'ruangan' => null, 'dari' => null, 'sampai' => null, 'status' => $filter['status'] === 'semua' ? null : $filter['status']])" variant="ghost" size="icon" icon="heroicon-o-x-mark" aria-label="Reset filter" />
            @endif
        </div>
    </form>

    <div class="card overflow-hidden">
        @if ($peminjaman->isEmpty())
            <x-empty icon="heroicon-o-calendar-days" title="Tidak ada peminjaman" :description="$adaFilter ? 'Tidak ada data yang cocok dengan filter.' : 'Belum ada data untuk status ini.'" />
        @else
            {{-- Tabel (desktop) --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 bg-zinc-50/80 text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:border-zinc-800 dark:bg-zinc-900/60">
                        <tr>
                            <th class="px-6 py-3">Peminjam</th>
                            <th class="px-4 py-3">Jadwal</th>
                            <th class="px-4 py-3">Ruangan</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Diajukan</th>
                            <th class="px-6 py-3 text-right"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($peminjaman as $p)
                            <tr class="group hover:bg-zinc-50/70 dark:hover:bg-zinc-800/30">
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <x-avatar :user="$p->user" size="sm" />
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.peminjaman.show', $p) }}" class="block truncate font-semibold text-zinc-900 group-hover:text-brand-600 dark:text-white">{{ $p->user->username }}</a>
                                            <p class="font-mono text-[11px] text-zinc-400">{{ $p->kode }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <p class="font-medium text-zinc-900 dark:text-white">{{ tanggal($p->tgl_pinjam, 'D, d M Y') }}</p>
                                    <p class="text-xs text-zinc-500 tabular-nums">{{ jam($p->waktu_mulai) }}–{{ jam($p->waktu_selesai) }} · {{ $p->jumlah_orang }} org</p>
                                </td>
                                <td class="px-4 py-3.5 text-zinc-600 dark:text-zinc-300">{{ $p->ruangan->nama_ruangan }}</td>
                                <td class="px-4 py-3.5"><x-status :status="$p->status" size="sm" pendek /></td>
                                <td class="px-4 py-3.5 text-xs whitespace-nowrap text-zinc-500" title="{{ tanggal_waktu($p->created_at) }}">{{ relatif($p->created_at) }}</td>
                                <td class="px-6 py-3.5 text-right">
                                    <x-button :href="route('admin.peminjaman.show', $p)" :variant="$p->status === S::Menunggu ? 'soft' : 'ghost'" size="sm">
                                        {{ $p->status === S::Menunggu && auth()->user()->isAdmin() ? 'Tinjau' : 'Detail' }}
                                    </x-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Kartu (ponsel) --}}
            <div class="divide-y divide-zinc-100 md:hidden dark:divide-zinc-800">
                @foreach ($peminjaman as $p)
                    <a href="{{ route('admin.peminjaman.show', $p) }}" class="flex items-start gap-3 px-4 py-4">
                        <x-avatar :user="$p->user" size="sm" />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="truncate font-semibold text-zinc-900 dark:text-white">{{ $p->user->username }}</p>
                                <x-status :status="$p->status" size="sm" pendek />
                            </div>
                            <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-300">{{ tanggal($p->tgl_pinjam, 'D, d M') }} · {{ jam($p->waktu_mulai) }}–{{ jam($p->waktu_selesai) }}</p>
                            <p class="font-mono text-[11px] text-zinc-400">{{ $p->kode }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if ($peminjaman->hasPages())
        <div class="mt-6">{{ $peminjaman->links() }}</div>
    @endif
</x-layouts.app>
