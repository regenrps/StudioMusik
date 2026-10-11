@php
    $tautan = function ($log) {
        return match ($log->subjek_type) {
            'peminjaman' => $log->subjek ? route('admin.peminjaman.show', $log->subjek) : null,
            'pesanan_jasa' => $log->subjek ? route('admin.jasa.show', $log->subjek) : null,
            'laporan' => $log->subjek ? route('admin.laporan.show', $log->subjek) : null,
            default => null,
        };
    };
@endphp
<x-layouts.app title="Log Aktivitas">
    <x-page-header title="Log aktivitas" description="Jejak audit seluruh tindakan penting di sistem." />

    <form method="GET" class="card mb-4 grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-[1fr_200px_160px_160px_auto]">
        <div class="relative sm:col-span-2 lg:col-span-1">
            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-zinc-400" />
            <input type="search" name="q" value="{{ $filter['q'] }}" placeholder="Cari deskripsi atau nama pengguna…" class="input pl-11" aria-label="Cari">
        </div>
        <select name="aksi" class="input" aria-label="Jenis aksi">
            <option value="">Semua aksi</option>
            @foreach ($daftarAksi as $a)
                <option value="{{ $a }}" @selected($filter['aksi'] === $a)>{{ str_replace('_', ' ', $a) }}</option>
            @endforeach
        </select>
        <input type="date" name="dari" value="{{ $filter['dari'] }}" class="input" aria-label="Dari tanggal">
        <input type="date" name="sampai" value="{{ $filter['sampai'] }}" class="input" aria-label="Sampai tanggal">
        <x-button type="submit" variant="secondary">Terapkan</x-button>
    </form>

    <div class="card overflow-hidden">
        @if ($log->isEmpty())
            <x-empty icon="heroicon-o-clipboard-document-list" title="Tidak ada aktivitas" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 bg-zinc-50/80 text-xs font-semibold tracking-wide text-zinc-500 uppercase dark:border-zinc-800 dark:bg-zinc-900/60">
                        <tr>
                            <th class="px-6 py-3">Waktu</th>
                            <th class="px-4 py-3">Pelaku</th>
                            <th class="px-4 py-3">Aksi</th>
                            <th class="px-4 py-3">Deskripsi</th>
                            <th class="px-6 py-3">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($log as $item)
                            <tr class="align-top">
                                <td class="px-6 py-3 text-xs whitespace-nowrap text-zinc-500" title="{{ $item->created_at }}">{{ tanggal_waktu($item->created_at) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="font-medium text-zinc-900 dark:text-white">{{ $item->user?->username ?? 'Sistem' }}</span>
                                    @if ($item->user?->isStaff())<span class="text-xs text-zinc-400">({{ $item->user->user_role->label() }})</span>@endif
                                </td>
                                <td class="px-4 py-3"><x-badge size="sm">{{ str_replace('_', ' ', $item->aksi) }}</x-badge></td>
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                    @php $url = $tautan($item); @endphp
                                    @if ($url)<a href="{{ $url }}" class="hover:text-brand-600 hover:underline">{{ $item->deskripsi }}</a>@else{{ $item->deskripsi }}@endif
                                    @if (! empty($item->properti['alasan']) || ! empty($item->properti['catatan']))
                                        <p class="mt-0.5 text-xs text-zinc-500">“{{ $item->properti['alasan'] ?? $item->properti['catatan'] }}”</p>
                                    @endif
                                </td>
                                <td class="px-6 py-3 font-mono text-xs text-zinc-400">{{ $item->ip_address ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @if ($log->hasPages())<div class="mt-6">{{ $log->links() }}</div>@endif
</x-layouts.app>
