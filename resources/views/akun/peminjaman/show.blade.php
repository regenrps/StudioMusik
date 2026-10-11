@php
    use App\Enums\StatusPeminjaman as S;

    $p = $peminjaman;
    $tahap = match ($p->status) {
        S::Menunggu => 1,
        S::Disetujui => 2,
        S::Berlangsung => 3,
        S::Selesai => 4,
        default => 0,
    };
    $langkah = ['Diajukan', 'Disetujui', 'Check-in', 'Selesai'];
    $waAdmin = wa_link(config('studio.kontak.whatsapp'), 'Halo admin Studio Musik ITERA, saya ingin bertanya tentang peminjaman '.$p->kode.'.');
@endphp

<x-layouts.app :title="'Peminjaman '.$p->kode">
    <x-page-header :title="$p->ruangan->nama_ruangan" :kembali="route('akun.peminjaman.index')" kembali-label="Peminjaman saya">
        <x-slot:meta>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <x-status :status="$p->status" />
                <button type="button" x-data @click="$salin(@js($p->kode), 'Kode peminjaman disalin')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-1 font-mono text-xs font-semibold text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                    {{ $p->kode }} <x-heroicon-o-clipboard-document class="size-3.5" />
                </button>
            </div>
        </x-slot:meta>
        @if ($p->bisaDibatalkanPeminjam())
            <x-button variant="danger-soft" icon="heroicon-o-x-circle" x-data @click="$dispatch('buka-modal', 'batal')">Batalkan</x-button>
        @endif
    </x-page-header>

    @if (session('baru'))
        <x-alert tone="success" title="Pengajuan berhasil dikirim" class="mb-6">
            Simpan kode <strong>{{ $p->kode }}</strong>. Admin akan meninjau pengajuan Anda; kami akan mengirim email dan notifikasi begitu ada keputusan.
        </x-alert>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_360px]">
        <div class="min-w-0 space-y-6">
            {{-- Progres --}}
            @if ($tahap > 0)
                <div class="card p-5 sm:p-6">
                    <ol class="flex items-center">
                        @foreach ($langkah as $i => $label)
                            @php $n = $i + 1; @endphp
                            <li @class(['flex items-center', 'flex-1' => ! $loop->last])>
                                <div class="flex flex-col items-center gap-1.5">
                                    <span @class([
                                        'grid size-8 place-items-center rounded-full text-xs font-bold ring-4',
                                        'bg-brand-600 text-white ring-brand-100 dark:ring-brand-500/20' => $n < $tahap || ($n === $tahap && $tahap === 4),
                                        'bg-white text-brand-700 ring-brand-200 border-2 border-brand-600 dark:bg-zinc-900 dark:text-brand-300 dark:ring-brand-500/20' => $n === $tahap && $tahap !== 4,
                                        'bg-zinc-100 text-zinc-400 ring-transparent dark:bg-zinc-800' => $n > $tahap,
                                    ])>
                                        @if ($n < $tahap || $tahap === 4)<x-heroicon-m-check class="size-4" />@else{{ $n }}@endif
                                    </span>
                                    <span @class(['text-[11px] font-semibold sm:text-xs', 'text-zinc-900 dark:text-white' => $n <= $tahap, 'text-zinc-400' => $n > $tahap])>{{ $label }}</span>
                                </div>
                                @unless ($loop->last)
                                    <span @class(['mx-1 mb-5 h-0.5 flex-1 rounded-full sm:mx-2', 'bg-brand-600' => $n < $tahap, 'bg-zinc-200 dark:bg-zinc-800' => $n >= $tahap])></span>
                                @endunless
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif

            {{-- Panel aksi sesuai status --}}
            @switch($p->status)
                @case(S::Menunggu)
                    <x-alert tone="warning" title="Menunggu persetujuan admin" icon="heroicon-s-clock">
                        Pengajuan diterima {{ relatif($p->created_at) }}. Jika belum diproses hingga jam mulai, pengajuan otomatis kedaluwarsa.
                        Butuh cepat? <a href="{{ $waAdmin }}" target="_blank" rel="noopener" class="font-semibold underline">Hubungi admin</a>.
                    </x-alert>
                    @break

                @case(S::Disetujui)
                    <div class="card overflow-hidden" x-data="hitungMundur({ mulai: @js($p->mulai->toIso8601String()), selesai: @js($p->selesai->toIso8601String()) })">
                        <div class="bg-gradient-to-r from-sky-500 to-brand-600 px-5 py-4 text-white sm:px-6">
                            <p class="text-sm font-medium text-white/80">Peminjaman disetujui</p>
                            <p class="mt-0.5 text-lg font-bold" x-text="teks">&nbsp;</p>
                        </div>
                        <div class="p-5 sm:p-6">
                            @if ($p->ket_admin)
                                <p class="mb-4 rounded-xl bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:bg-sky-500/10 dark:text-sky-100"><strong>Catatan admin:</strong> {{ $p->ket_admin }}</p>
                            @endif
                            @if ($p->bisaCheckIn())
                                <form method="POST" action="{{ route('akun.peminjaman.checkin', $p) }}" enctype="multipart/form-data" class="space-y-4">
                                    @csrf
                                    <div>
                                        <h3 class="font-semibold text-zinc-900 dark:text-white">Check-in sekarang</h3>
                                        <p class="mt-1 text-sm text-zinc-500">Foto kondisi studio saat Anda tiba (alat, lantai, kabel). Foto ini melindungi Anda jika ada kerusakan sebelumnya.</p>
                                    </div>
                                    <x-file-input name="foto" required accept="image/*" ikon="heroicon-o-camera" judul="Ambil / pilih foto kondisi awal" />
                                    <x-button type="submit" size="lg" class="w-full" icon="heroicon-m-arrow-right-end-on-rectangle">Check-in & mulai pakai studio</x-button>
                                </form>
                            @else
                                <div class="flex items-start gap-3 text-sm text-zinc-600 dark:text-zinc-300">
                                    <x-heroicon-o-information-circle class="size-5 shrink-0 text-sky-500" />
                                    <p>Tombol check-in muncul di halaman ini mulai <strong>{{ $p->mulai->copy()->subMinutes(15)->format('H.i') }}</strong> pada {{ tanggal($p->tgl_pinjam, 'l, d M Y') }}. Siapkan ponsel untuk memotret kondisi studio.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                    @break

                @case(S::Berlangsung)
                    <div class="card overflow-hidden" x-data="hitungMundur({ mulai: @js($p->mulai->toIso8601String()), selesai: @js($p->selesai->toIso8601String()) })">
                        <div class="bg-gradient-to-r from-violet-500 to-fuchsia-600 px-5 py-4 text-white sm:px-6">
                            <p class="text-sm font-medium text-white/80">Studio sedang Anda pakai · check-in {{ $p->checkin_at?->format('H.i') }}</p>
                            <p class="mt-0.5 text-lg font-bold" x-text="teks">&nbsp;</p>
                        </div>
                        <form method="POST" action="{{ route('akun.peminjaman.checkout', $p) }}" enctype="multipart/form-data" class="space-y-4 p-5 sm:p-6">
                            @csrf
                            <div>
                                <h3 class="font-semibold text-zinc-900 dark:text-white">Selesai? Check-out di sini</h3>
                                <p class="mt-1 text-sm text-zinc-500">Rapikan alat ke posisi semula, matikan listrik & AC, lalu foto kondisi akhir studio.</p>
                            </div>
                            <x-file-input name="foto" required accept="image/*" ikon="heroicon-o-camera" judul="Ambil / pilih foto kondisi akhir" />
                            <x-textarea name="catatan" label="Catatan (opsional)" rows="2" placeholder="Mis. senar gitar putus, kabel jack kurang satu" maxlength="500" />
                            <x-button type="submit" variant="success" size="lg" class="w-full" icon="heroicon-m-check-circle">Check-out & selesaikan</x-button>
                        </form>
                    </div>
                    @break

                @case(S::Selesai)
                    @if ($p->bisaDiulas())
                        <x-card title="Bagaimana pengalaman Anda?" description="Ulasan membantu pengelola meningkatkan fasilitas studio." icon="heroicon-o-star">
                            <form method="POST" action="{{ route('akun.peminjaman.ulasan', $p) }}" class="space-y-4">
                                @csrf
                                <x-rating-input name="rating" />
                                <x-textarea name="review" rows="3" placeholder="Ceritakan pengalaman Anda (opsional)" maxlength="1000" />
                                <x-button type="submit" class="w-full">Kirim ulasan</x-button>
                            </form>
                        </x-card>
                    @else
                        <x-alert tone="success" title="Peminjaman selesai">
                            Terima kasih telah menjaga studio! @if ($p->rating) Ulasan Anda: <x-rating :nilai="$p->rating" ukuran="size-3.5" class="ml-1 align-middle" /> @endif
                            @if ($p->review)<span class="mt-1 block italic">“{{ $p->review }}”</span>@endif
                        </x-alert>
                    @endif
                    @break

                @case(S::Ditolak)
                    <x-alert tone="danger" title="Pengajuan ditolak">
                        Alasan: {{ $p->ket_admin ?: 'tidak disebutkan' }}
                        <div class="mt-3"><x-button :href="route('akun.peminjaman.create', ['ruangan' => $p->ruangan->slug])" size="sm" icon="heroicon-m-plus">Ajukan jadwal lain</x-button></div>
                    </x-alert>
                    @break

                @default
                    <x-alert tone="{{ $p->status === S::TidakHadir ? 'warning' : 'info' }}" :title="$p->status->label()">
                        {{ $p->status->description() }}
                        @if ($p->alasan_batal) <span class="mt-1 block">Alasan: {{ $p->alasan_batal }}</span> @endif
                        <div class="mt-3"><x-button :href="route('akun.peminjaman.create', ['ruangan' => $p->ruangan->slug])" size="sm" variant="secondary" icon="heroicon-m-plus">Ajukan peminjaman baru</x-button></div>
                    </x-alert>
            @endswitch

            {{-- Rincian --}}
            <x-card title="Rincian peminjaman" icon="heroicon-o-document-text">
                <dl class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    <x-info label="Tanggal" icon="heroicon-o-calendar">{{ tanggal_lengkap($p->tgl_pinjam) }}</x-info>
                    <x-info label="Waktu" icon="heroicon-o-clock">{{ jam($p->waktu_mulai) }}–{{ jam($p->waktu_selesai) }} WIB · {{ $p->durasi_label }}</x-info>
                    <x-info label="Ruangan" icon="heroicon-o-building-office-2">{{ $p->ruangan->nama_ruangan }}</x-info>
                    <x-info label="Lokasi" icon="heroicon-o-map-pin">{{ $p->ruangan->lokasi ?? config('studio.lokasi') }}</x-info>
                    <x-info label="Jumlah orang" icon="heroicon-o-user-group">{{ $p->jumlah_orang }} orang</x-info>
                    <x-info label="Keperluan" icon="heroicon-o-chat-bubble-bottom-center-text">{{ $p->ket_keperluan }}</x-info>
                    <x-info label="Diajukan" icon="heroicon-o-paper-airplane">{{ tanggal_waktu($p->created_at) }}</x-info>
                    @if ($p->pemroses)
                        <x-info label="Diproses oleh" icon="heroicon-o-user">{{ $p->pemroses->username }} · {{ tanggal_waktu($p->diproses_at) }}</x-info>
                    @endif
                    @if ($p->catatan_checkout)
                        <x-info label="Catatan check-out" icon="heroicon-o-pencil-square">{{ $p->catatan_checkout }}</x-info>
                    @endif
                </dl>
            </x-card>

            {{-- Berkas --}}
            <x-card title="Foto & dokumen" icon="heroicon-o-photo">
                <div class="grid grid-cols-3 gap-3">
                    @foreach (['jaminan' => ['KTM/KTP', $p->img_jaminan], 'awal' => ['Kondisi awal', $p->img_kondisi_awal], 'akhir' => ['Kondisi akhir', $p->img_kondisi_akhir]] as $jenis => [$label, $path])
                        <div>
                            @if ($path)
                                <a href="{{ route('berkas.peminjaman', [$p, $jenis]) }}" target="_blank" class="group block overflow-hidden rounded-xl ring-1 ring-zinc-200 dark:ring-zinc-700">
                                    <img src="{{ route('berkas.peminjaman', [$p, $jenis]) }}" alt="{{ $label }}" loading="lazy" class="aspect-square w-full object-cover transition group-hover:scale-105">
                                </a>
                            @else
                                <div class="grid aspect-square place-items-center rounded-xl border-2 border-dashed border-zinc-200 text-zinc-300 dark:border-zinc-700 dark:text-zinc-600">
                                    <x-heroicon-o-photo class="size-6" />
                                </div>
                            @endif
                            <p class="mt-1.5 text-center text-xs font-medium text-zinc-500">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card title="Riwayat" icon="heroicon-o-clock">
                <x-timeline :log="$p->log" />
            </x-card>

            <div class="card p-5">
                <p class="text-sm font-semibold text-zinc-900 dark:text-white">Butuh bantuan?</p>
                <p class="mt-1 text-sm text-zinc-500">Sertakan kode <span class="font-mono font-semibold">{{ $p->kode }}</span> saat menghubungi admin.</p>
                <x-button :href="$waAdmin" target="_blank" rel="noopener" variant="secondary" class="mt-4 w-full" icon="heroicon-o-chat-bubble-left-right">Chat admin via WhatsApp</x-button>
            </div>
        </aside>
    </div>

    @if ($p->bisaDibatalkanPeminjam())
        <x-modal name="batal" title="Batalkan peminjaman?" description="Slot akan dibuka kembali untuk peminjam lain. Tindakan ini tidak dapat dibatalkan." icon="heroicon-o-exclamation-triangle" tone="rose" :show="$errors->has('alasan')">
            <form method="POST" action="{{ route('akun.peminjaman.batal', $p) }}" class="space-y-4">
                @csrf
                <x-textarea name="alasan" label="Alasan pembatalan" rows="3" required minlength="5" maxlength="500" placeholder="Mis. jadwal latihan diundur" />
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-button variant="secondary" @click="$dispatch('tutup-modal')">Tidak jadi</x-button>
                    <x-button type="submit" variant="danger">Ya, batalkan</x-button>
                </div>
            </form>
        </x-modal>
    @endif
</x-layouts.app>
