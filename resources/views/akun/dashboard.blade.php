@php
    use App\Enums\StatusPeminjaman as S;

    $jamSekarang = (int) now()->format('H');
    $sapaan = $jamSekarang < 11 ? 'Selamat pagi' : ($jamSekarang < 15 ? 'Selamat siang' : ($jamSekarang < 18 ? 'Selamat sore' : 'Selamat malam'));
    $namaDepan = explode(' ', trim($user->username))[0];
@endphp

<x-layouts.app title="Beranda">
    <div class="mb-6 sm:mb-8">
        <p class="text-sm font-medium text-zinc-500">{{ tanggal_lengkap(today()) }}</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">{{ $sapaan }}, {{ $namaDepan }}!</h1>
    </div>

    @if ($pengumuman)
        <x-alert tone="brand" icon="heroicon-s-megaphone" class="mb-6">{{ $pengumuman }}</x-alert>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Jadwal berikutnya --}}
            @if ($berikutnya)
                @php $b = $berikutnya; @endphp
                <div class="card overflow-hidden" x-data="hitungMundur({ mulai: @js($b->mulai->toIso8601String()), selesai: @js($b->selesai->toIso8601String()) })">
                    <div @class([
                        'relative overflow-hidden px-5 py-5 text-white sm:px-6',
                        'bg-gradient-to-br from-violet-500 to-fuchsia-600' => $b->status === S::Berlangsung,
                        'bg-gradient-to-br from-sky-500 to-brand-600' => $b->status === S::Disetujui,
                        'bg-gradient-to-br from-amber-500 to-orange-500' => $b->status === S::Menunggu,
                    ])>
                        <div class="pointer-events-none absolute -top-10 -right-10 size-40 rounded-full bg-white/10"></div>
                        <p class="text-xs font-bold tracking-widest text-white/80 uppercase">
                            {{ $b->status === S::Berlangsung ? 'Sedang berlangsung' : 'Jadwal berikutnya' }}
                        </p>
                        <p class="mt-2 text-2xl font-bold">{{ tanggal($b->tgl_pinjam, 'l, d F') }}</p>
                        <p class="mt-0.5 text-lg font-semibold text-white/90">{{ jam($b->waktu_mulai) }}–{{ jam($b->waktu_selesai) }} · {{ $b->ruangan->nama_ruangan }}</p>
                        <p class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-sm font-medium backdrop-blur" x-text="teks"></p>
                    </div>
                    <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div class="flex items-start gap-3">
                            <x-dynamic-component :component="$b->status->icon()" class="mt-0.5 size-5 shrink-0 text-zinc-400" />
                            <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $b->status->description() }}</p>
                        </div>
                        @if ($b->bisaCheckIn())
                            <x-button :href="route('akun.peminjaman.show', $b)" icon="heroicon-m-camera" class="shrink-0">Check-in sekarang</x-button>
                        @elseif ($b->bisaCheckOut())
                            <x-button :href="route('akun.peminjaman.show', $b)" variant="success" icon="heroicon-m-check-circle" class="shrink-0">Check-out</x-button>
                        @else
                            <x-button :href="route('akun.peminjaman.show', $b)" variant="secondary" class="shrink-0">Lihat detail</x-button>
                        @endif
                    </div>
                </div>
            @else
                <div class="card relative overflow-hidden p-6 sm:p-8">
                    <div class="pointer-events-none absolute -top-16 -right-16 size-56 rounded-full bg-brand-200/40 blur-2xl dark:bg-brand-600/20"></div>
                    <div class="relative max-w-md">
                        <span class="grid size-12 place-items-center rounded-2xl bg-brand-600 text-white shadow-lg shadow-brand-600/30"><x-heroicon-o-musical-note class="size-6" /></span>
                        <h2 class="mt-4 text-xl font-bold text-zinc-900 dark:text-white">Belum ada jadwal studio</h2>
                        <p class="mt-1.5 text-sm text-zinc-500 dark:text-zinc-400">Studio buka {{ $aturan['label_hari'] }}, {{ jam($aturan['jam_buka']) }}–{{ jam($aturan['jam_tutup']) }}. Pilih slot yang masih kosong dan ajukan sekarang.</p>
                        <div class="mt-5 flex flex-wrap gap-2">
                            <x-button :href="route('akun.peminjaman.create')" icon="heroicon-m-plus">Pinjam studio</x-button>
                            <x-button :href="route('jadwal')" variant="secondary" icon="heroicon-o-calendar">Lihat jadwal</x-button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Perlu tindakan --}}
            @if ($perluUlasan || $jasaPerluUlasan)
                <div class="space-y-3">
                    @if ($perluUlasan)
                        <a href="{{ route('akun.peminjaman.show', $perluUlasan) }}" class="card flex items-center gap-4 p-4 transition hover:shadow-lift">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-amber-50 text-amber-500 dark:bg-amber-500/10"><x-heroicon-s-star class="size-5" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-zinc-900 dark:text-white">Beri ulasan untuk sesi {{ tanggal($perluUlasan->tgl_pinjam, 'd M') }}</p>
                                <p class="text-xs text-zinc-500">Bantu pengelola meningkatkan fasilitas studio.</p>
                            </div>
                            <x-heroicon-m-chevron-right class="size-5 text-zinc-300" />
                        </a>
                    @endif
                    @if ($jasaPerluUlasan)
                        <a href="{{ route('akun.jasa.show', $jasaPerluUlasan) }}" class="card flex items-center gap-4 p-4 transition hover:shadow-lift">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-500 dark:bg-emerald-500/10"><x-heroicon-s-gift class="size-5" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-zinc-900 dark:text-white">Hasil {{ $jasaPerluUlasan->jasa->nama_jenis_jasa }} sudah siap</p>
                                <p class="text-xs text-zinc-500">Unduh hasilnya dan beri ulasan.</p>
                            </div>
                            <x-heroicon-m-chevron-right class="size-5 text-zinc-300" />
                        </a>
                    @endif
                </div>
            @endif

            {{-- Riwayat --}}
            <x-card title="Peminjaman terakhir" :padding="false">
                <x-slot:actions>
                    <a href="{{ route('akun.peminjaman.index', ['tab' => 'riwayat']) }}" class="text-sm font-semibold text-brand-600 hover:underline dark:text-brand-400">Semua</a>
                </x-slot:actions>
                @forelse ($riwayat as $p)
                    <div class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">@include('akun.peminjaman._item', ['p' => $p])</div>
                @empty
                    <x-empty compact icon="heroicon-o-calendar-days" title="Belum ada peminjaman" description="Riwayat peminjaman studio Anda akan tampil di sini." />
                @endforelse
            </x-card>
        </div>

        <div class="space-y-6">
            <div class="grid grid-cols-3 gap-3 lg:grid-cols-1">
                <div class="card p-4">
                    <p class="text-xs font-medium text-zinc-500">Total peminjaman</p>
                    <p class="mt-1 text-2xl font-bold text-zinc-900 tabular-nums dark:text-white">{{ $statistik['total'] }}</p>
                </div>
                <div class="card p-4">
                    <p class="text-xs font-medium text-zinc-500">Sesi selesai</p>
                    <p class="mt-1 text-2xl font-bold text-zinc-900 tabular-nums dark:text-white">{{ $statistik['selesai'] }}</p>
                </div>
                <div class="card p-4">
                    <p class="text-xs font-medium text-zinc-500">Pesanan jasa</p>
                    <p class="mt-1 text-2xl font-bold text-zinc-900 tabular-nums dark:text-white">{{ $statistik['jasa'] }}</p>
                </div>
            </div>

            <x-card title="Pesanan jasa aktif" :padding="false">
                <x-slot:actions>
                    <a href="{{ route('akun.jasa.index') }}" class="text-sm font-semibold text-brand-600 hover:underline dark:text-brand-400">Semua</a>
                </x-slot:actions>
                @forelse ($jasaAktif as $j)
                    <a href="{{ route('akun.jasa.show', $j) }}" class="block border-b border-zinc-100 px-5 py-4 last:border-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/40">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $j->judul }}</p>
                            <x-status :status="$j->status" size="sm" pendek />
                        </div>
                        <p class="mt-0.5 text-xs text-zinc-500">{{ $j->jasa->nama_jenis_jasa }} · tenggat {{ tanggal($j->tenggat_produksi, 'd M') }}</p>
                        <div class="mt-3 flex gap-1">
                            @for ($i = 1; $i <= 4; $i++)
                                <span @class(['h-1.5 flex-1 rounded-full', 'bg-brand-500' => $i <= $j->status->step(), 'bg-zinc-200 dark:bg-zinc-700' => $i > $j->status->step()])></span>
                            @endfor
                        </div>
                    </a>
                @empty
                    <div class="p-5">
                        <p class="text-sm text-zinc-500">Tidak ada pesanan jasa yang sedang berjalan.</p>
                        <div class="mt-4 space-y-2">
                            @foreach ($layanan as $l)
                                <a href="{{ route('akun.jasa.create', $l) }}" class="flex items-center gap-3 rounded-xl p-2 ring-1 ring-zinc-200 hover:bg-zinc-50 dark:ring-zinc-700 dark:hover:bg-zinc-800">
                                    <img src="{{ $l->gambar_url }}" alt="" class="size-10 rounded-lg object-cover" loading="lazy">
                                    <span class="flex-1 text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $l->nama_jenis_jasa }}</span>
                                    <x-heroicon-m-plus class="size-4 text-zinc-400" />
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforelse
            </x-card>

            <div class="card p-5">
                <p class="text-sm font-semibold text-zinc-900 dark:text-white">Panduan cepat</p>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <a href="{{ route('tutorial.index') }}" class="flex flex-col items-center gap-2 rounded-xl bg-zinc-50 p-4 text-center text-xs font-semibold text-zinc-700 hover:bg-brand-50 hover:text-brand-700 dark:bg-zinc-800/60 dark:text-zinc-300 dark:hover:bg-brand-500/10">
                        <x-heroicon-o-book-open class="size-6" /> Tutorial alat
                    </a>
                    <a href="{{ route('beranda') }}#sop" class="flex flex-col items-center gap-2 rounded-xl bg-zinc-50 p-4 text-center text-xs font-semibold text-zinc-700 hover:bg-brand-50 hover:text-brand-700 dark:bg-zinc-800/60 dark:text-zinc-300 dark:hover:bg-brand-500/10">
                        <x-heroicon-o-shield-check class="size-6" /> SOP studio
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
