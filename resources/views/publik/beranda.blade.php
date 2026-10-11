@php
    $user = auth()->user();
    $ctaPinjam = $user && ! $user->isStaff() ? route('akun.peminjaman.create') : ($user ? route('admin.dashboard') : route('register'));
    $utama = $ruangan->first();

    $statusHariIni = match ($hariIni['status'] ?? null) {
        'tersedia' => ['emerald', 'Slot hari ini masih tersedia'],
        'sebagian' => ['amber', 'Sebagian slot hari ini terisi'],
        'penuh' => ['rose', 'Slot hari ini penuh'],
        'tutup' => ['zinc', 'Studio tutup hari ini'],
        'libur' => ['zinc', 'Hari ini studio tidak beroperasi'],
        default => ['zinc', 'Pemesanan hari ini sudah ditutup'],
    };
    if ($sedangDipakai) {
        $statusHariIni = ['violet', 'Studio sedang dipakai sekarang'];
    }
    $titikStatus = [
        'emerald' => ['bg-emerald-400', 'bg-emerald-500'],
        'amber' => ['bg-amber-400', 'bg-amber-500'],
        'rose' => ['bg-rose-400', 'bg-rose-500'],
        'violet' => ['bg-violet-400', 'bg-violet-500'],
        'zinc' => ['bg-zinc-300', 'bg-zinc-400'],
    ][$statusHariIni[0]];

    $langkah = [
        ['heroicon-o-calendar-days', 'Pilih jadwal', 'Lihat kalender ketersediaan dan pilih jam yang masih kosong — tanpa perlu menebak.'],
        ['heroicon-o-identification', 'Ajukan & unggah KTM', 'Isi keperluan dan foto KTM/KTP sebagai jaminan. Hanya butuh satu menit.'],
        ['heroicon-o-check-badge', 'Tunggu persetujuan', 'Admin meninjau pengajuan. Anda mendapat notifikasi email & di dasbor.'],
        ['heroicon-o-camera', 'Check-in & check-out', 'Foto kondisi studio saat datang dan pulang. Selesai, beri ulasan.'],
    ];

    $faq = [
        ['Siapa yang boleh meminjam studio?', 'Seluruh civitas akademika ITERA (mahasiswa, dosen, dan tenaga kependidikan) yang memiliki akun di sistem ini.'],
        ['Apakah peminjaman studio berbayar?', 'Tidak. Peminjaman studio gratis untuk civitas ITERA. Anda cukup menyerahkan KTM/KTP sebagai jaminan dan mematuhi SOP.'],
        ['Kapan studio bisa dipinjam?', 'Hari '.$aturan['label_hari'].', pukul '.jam($aturan['jam_buka']).'–'.jam($aturan['jam_tutup']).' WIB, dengan durasi '.durasi_label($aturan['durasi_min_menit']).' hingga '.durasi_label($aturan['durasi_maks_menit']).' per pengajuan. Studio tutup saat UTS/UAS dan kegiatan UKMBSM.'],
        ['Berapa lama pengajuan diproses?', 'Admin meninjau pengajuan secepatnya. Ajukan paling lambat '.durasi_label($aturan['jeda_minimal_menit']).' sebelum jadwal. Pengajuan yang belum diproses hingga jadwal dimulai otomatis kedaluwarsa.'],
        ['Bagaimana jika saya batal datang?', 'Batalkan lewat menu Peminjaman Saya paling lambat '.pengaturan('batas_pembatalan_jam').' jam sebelum jadwal agar slot bisa dipakai orang lain. Tidak check-in pada jadwal yang disetujui akan tercatat "Tidak Hadir".'],
        ['Bagaimana cara memesan jasa musik?', 'Pilih layanan (jingle, aransemen, minus one, dll), isi detail kebutuhan, unggah referensi, dan tentukan tenggat. Pantau progresnya hingga hasil siap diunduh.'],
    ];
@endphp

<x-layouts.publik title="Peminjaman Studio & Jasa Musik" transparan>
    @if ($pengumuman)
        <div class="bg-brand-600 text-white">
            <div class="mx-auto flex max-w-7xl items-center justify-center gap-2 px-4 py-2.5 text-center text-sm font-medium">
                <x-heroicon-s-megaphone class="size-4 shrink-0" /> {{ $pengumuman }}
            </div>
        </div>
    @endif

    {{-- HERO --}}
    <section class="relative overflow-hidden">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute -top-40 left-1/2 h-[36rem] w-[60rem] -translate-x-1/2 rounded-full bg-brand-400/20 blur-3xl dark:bg-brand-600/20"></div>
            <div class="absolute top-40 -right-20 h-72 w-72 rounded-full bg-amber-300/20 blur-3xl dark:bg-amber-500/10"></div>
        </div>

        <div class="mx-auto grid grid-cols-1 max-w-7xl items-center gap-12 px-4 pt-10 pb-16 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8 lg:pt-16 lg:pb-24">
            <div class="animate-slide-up">
                <span @class(['inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1.5 text-xs font-semibold shadow-sm ring-1 ring-zinc-200 backdrop-blur dark:bg-zinc-900/80 dark:ring-zinc-800'])>
                    <span class="relative flex size-2">
                        <span class="absolute inline-flex size-full animate-ping rounded-full opacity-60 {{ $titikStatus[0] }}"></span>
                        <span class="relative inline-flex size-2 rounded-full {{ $titikStatus[1] }}"></span>
                    </span>
                    <span class="text-zinc-700 dark:text-zinc-300">{{ $statusHariIni[1] }}</span>
                </span>

                <h1 class="mt-6 text-4xl leading-[1.1] font-extrabold tracking-tight text-zinc-900 sm:text-5xl lg:text-6xl dark:text-white">
                    Pinjam studio musik
                    <span class="bg-gradient-to-r from-brand-600 via-violet-500 to-fuchsia-500 bg-clip-text text-transparent dark:from-brand-400 dark:via-violet-300 dark:to-fuchsia-300">tanpa ribet.</span>
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-relaxed text-zinc-600 dark:text-zinc-400">
                    Cek slot kosong secara langsung, ajukan dalam hitungan detik, dan pantau status hingga selesai. Studio full band gratis untuk seluruh civitas ITERA.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <x-button :href="$ctaPinjam" size="lg" icon="heroicon-m-calendar-days">Pinjam Studio Sekarang</x-button>
                    <x-button :href="route('jadwal')" variant="secondary" size="lg" icon="heroicon-o-eye">Lihat Jadwal</x-button>
                </div>

                <dl class="mt-10 grid max-w-lg grid-cols-3 gap-4 border-t border-zinc-200 pt-6 dark:border-zinc-800">
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Jam buka</dt>
                        <dd class="mt-1 text-sm font-bold text-zinc-900 dark:text-white">{{ jam($aturan['jam_buka']) }}–{{ jam($aturan['jam_tutup']) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Hari</dt>
                        <dd class="mt-1 text-sm font-bold text-zinc-900 dark:text-white">{{ $aturan['label_hari'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Kapasitas</dt>
                        <dd class="mt-1 text-sm font-bold text-zinc-900 dark:text-white">Maks. {{ $aturan['maks_orang'] }} orang</dd>
                    </div>
                </dl>
            </div>

            {{-- Visual + ketersediaan hari ini --}}
            <div class="relative animate-fade-in">
                <div class="relative overflow-hidden rounded-3xl shadow-2xl ring-1 ring-zinc-900/10 dark:ring-white/10">
                    <img src="{{ $utama?->thumbnail_url ?? asset('assets/images/welcome-hero/welcome-banner.jpg') }}" alt="Ruang {{ $utama?->nama_ruangan ?? 'studio' }}"
                        class="aspect-[4/3] w-full object-cover" fetchpriority="high">
                    <div class="absolute inset-0 bg-gradient-to-t from-zinc-950/70 via-transparent"></div>
                    <div class="absolute bottom-4 left-4 text-white">
                        <p class="text-xs font-semibold tracking-wider text-white/70 uppercase">{{ $utama?->lokasi ?? config('studio.lokasi') }}</p>
                        <p class="text-lg font-bold">{{ $utama?->nama_ruangan ?? config('studio.nama') }}</p>
                    </div>
                </div>

                @if ($hariIni && in_array($hariIni['status'], ['tersedia', 'sebagian', 'penuh'], true))
                    @php
                        $b = (int) substr($hariIni['buka'], 0, 2) * 60 + (int) substr($hariIni['buka'], 3, 2);
                        $t = (int) substr($hariIni['tutup'], 0, 2) * 60 + (int) substr($hariIni['tutup'], 3, 2);
                        $pos = fn ($j) => (((int) substr($j, 0, 2) * 60 + (int) substr($j, 3, 2)) - $b) / max(1, $t - $b) * 100;
                    @endphp
                    <div class="card absolute -bottom-8 left-4 right-4 p-4 sm:left-auto sm:-right-4 sm:w-80">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">Hari ini</p>
                            <a href="{{ route('jadwal') }}" class="text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400">Jadwal lengkap →</a>
                        </div>
                        <div class="relative mt-3 h-8 overflow-hidden rounded-lg bg-emerald-50 ring-1 ring-emerald-200/60 ring-inset dark:bg-emerald-500/10 dark:ring-emerald-500/20">
                            @foreach ($hariIni['terisi'] as $slot)
                                <div class="absolute inset-y-0 border-x border-white/60 dark:border-zinc-900/60 {{ $slot['jenis'] === 'tutup' ? 'bg-zinc-300 dark:bg-zinc-700' : ($slot['status'] === 'pending' ? 'bg-amber-300 dark:bg-amber-500/60' : 'bg-brand-400 dark:bg-brand-500/70') }}"
                                    style="left: {{ $pos($slot['mulai']) }}%; width: {{ $pos($slot['selesai']) - $pos($slot['mulai']) }}%" title="{{ $slot['mulai'] }}–{{ $slot['selesai'] }} · {{ $slot['label'] }}"></div>
                            @endforeach
                        </div>
                        <div class="mt-1.5 flex justify-between text-[11px] font-medium text-zinc-400 tabular-nums">
                            <span>{{ jam($hariIni['buka']) }}</span><span>{{ jam($hariIni['tutup']) }}</span>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-zinc-500">
                            <span class="inline-flex items-center gap-1"><span class="size-2 rounded-sm bg-emerald-200 dark:bg-emerald-500/40"></span>Kosong</span>
                            <span class="inline-flex items-center gap-1"><span class="size-2 rounded-sm bg-brand-400"></span>Terisi</span>
                            <span class="inline-flex items-center gap-1"><span class="size-2 rounded-sm bg-amber-300"></span>Menunggu</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- STATISTIK --}}
    <section class="border-y border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900/40">
        <div class="mx-auto grid max-w-7xl grid-cols-2 gap-6 px-4 py-10 sm:px-6 md:grid-cols-4 lg:px-8">
            @foreach ([
                [number_format($statistik['peminjaman']), 'Sesi studio terlaksana', 'heroicon-o-musical-note'],
                [number_format($statistik['jasa']), 'Karya jasa musik selesai', 'heroicon-o-sparkles'],
                [$statistik['rating'] ? number_format($statistik['rating'], 1, ',', '.').' / 5' : '—', 'Rata-rata kepuasan', 'heroicon-o-star'],
                ['Gratis', 'Untuk seluruh civitas ITERA', 'heroicon-o-academic-cap'],
            ] as [$nilai, $label, $ikon])
                <div class="flex items-center gap-4">
                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                        <x-dynamic-component :component="$ikon" class="size-6" />
                    </span>
                    <div>
                        <p class="text-2xl font-extrabold tracking-tight text-zinc-900 tabular-nums dark:text-white">{{ $nilai }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- CARA KERJA --}}
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-bold tracking-widest text-brand-600 uppercase dark:text-brand-400">Alur peminjaman</p>
            <h2 class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">Empat langkah, tanpa antre di sekretariat</h2>
        </div>
        <ol class="mt-14 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($langkah as $i => [$ikon, $judul, $teks])
                <li class="card relative p-6">
                    <span class="absolute top-5 right-5 text-5xl font-black text-zinc-100 dark:text-zinc-800">{{ $i + 1 }}</span>
                    <span class="relative grid size-12 place-items-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-lg shadow-brand-600/25">
                        <x-dynamic-component :component="$ikon" class="size-6" />
                    </span>
                    <h3 class="relative mt-5 font-semibold text-zinc-900 dark:text-white">{{ $judul }}</h3>
                    <p class="relative mt-2 text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $teks }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- RUANGAN --}}
    <section class="bg-white py-20 dark:bg-zinc-900/40">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p class="text-sm font-bold tracking-widest text-brand-600 uppercase dark:text-brand-400">Ruangan</p>
                    <h2 class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Fasilitas studio</h2>
                </div>
                <x-button :href="route('jadwal')" variant="secondary" icon-right="heroicon-m-arrow-right">Cek ketersediaan</x-button>
            </div>

            @if ($ruangan->isEmpty())
                <x-empty class="mt-10" icon="heroicon-o-building-office-2" title="Belum ada ruangan" description="Ruangan studio akan tampil di sini setelah ditambahkan admin." />
            @else
                @php $tunggal = $ruangan->count() === 1; @endphp
                <div @class(['mt-10 grid grid-cols-1 gap-6', 'md:grid-cols-2' => ! $tunggal])>
                    @foreach ($ruangan as $r)
                        <article @class(['card group flex flex-col overflow-hidden', 'lg:flex-row' => $tunggal])>
                            <a href="{{ route('ruangan.show', $r) }}" @class(['relative block overflow-hidden', 'lg:w-3/5 lg:shrink-0' => $tunggal])>
                                <img src="{{ $r->thumbnail_url }}" alt="{{ $r->nama_ruangan }}" loading="lazy" @class(['aspect-[16/9] w-full object-cover transition duration-500 group-hover:scale-105', 'lg:h-full' => $tunggal])>
                                <x-badge tone="emerald" class="absolute top-4 left-4" dot>Gratis untuk civitas</x-badge>
                            </a>
                            <div @class(['flex flex-1 flex-col p-6', 'lg:justify-center lg:p-10' => $tunggal])>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 @class(['font-semibold text-zinc-900 dark:text-white', 'text-lg' => ! $tunggal, 'text-2xl' => $tunggal])>{{ $r->nama_ruangan }}</h3>
                                        <p class="mt-1 flex items-center gap-1.5 text-sm text-zinc-500"><x-heroicon-m-map-pin class="size-4" />{{ $r->lokasi ?? config('studio.lokasi') }}</p>
                                    </div>
                                    <span class="inline-flex items-center gap-1 rounded-lg bg-zinc-100 px-2 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                        <x-heroicon-m-user-group class="size-4" /> {{ $r->kapasitas }} org
                                    </span>
                                </div>
                                @if ($tunggal && $r->deskripsi)
                                    <div class="prose-konten mt-4 line-clamp-4">{!! konten($r->deskripsi) !!}</div>
                                @endif
                                @if ($r->daftar_fasilitas)
                                    <ul class="mt-4 flex flex-wrap gap-2">
                                        @foreach (array_slice($r->daftar_fasilitas, 0, 6) as $f)
                                            <li class="rounded-lg bg-zinc-50 px-2.5 py-1 text-xs font-medium text-zinc-600 ring-1 ring-zinc-200 ring-inset dark:bg-zinc-800/60 dark:text-zinc-300 dark:ring-zinc-700">{{ $f }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                <div class="mt-6 flex gap-3 pt-2">
                                    <x-button :href="route('ruangan.show', $r)" variant="secondary" class="flex-1">Detail</x-button>
                                    <x-button :href="$user && ! $user->isStaff() ? route('akun.peminjaman.create', ['ruangan' => $r->slug]) : $ctaPinjam" class="flex-1">Pinjam</x-button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- JASA MUSIK --}}
    @if ($layanan->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                <div class="max-w-2xl">
                    <p class="text-sm font-bold tracking-widest text-brand-600 uppercase dark:text-brand-400">Jasa musik</p>
                    <h2 class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Butuh musik untuk acaramu?</h2>
                    <p class="mt-3 text-zinc-500 dark:text-zinc-400">Tim komposer UKMBSM ITERA siap membuatkan jingle, aransemen, hingga minus one sesuai kebutuhan.</p>
                </div>
                <x-button :href="route('layanan.index')" variant="secondary" icon-right="heroicon-m-arrow-right">Semua layanan</x-button>
            </div>
            <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($layanan as $jasa)
                    <a href="{{ route('layanan.show', $jasa) }}" class="card group overflow-hidden transition hover:-translate-y-1 hover:shadow-lift">
                        <div class="overflow-hidden">
                            <img src="{{ $jasa->gambar_url }}" alt="{{ $jasa->nama_jenis_jasa }}" loading="lazy" class="aspect-[16/10] w-full object-cover transition duration-500 group-hover:scale-105">
                        </div>
                        <div class="p-5">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $jasa->nama_jenis_jasa }}</h3>
                                <span class="text-xs font-medium text-zinc-500">± {{ $jasa->estimasi_hari }} hari</span>
                            </div>
                            <p class="mt-2 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $jasa->ringkasan ?? str(strip_tags((string) $jasa->deskripsi))->limit(120) }}</p>
                            <p class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-600 dark:text-brand-400">
                                Lihat detail <x-heroicon-m-arrow-right class="size-4 transition group-hover:translate-x-0.5" />
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ULASAN --}}
    @if (count($ulasan))
        <section class="bg-zinc-900 py-20 text-white dark:bg-zinc-900/60">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <p class="text-sm font-bold tracking-widest text-brand-300 uppercase">Kata mereka</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight">Ulasan pengguna studio</h2>
                <div class="mt-10 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($ulasan as $u)
                        <figure class="flex flex-col rounded-2xl bg-white/5 p-6 ring-1 ring-white/10">
                            <x-rating :nilai="$u['rating']" />
                            <blockquote class="mt-4 flex-1 text-sm leading-relaxed text-zinc-200">“{{ $u['teks'] }}”</blockquote>
                            <figcaption class="mt-5 flex items-center gap-3">
                                <x-avatar :nama="$u['nama']" size="sm" class="ring-zinc-900" />
                                <div>
                                    <p class="text-sm font-semibold">{{ $u['nama'] }}</p>
                                    <p class="text-xs text-zinc-400">{{ $u['jenis'] }}</p>
                                </div>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- SOP & FAQ --}}
    <section id="sop" class="mx-auto grid grid-cols-1 max-w-7xl scroll-mt-20 gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:px-8">
        <div>
            <p class="text-sm font-bold tracking-widest text-brand-600 uppercase dark:text-brand-400">Aturan main</p>
            <h2 class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">SOP peminjaman studio</h2>
            <ol class="mt-8 space-y-4">
                @foreach (config('studio.sop') as $i => $aturanSop)
                    <li class="flex gap-4">
                        <span class="grid size-7 shrink-0 place-items-center rounded-full bg-brand-100 text-xs font-bold text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">{{ $i + 1 }}</span>
                        <p class="pt-0.5 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">{{ $aturanSop }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
        <div>
            <p class="text-sm font-bold tracking-widest text-brand-600 uppercase dark:text-brand-400">FAQ</p>
            <h2 class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Pertanyaan umum</h2>
            <div class="mt-8 divide-y divide-zinc-200 rounded-2xl border border-zinc-200 bg-white dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-900" x-data="{ buka: 0 }">
                @foreach ($faq as $i => [$tanya, $jawab])
                    <div>
                        <button type="button" @click="buka = buka === {{ $i }} ? null : {{ $i }}" :aria-expanded="buka === {{ $i }}"
                            class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left text-sm font-semibold text-zinc-900 dark:text-white">
                            {{ $tanya }}
                            <x-heroicon-m-chevron-down class="size-5 shrink-0 text-zinc-400 transition" x-bind:class="buka === {{ $i }} && 'rotate-180'" />
                        </button>
                        <div x-show="buka === {{ $i }}" x-collapse @if ($i !== 0) x-cloak @endif>
                            <p class="px-5 pb-5 text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $jawab }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="px-4 pb-20 sm:px-6 lg:px-8">
        <div class="relative mx-auto max-w-7xl overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-700 to-violet-800 px-6 py-14 text-center text-white shadow-2xl sm:px-12">
            <div class="pointer-events-none absolute -top-24 -right-24 size-72 rounded-full bg-white/10 blur-2xl"></div>
            <h2 class="relative text-3xl font-bold tracking-tight sm:text-4xl">Siap ngejam hari ini?</h2>
            <p class="relative mx-auto mt-4 max-w-xl text-white/80">Cek slot yang masih kosong dan amankan jadwalmu sebelum diambil orang lain.</p>
            <div class="relative mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <x-button :href="$ctaPinjam" variant="white" size="lg" icon="heroicon-m-calendar-days">{{ $user ? 'Pinjam Studio' : 'Daftar & Pinjam' }}</x-button>
                <x-button :href="route('jadwal')" size="lg" class="bg-white/10 shadow-none ring-1 ring-white/30 hover:bg-white/20">Lihat Jadwal</x-button>
            </div>
        </div>
    </section>
</x-layouts.publik>
