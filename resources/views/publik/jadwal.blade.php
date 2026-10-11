@php
    $user = auth()->user();
    $ctaPinjam = $user && ! $user->isStaff() ? route('akun.peminjaman.create') : ($user ? route('admin.dashboard') : route('login'));
@endphp
<x-layouts.publik title="Jadwal Studio" description="Lihat jadwal pemakaian Studio Musik ITERA secara langsung dan temukan slot yang masih kosong.">
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
        <x-page-header title="Jadwal studio" description="Pantau slot yang sudah terisi minggu ini. Identitas peminjam disembunyikan demi privasi.">
            <x-button :href="$ctaPinjam" icon="heroicon-m-plus">Ajukan peminjaman</x-button>
        </x-page-header>

        <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="card flex items-center gap-3 p-4">
                <x-heroicon-o-clock class="size-6 text-brand-500" />
                <div><p class="text-xs text-zinc-500">Jam operasional</p><p class="text-sm font-semibold">{{ jam($aturan['jam_buka']) }}–{{ jam($aturan['jam_tutup']) }} WIB</p></div>
            </div>
            <div class="card flex items-center gap-3 p-4">
                <x-heroicon-o-calendar class="size-6 text-brand-500" />
                <div><p class="text-xs text-zinc-500">Hari operasional</p><p class="text-sm font-semibold">{{ $aturan['label_hari'] }}</p></div>
            </div>
            <div class="card flex items-center gap-3 p-4">
                <x-heroicon-o-arrows-right-left class="size-6 text-brand-500" />
                <div><p class="text-xs text-zinc-500">Durasi per sesi</p><p class="text-sm font-semibold">{{ durasi_label($aturan['durasi_min_menit']) }} – {{ durasi_label($aturan['durasi_maks_menit']) }}</p></div>
            </div>
        </div>

        <x-kalender-mingguan :url="route('jadwal.data')" :ruangan="$ruangan" :aturan="$aturan" />
    </section>
</x-layouts.publik>
