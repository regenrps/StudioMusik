@php
    use App\Enums\StatusPeminjaman as S;
    $p = $peminjaman;
    $admin = auth()->user()->isAdmin();
    $u = $p->user;
    $jumlahRiwayat = (int) $riwayatUser->sum();
@endphp
<x-layouts.app :title="'Peminjaman '.$p->kode">
    <x-page-header :title="$u->username.' · '.tanggal($p->tgl_pinjam, 'd M Y')" :kembali="route('admin.peminjaman.index')" kembali-label="Daftar peminjaman">
        <x-slot:meta>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <x-status :status="$p->status" />
                <span class="rounded-full bg-zinc-100 px-2.5 py-1 font-mono text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $p->kode }}</span>
                <span class="text-xs text-zinc-500">diajukan {{ relatif($p->created_at) }}</span>
            </div>
        </x-slot:meta>
        <x-button :href="route('admin.laporan.create', ['peminjaman' => $p->kode])" variant="secondary" icon="heroicon-o-exclamation-triangle">Laporkan masalah</x-button>
    </x-page-header>

    @if ($bentrok->isNotEmpty())
        <x-alert tone="danger" title="Jadwal bertabrakan" class="mb-6">
            Slot ini bertumpuk dengan:
            @foreach ($bentrok as $b)
                <a href="{{ route('admin.peminjaman.show', $b) }}" class="font-semibold underline">{{ $b->kode }}</a> ({{ $b->user->username }}, {{ str_replace(':', '.', $b->rentang_waktu) }}, {{ $b->status->shortLabel() }})@if (! $loop->last), @endif
            @endforeach.
            Sistem tidak akan mengizinkan persetujuan selama masih bertabrakan.
        </x-alert>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_380px]">
        <div class="min-w-0 space-y-6">
            {{-- Panel keputusan --}}
            @if ($admin && $p->status === S::Menunggu)
                <div class="card overflow-hidden ring-2 ring-amber-300/60 dark:ring-amber-500/30">
                    <div class="flex items-start gap-3 bg-amber-50 px-5 py-4 sm:px-6 dark:bg-amber-500/10">
                        <x-heroicon-s-inbox-arrow-down class="mt-0.5 size-5 shrink-0 text-amber-500" />
                        <div>
                            <p class="font-semibold text-amber-900 dark:text-amber-100">Pengajuan menunggu keputusan</p>
                            <p class="text-sm text-amber-800/80 dark:text-amber-200/80">
                                Jadwal dimulai {{ relatif($p->mulai) }}. Periksa foto KTM/KTP dan keperluan sebelum memutuskan.
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 p-5 sm:flex-row sm:px-6">
                        <x-button variant="success" class="flex-1" icon="heroicon-m-check" x-data @click="$dispatch('buka-modal', 'setujui')">Setujui</x-button>
                        <x-button variant="danger-soft" class="flex-1" icon="heroicon-m-x-mark" x-data @click="$dispatch('buka-modal', 'tolak')">Tolak</x-button>
                    </div>
                </div>
            @elseif ($admin && in_array($p->status, [S::Disetujui, S::Berlangsung, S::TidakHadir], true))
                <div class="card flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div class="flex items-start gap-3">
                        <x-dynamic-component :component="$p->status->icon()" class="mt-0.5 size-5 shrink-0 text-zinc-400" />
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $p->status->description() }}</p>
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        @if (in_array($p->status, [S::Berlangsung, S::TidakHadir], true) || ($p->status === S::Disetujui && $p->selesai->isPast()))
                            <x-button variant="success" size="sm" icon="heroicon-m-check-circle" x-data @click="$dispatch('buka-modal', 'selesai')">Tandai selesai</x-button>
                        @endif
                        @if ($p->status === S::Disetujui && $p->mulai->isPast())
                            <form method="POST" action="{{ route('admin.peminjaman.tidak-hadir', $p) }}" data-konfirmasi="Tandai peminjam tidak hadir?">
                                @csrf
                                <x-button type="submit" variant="secondary" size="sm" icon="heroicon-m-user-minus">Tidak hadir</x-button>
                            </form>
                        @endif
                        @if ($p->status === S::Disetujui)
                            <x-button variant="danger-soft" size="sm" icon="heroicon-m-no-symbol" x-data @click="$dispatch('buka-modal', 'batal')">Batalkan</x-button>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Rincian --}}
            <x-card title="Rincian peminjaman" icon="heroicon-o-document-text">
                <dl class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    <x-info label="Tanggal" icon="heroicon-o-calendar">{{ tanggal_lengkap($p->tgl_pinjam) }}</x-info>
                    <x-info label="Waktu" icon="heroicon-o-clock">{{ jam($p->waktu_mulai) }}–{{ jam($p->waktu_selesai) }} · {{ $p->durasi_label }}</x-info>
                    <x-info label="Ruangan" icon="heroicon-o-building-office-2">{{ $p->ruangan->nama_ruangan }}</x-info>
                    <x-info label="Jumlah orang" icon="heroicon-o-user-group">{{ $p->jumlah_orang }} orang</x-info>
                    <x-info label="Keperluan" icon="heroicon-o-chat-bubble-bottom-center-text"><span class="whitespace-pre-line">{{ $p->ket_keperluan }}</span></x-info>
                    @if ($p->ket_admin)
                        <x-info label="{{ $p->status === S::Ditolak ? 'Alasan penolakan' : 'Catatan admin' }}" icon="heroicon-o-pencil-square">{{ $p->ket_admin }}</x-info>
                    @endif
                    @if ($p->alasan_batal)
                        <x-info label="Alasan pembatalan" icon="heroicon-o-no-symbol">{{ $p->alasan_batal }}</x-info>
                    @endif
                    @if ($p->pemroses)
                        <x-info label="Diproses oleh" icon="heroicon-o-user">{{ $p->pemroses->username }} · {{ tanggal_waktu($p->diproses_at) }}</x-info>
                    @endif
                    @if ($p->checkin_at)
                        <x-info label="Check-in" icon="heroicon-o-arrow-right-end-on-rectangle">{{ tanggal_waktu($p->checkin_at) }}</x-info>
                    @endif
                    @if ($p->checkout_at)
                        <x-info label="Check-out" icon="heroicon-o-arrow-left-start-on-rectangle">{{ tanggal_waktu($p->checkout_at) }}</x-info>
                    @endif
                    @if ($p->catatan_checkout)
                        <x-info label="Catatan check-out" icon="heroicon-o-pencil">{{ $p->catatan_checkout }}</x-info>
                    @endif
                    @if ($p->rating)
                        <x-info label="Ulasan" icon="heroicon-o-star"><x-rating :nilai="$p->rating" ukuran="size-3.5" /> @if ($p->review)<span class="mt-1 block font-normal italic">“{{ $p->review }}”</span>@endif</x-info>
                    @endif
                </dl>
            </x-card>

            {{-- Foto --}}
            <x-card title="Jaminan & kondisi ruangan" icon="heroicon-o-photo">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    @foreach (['jaminan' => ['KTM/KTP jaminan', $p->img_jaminan], 'awal' => ['Kondisi awal (check-in)', $p->img_kondisi_awal], 'akhir' => ['Kondisi akhir (check-out)', $p->img_kondisi_akhir]] as $jenis => [$label, $path])
                        <figure>
                            @if ($path)
                                <a href="{{ route('berkas.peminjaman', [$p, $jenis]) }}" target="_blank" class="group block overflow-hidden rounded-xl ring-1 ring-zinc-200 dark:ring-zinc-700">
                                    <img src="{{ route('berkas.peminjaman', [$p, $jenis]) }}" alt="{{ $label }}" loading="lazy" class="aspect-[4/3] w-full object-cover transition group-hover:scale-105">
                                </a>
                            @else
                                <div class="grid aspect-[4/3] place-items-center rounded-xl border-2 border-dashed border-zinc-200 p-2 text-center text-xs text-zinc-400 dark:border-zinc-700">{{ $jenis === 'jaminan' && $p->status->isFinal() ? 'Dihapus otomatis (retensi privasi)' : 'Belum ada' }}</div>
                            @endif
                            <figcaption class="mt-1.5 text-xs font-medium text-zinc-500">{{ $label }}</figcaption>
                        </figure>
                    @endforeach
                </div>
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card title="Peminjam" icon="heroicon-o-user">
                <div class="flex items-center gap-3">
                    <x-avatar :user="$u" />
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-zinc-900 dark:text-white">{{ $u->username }}</p>
                        <p class="truncate text-sm text-zinc-500">{{ $u->nim_nip ? 'NIM/NIP '.$u->nim_nip : $u->email }}</p>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    @if ($u->whatsapp_url)
                        <x-button :href="wa_link($u->no_wa, 'Halo '.$u->username.', terkait peminjaman studio '.$p->kode.' ...')" target="_blank" rel="noopener" variant="secondary" size="sm" icon="heroicon-o-chat-bubble-left-right">WhatsApp</x-button>
                    @endif
                    <x-button :href="'mailto:'.$u->email.'?subject='.rawurlencode('Peminjaman studio '.$p->kode)" variant="secondary" size="sm" icon="heroicon-o-envelope">Email</x-button>
                </div>
                <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-xl bg-zinc-50 p-2 dark:bg-zinc-800/60"><dt class="text-[11px] text-zinc-500">Total</dt><dd class="font-bold tabular-nums">{{ $jumlahRiwayat }}</dd></div>
                    <div class="rounded-xl bg-emerald-50 p-2 dark:bg-emerald-500/10"><dt class="text-[11px] text-emerald-700 dark:text-emerald-300">Selesai</dt><dd class="font-bold text-emerald-700 tabular-nums dark:text-emerald-300">{{ (int) ($riwayatUser['completed'] ?? 0) }}</dd></div>
                    <div class="rounded-xl bg-orange-50 p-2 dark:bg-orange-500/10"><dt class="text-[11px] text-orange-700 dark:text-orange-300">Tdk hadir</dt><dd class="font-bold text-orange-700 tabular-nums dark:text-orange-300">{{ (int) ($riwayatUser['no_show'] ?? 0) }}</dd></div>
                </dl>
                @if (($riwayatUser['no_show'] ?? 0) >= 2)
                    <x-alert tone="warning" class="mt-4">Peminjam ini {{ $riwayatUser['no_show'] }} kali tidak hadir. Pertimbangkan sebelum menyetujui.</x-alert>
                @endif
            </x-card>

            <x-card title="Riwayat aktivitas" icon="heroicon-o-clock">
                <x-timeline :log="$p->log" />
            </x-card>
        </aside>
    </div>

    @if ($admin)
        <x-modal name="setujui" title="Setujui peminjaman?" :description="'Peminjam akan menerima email & notifikasi. Jadwal: '.tanggal($p->tgl_pinjam, 'd M Y').', '.str_replace(':', '.', $p->rentang_waktu).'.'" icon="heroicon-o-check-badge" tone="emerald">
            <form method="POST" action="{{ route('admin.peminjaman.setujui', $p) }}" class="space-y-4">
                @csrf
                <x-textarea name="catatan" label="Catatan untuk peminjam (opsional)" rows="3" maxlength="500" placeholder="Mis. kunci diambil di pos satpam Gedung D" />
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-button variant="secondary" @click="$dispatch('tutup-modal')">Batal</x-button>
                    <x-button type="submit" variant="success" icon="heroicon-m-check">Setujui</x-button>
                </div>
            </form>
        </x-modal>

        <x-modal name="tolak" title="Tolak pengajuan" description="Alasan penolakan akan dikirim ke peminjam agar dapat mengajukan ulang dengan benar." icon="heroicon-o-x-circle" tone="rose" :show="$errors->has('alasan') && old('_aksi') === 'tolak'">
            <form method="POST" action="{{ route('admin.peminjaman.tolak', $p) }}" class="space-y-4">
                @csrf
                <input type="hidden" name="_aksi" value="tolak">
                <div x-data="{ alasan: @js(old('alasan', '')) }" class="space-y-2">
                    <div class="flex flex-wrap gap-1.5">
                        @foreach (['Foto KTM/KTP tidak jelas', 'Bentrok dengan kegiatan UKMBSM', 'Keperluan kurang jelas', 'Jumlah orang melebihi kapasitas'] as $templat)
                            <button type="button" @click="alasan = @js($templat)" class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300">{{ $templat }}</button>
                        @endforeach
                    </div>
                    <textarea name="alasan" x-model="alasan" rows="3" required minlength="5" maxlength="500" class="input" placeholder="Tulis alasan penolakan"></textarea>
                    @error('alasan')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-button variant="secondary" @click="$dispatch('tutup-modal')">Batal</x-button>
                    <x-button type="submit" variant="danger">Tolak pengajuan</x-button>
                </div>
            </form>
        </x-modal>

        <x-modal name="batal" title="Batalkan peminjaman yang sudah disetujui?" description="Peminjam akan diberi tahu beserta alasannya." icon="heroicon-o-no-symbol" tone="rose" :show="$errors->has('alasan') && old('_aksi') === 'batal'">
            <form method="POST" action="{{ route('admin.peminjaman.batal', $p) }}" class="space-y-4">
                @csrf
                <input type="hidden" name="_aksi" value="batal">
                <x-textarea name="alasan" label="Alasan pembatalan" rows="3" required minlength="5" maxlength="500" placeholder="Mis. studio dipakai kegiatan mendadak UKMBSM" />
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-button variant="secondary" @click="$dispatch('tutup-modal')">Kembali</x-button>
                    <x-button type="submit" variant="danger">Batalkan peminjaman</x-button>
                </div>
            </form>
        </x-modal>

        <x-modal name="selesai" title="Tandai selesai?" description="Gunakan bila peminjam lupa check-out. Pastikan kondisi studio sudah diperiksa." icon="heroicon-o-check-circle" tone="emerald">
            <form method="POST" action="{{ route('admin.peminjaman.selesai', $p) }}" class="space-y-4">
                @csrf
                <x-textarea name="catatan" label="Catatan (opsional)" rows="3" maxlength="500" placeholder="Mis. kondisi studio diperiksa admin, semua alat lengkap" />
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-button variant="secondary" @click="$dispatch('tutup-modal')">Batal</x-button>
                    <x-button type="submit" variant="success">Tandai selesai</x-button>
                </div>
            </form>
        </x-modal>
    @endif
</x-layouts.app>
