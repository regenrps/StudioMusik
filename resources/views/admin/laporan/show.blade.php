@php $l = $laporan; @endphp
<x-layouts.app :title="'Laporan '.$l->jenis_laporan" lebar="max-w-5xl">
    <x-page-header :title="$l->jenis_laporan" :kembali="route('admin.laporan.index')" kembali-label="Laporan masalah">
        <x-slot:meta>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <x-badge :tone="$l->status->tone()" dot>{{ $l->status->label() }}</x-badge>
                <span class="text-xs text-zinc-500">Laporan #{{ $l->getKey() }} · {{ tanggal($l->tgl_laporan) }}</span>
            </div>
        </x-slot:meta>
        <x-button :href="route('admin.laporan.edit', $l)" variant="secondary" icon="heroicon-o-pencil-square">Ubah / tindak lanjut</x-button>
        @can('admin')
            <form method="POST" action="{{ route('admin.laporan.destroy', $l) }}" data-konfirmasi="Hapus laporan ini beserta fotonya?">
                @csrf @method('DELETE')
                <x-button type="submit" variant="danger-soft" icon="heroicon-o-trash">Hapus</x-button>
            </form>
        @endcan
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_320px]">
        <div class="space-y-6">
            <x-card title="Uraian" icon="heroicon-o-document-text">
                <p class="text-sm leading-relaxed whitespace-pre-line text-zinc-700 dark:text-zinc-300">{{ $l->keterangan }}</p>
                @if (count($l->daftarGambar()))
                    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($l->daftarGambar() as $i => $g)
                            <a href="{{ route('berkas.laporan', [$l, $i]) }}" target="_blank" class="block overflow-hidden rounded-xl ring-1 ring-zinc-200 dark:ring-zinc-700">
                                <img src="{{ route('berkas.laporan', [$l, $i]) }}" alt="Foto bukti {{ $i + 1 }}" loading="lazy" class="aspect-[4/3] w-full object-cover transition hover:scale-105">
                            </a>
                        @endforeach
                    </div>
                @endif
            </x-card>

            <x-card title="Tindak lanjut" icon="heroicon-o-wrench-screwdriver">
                @if ($l->tindak_lanjut)
                    <p class="text-sm leading-relaxed whitespace-pre-line text-zinc-700 dark:text-zinc-300">{{ $l->tindak_lanjut }}</p>
                    @if ($l->selesai_at)<p class="mt-3 text-xs text-zinc-500">Diselesaikan {{ tanggal_waktu($l->selesai_at) }}</p>@endif
                @else
                    <p class="text-sm text-zinc-500">Belum ada tindak lanjut. <a href="{{ route('admin.laporan.edit', $l) }}" class="link">Tambahkan</a>.</p>
                @endif
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card title="Informasi" icon="heroicon-o-information-circle">
                <dl class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    <x-info label="Pelapor">{{ $l->pelapor?->username ?? '—' }}</x-info>
                    <x-info label="Ruangan">{{ $l->ruangan?->nama_ruangan ?? '—' }}</x-info>
                    <x-info label="Dibuat">{{ tanggal_waktu($l->created_at) }}</x-info>
                    @if ($l->peminjaman)
                        <x-info label="Peminjaman"><a href="{{ route('admin.peminjaman.show', $l->peminjaman) }}" class="link font-mono">{{ $l->peminjaman->kode }}</a></x-info>
                        <x-info label="Peminjam">{{ $l->peminjaman->user?->username }}</x-info>
                    @endif
                </dl>
            </x-card>
            <x-card title="Riwayat" icon="heroicon-o-clock"><x-timeline :log="$l->log" /></x-card>
        </aside>
    </div>
</x-layouts.app>
