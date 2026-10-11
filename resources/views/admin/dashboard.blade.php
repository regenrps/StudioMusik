@php
    use App\Enums\StatusPeminjaman as S;
    $user = auth()->user();
@endphp
<x-layouts.app title="Dasbor">
    <x-page-header :title="'Halo, '.explode(' ', trim($user->username))[0]" :description="tanggal_lengkap(today()).' · Ringkasan operasional studio.'">
        <x-button :href="route('admin.peminjaman.kalender')" variant="secondary" icon="heroicon-o-calendar">Kalender</x-button>
        @if ($user->isAdmin())
            <x-button :href="route('admin.peminjaman.index', ['status' => 'pending'])" icon="heroicon-m-inbox-arrow-down">Tinjau pengajuan</x-button>
        @else
            <x-button :href="route('admin.laporan.create')" icon="heroicon-m-plus">Buat laporan</x-button>
        @endif
    </x-page-header>

    {{-- KPI --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Menunggu persetujuan" :value="$kpi['menunggu']" icon="heroicon-o-inbox-arrow-down" tone="amber"
            :href="route('admin.peminjaman.index', ['status' => 'pending'])" :hint="$kpi['menunggu'] ? 'Perlu ditinjau sebelum jadwal dimulai' : 'Semua pengajuan sudah diproses'" />
        <x-stat label="Jadwal hari ini" :value="$kpi['hari_ini']" icon="heroicon-o-calendar-days" tone="sky" :href="route('admin.peminjaman.kalender')" hint="Disetujui, menunggu & berlangsung" />
        <x-stat label="Utilisasi minggu ini" :value="$kpi['utilisasi'].'%'" icon="heroicon-o-chart-bar" tone="violet" hint="Dari total jam operasional" />
        @if ($user->isAdmin())
            <x-stat label="Pesanan jasa menunggu" :value="$kpi['jasa_menunggu']" icon="heroicon-o-musical-note" tone="brand" :href="route('admin.jasa.index', ['status' => 'pending'])" :hint="$kpi['jasa_aktif'].' pesanan sedang berjalan'" />
        @else
            <x-stat label="Laporan baru" :value="$kpi['laporan']" icon="heroicon-o-exclamation-triangle" tone="rose" :href="route('admin.laporan.index', ['status' => 'open'])" hint="Belum ditangani" />
        @endif
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            {{-- Sedang berlangsung --}}
            @if ($berlangsung->isNotEmpty())
                <div class="card border-violet-200 bg-gradient-to-r from-violet-50 to-white p-5 dark:border-violet-500/30 dark:from-violet-500/10 dark:to-zinc-900">
                    <div class="flex items-center gap-2">
                        <span class="relative flex size-2.5"><span class="absolute inline-flex size-full animate-ping rounded-full bg-violet-400 opacity-75"></span><span class="relative inline-flex size-2.5 rounded-full bg-violet-500"></span></span>
                        <p class="text-sm font-semibold text-violet-900 dark:text-violet-100">Studio sedang dipakai</p>
                    </div>
                    <div class="mt-3 space-y-2">
                        @foreach ($berlangsung as $p)
                            <a href="{{ route('admin.peminjaman.show', $p) }}" class="flex items-center gap-3 rounded-xl bg-white/80 p-3 ring-1 ring-violet-100 hover:ring-violet-300 dark:bg-zinc-900/60 dark:ring-violet-500/20">
                                <x-avatar :user="$p->user" size="sm" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $p->user->username }}</p>
                                    <p class="text-xs text-zinc-500">{{ $p->ruangan->nama_ruangan }} · check-in {{ $p->checkin_at?->format('H.i') }} · s/d {{ jam($p->waktu_selesai) }}</p>
                                </div>
                                @if ($p->selesai->isPast())
                                    <x-badge tone="rose" size="sm">Lewat waktu</x-badge>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Antrean persetujuan --}}
            <x-card title="Antrean persetujuan" description="Diurutkan dari jadwal terdekat." icon="heroicon-o-inbox-arrow-down" :padding="false">
                <x-slot:actions>
                    <a href="{{ route('admin.peminjaman.index', ['status' => 'pending']) }}" class="text-sm font-semibold text-brand-600 hover:underline dark:text-brand-400">Semua</a>
                </x-slot:actions>
                @forelse ($antrean as $p)
                    <div class="flex flex-col gap-3 border-b border-zinc-100 px-5 py-4 last:border-0 sm:flex-row sm:items-center sm:px-6 dark:border-zinc-800">
                        <a href="{{ route('admin.peminjaman.show', $p) }}" class="flex min-w-0 flex-1 items-center gap-3">
                            <x-avatar :user="$p->user" size="sm" />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-zinc-900 hover:text-brand-600 dark:text-white">{{ $p->user->username }}</p>
                                <p class="text-xs text-zinc-500">
                                    {{ tanggal($p->tgl_pinjam, 'D, d M') }} · {{ jam($p->waktu_mulai) }}–{{ jam($p->waktu_selesai) }} · {{ $p->jumlah_orang }} org
                                    @if ($p->mulai->isToday())<span class="font-semibold text-amber-600"> · hari ini</span>@endif
                                </p>
                                <p class="mt-0.5 truncate text-xs text-zinc-400">{{ $p->ket_keperluan }}</p>
                            </div>
                        </a>
                        @if ($user->isAdmin())
                            <div class="flex shrink-0 gap-2">
                                <x-button :href="route('admin.peminjaman.show', $p)" variant="secondary" size="sm">Tinjau</x-button>
                                <form method="POST" action="{{ route('admin.peminjaman.setujui', $p) }}">
                                    @csrf
                                    <input type="hidden" name="kembali" value="{{ url()->current() }}">
                                    <x-button type="submit" variant="success" size="sm" icon="heroicon-m-check">Setujui</x-button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <x-empty compact icon="heroicon-o-check-badge" title="Tidak ada antrean" description="Semua pengajuan sudah diproses. Kerja bagus!" />
                @endforelse
            </x-card>

            {{-- Grafik --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-5" x-data="grafikDashboard(@js($grafik))">
                <x-card title="Tren peminjaman 30 hari" class="lg:col-span-3">
                    <div class="h-64"><canvas x-ref="tren" aria-label="Grafik tren peminjaman"></canvas></div>
                </x-card>
                <x-card title="Status bulan ini" class="lg:col-span-2">
                    @if (count($grafik['status']))
                        <div class="h-64"><canvas x-ref="komposisi" aria-label="Grafik komposisi status"></canvas></div>
                    @else
                        <x-empty compact icon="heroicon-o-chart-pie" title="Belum ada data bulan ini" />
                    @endif
                </x-card>
            </div>
        </div>

        <div class="space-y-6">
            {{-- Jadwal hari ini --}}
            <x-card title="Jadwal hari ini" :padding="false">
                <x-slot:actions>
                    <span class="text-xs font-medium text-zinc-500">{{ jam($aturan['jam_buka']) }}–{{ jam($aturan['jam_tutup']) }}</span>
                </x-slot:actions>
                @forelse ($hariIni as $p)
                    <a href="{{ route('admin.peminjaman.show', $p) }}" class="flex gap-3 border-b border-zinc-100 px-5 py-3 last:border-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/40">
                        <div class="w-12 shrink-0 text-xs font-semibold text-zinc-500 tabular-nums">{{ jam($p->waktu_mulai) }}<br><span class="font-normal text-zinc-400">{{ jam($p->waktu_selesai) }}</span></div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $p->user->username }}</p>
                            <x-status :status="$p->status" size="sm" pendek class="mt-1" />
                        </div>
                    </a>
                @empty
                    <x-empty compact icon="heroicon-o-calendar" title="Tidak ada jadwal hari ini" />
                @endforelse
            </x-card>

            {{-- Tenggat jasa --}}
            @if ($jasa)
                <x-card title="Tenggat jasa terdekat" :padding="false">
                    <x-slot:actions>
                        <a href="{{ route('admin.jasa.index') }}" class="text-sm font-semibold text-brand-600 hover:underline dark:text-brand-400">Semua</a>
                    </x-slot:actions>
                    @forelse ($jasa as $j)
                        <a href="{{ route('admin.jasa.show', $j) }}" class="flex items-center gap-3 border-b border-zinc-100 px-5 py-3 last:border-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/40">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $j->judul }}</p>
                                <p class="text-xs text-zinc-500">{{ $j->jasa->nama_jenis_jasa }} · {{ $j->user->username }}</p>
                            </div>
                            @php $sisa = $j->sisaHari(); @endphp
                            <x-badge size="sm" :tone="$sisa < 0 ? 'rose' : ($sisa <= 3 ? 'amber' : 'zinc')">
                                {{ $sisa < 0 ? 'Telat '.abs($sisa).'h' : ($sisa === 0 ? 'Hari ini' : $sisa.' hari') }}
                            </x-badge>
                        </a>
                    @empty
                        <x-empty compact icon="heroicon-o-musical-note" title="Tidak ada pesanan aktif" />
                    @endforelse
                </x-card>
            @endif

            {{-- Aktivitas --}}
            <x-card title="Aktivitas terbaru" :padding="false">
                @if ($user->isAdmin())
                    <x-slot:actions>
                        <a href="{{ route('admin.log.index') }}" class="text-sm font-semibold text-brand-600 hover:underline dark:text-brand-400">Log</a>
                    </x-slot:actions>
                @endif
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($aktivitas as $a)
                        <li class="flex gap-3 px-5 py-3">
                            <x-avatar :nama="$a->user?->username ?? 'Sistem'" size="xs" class="mt-0.5" />
                            <div class="min-w-0">
                                <p class="text-sm text-zinc-700 dark:text-zinc-300"><span class="font-semibold text-zinc-900 dark:text-white">{{ $a->user?->username ?? 'Sistem' }}</span> · {{ $a->deskripsi }}</p>
                                <p class="text-xs text-zinc-400">{{ relatif($a->created_at) }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-zinc-500">Belum ada aktivitas.</li>
                    @endforelse
                </ul>
            </x-card>
        </div>
    </div>
</x-layouts.app>
