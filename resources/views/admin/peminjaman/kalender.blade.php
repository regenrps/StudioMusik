<x-layouts.app title="Kalender Peminjaman">
    <x-page-header title="Kalender peminjaman" description="Lihat seluruh jadwal per minggu. Klik jadwal untuk membuka detailnya.">
        <x-button :href="route('admin.peminjaman.index')" variant="secondary" icon="heroicon-o-list-bullet">Daftar</x-button>
        @can('admin')
            <x-button :href="route('admin.jadwal-tutup.index')" variant="secondary" icon="heroicon-o-no-symbol">Jadwal tutup</x-button>
        @endcan
    </x-page-header>

    <x-kalender-mingguan :url="route('admin.peminjaman.kalender.data')" :ruangan="$ruangan" :aturan="$aturan" admin />
</x-layouts.app>
