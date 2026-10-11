<x-layouts.app title="Peminjaman Saya">
    <x-page-header title="Peminjaman saya" description="Pantau status pengajuan, lakukan check-in/out, dan lihat riwayat pemakaian studio.">
        <x-button :href="route('akun.peminjaman.create')" icon="heroicon-m-plus">Pinjam studio</x-button>
    </x-page-header>

    <x-tabs class="mb-5" :items="[
        ['label' => 'Aktif', 'href' => route('akun.peminjaman.index', ['tab' => 'aktif']), 'aktif' => $tab === 'aktif', 'jumlah' => $jumlah['aktif']],
        ['label' => 'Riwayat', 'href' => route('akun.peminjaman.index', ['tab' => 'riwayat']), 'aktif' => $tab === 'riwayat', 'jumlah' => $jumlah['riwayat']],
    ]" />

    <div class="card overflow-hidden">
        @forelse ($peminjaman as $p)
            <div class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                @include('akun.peminjaman._item', ['p' => $p])
            </div>
        @empty
            @if ($tab === 'aktif')
                <x-empty icon="heroicon-o-calendar-days" title="Tidak ada peminjaman aktif" description="Cek slot yang masih kosong dan ajukan peminjaman dalam hitungan detik.">
                    <x-button :href="route('akun.peminjaman.create')" icon="heroicon-m-plus">Pinjam studio</x-button>
                    <x-button :href="route('jadwal')" variant="secondary">Lihat jadwal</x-button>
                </x-empty>
            @else
                <x-empty icon="heroicon-o-archive-box" title="Belum ada riwayat" description="Peminjaman yang selesai, dibatalkan, atau ditolak akan tampil di sini." />
            @endif
        @endforelse
    </div>

    @if ($peminjaman->hasPages())
        <div class="mt-6">{{ $peminjaman->links() }}</div>
    @endif
</x-layouts.app>
