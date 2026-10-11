@php
    use App\Enums\StatusPesananJasa as S;
    $j = $pesanan;
    $u = $j->user;
    $upload = config('studio.upload');
@endphp
<x-layouts.app :title="$j->judul">
    <x-page-header :title="$j->judul" :kembali="route('admin.jasa.index')" kembali-label="Pesanan jasa">
        <x-slot:meta>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <x-status :status="$j->status" />
                <x-badge tone="zinc">{{ $j->jasa->nama_jenis_jasa }}</x-badge>
                <span class="rounded-full bg-zinc-100 px-2.5 py-1 font-mono text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $j->kode }}</span>
                @if ($j->terlambat())<x-badge tone="rose" icon="heroicon-m-exclamation-triangle">Melewati tenggat</x-badge>@endif
            </div>
        </x-slot:meta>
        @if ($j->status->isActive())
            <x-button variant="danger-soft" icon="heroicon-o-no-symbol" x-data @click="$dispatch('buka-modal', 'batal')">Batalkan</x-button>
        @endif
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_380px]">
        <div class="min-w-0 space-y-6">
            {{-- Panel aksi --}}
            @switch($j->status)
                @case(S::Menunggu)
                    <div class="card overflow-hidden ring-2 ring-amber-300/60 dark:ring-amber-500/30">
                        <div class="bg-amber-50 px-5 py-4 sm:px-6 dark:bg-amber-500/10">
                            <p class="font-semibold text-amber-900 dark:text-amber-100">Pesanan menunggu keputusan</p>
                            <p class="text-sm text-amber-800/80 dark:text-amber-200/80">Pemesan meminta selesai paling lambat {{ tanggal_lengkap($j->tenggat_produksi) }}. Ada {{ $antreanLain }} pesanan lain dengan tenggat 30 hari ke depan.</p>
                        </div>
                        <form method="POST" action="{{ route('admin.jasa.setujui', $j) }}" class="space-y-4 p-5 sm:p-6">
                            @csrf
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <x-input name="tgl_produksi" type="date" label="Mulai produksi" :value="today()->toDateString()" min="{{ today()->toDateString() }}" required />
                                <x-input name="tenggat_produksi" type="date" label="Target selesai" :value="$j->tenggat_produksi->toDateString()" required />
                            </div>
                            <x-textarea name="catatan" label="Catatan untuk pemesan (opsional)" rows="2" maxlength="1000" placeholder="Mis. komposer: Andi. Kami akan menghubungi via WhatsApp untuk diskusi konsep." />
                            <x-checkbox name="langsung_produksi" label="Langsung mulai produksi hari ini" />
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <x-button type="submit" variant="success" icon="heroicon-m-check" class="flex-1">Terima pesanan</x-button>
                                <x-button variant="danger-soft" icon="heroicon-m-x-mark" class="flex-1" x-data @click="$dispatch('buka-modal', 'tolak')">Tolak</x-button>
                            </div>
                        </form>
                    </div>
                    @break

                @case(S::Disetujui)
                @case(S::Diproduksi)
                    <div class="card overflow-hidden">
                        <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div>
                                <p class="font-semibold text-zinc-900 dark:text-white">{{ $j->status->label() }}</p>
                                <p class="text-sm text-zinc-500">Produksi {{ tanggal($j->tgl_produksi) }} → target {{ tanggal($j->tenggat_produksi) }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <x-button variant="secondary" size="sm" icon="heroicon-o-calendar" x-data @click="$dispatch('buka-modal', 'jadwal')">Ubah jadwal</x-button>
                                @if ($j->status === S::Disetujui)
                                    <form method="POST" action="{{ route('admin.jasa.mulai', $j) }}">
                                        @csrf
                                        <x-button type="submit" size="sm" icon="heroicon-m-play">Mulai produksi</x-button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.jasa.selesai', $j) }}" enctype="multipart/form-data" class="space-y-4 border-t border-zinc-100 p-5 sm:p-6 dark:border-zinc-800">
                            @csrf
                            <div>
                                <h3 class="font-semibold text-zinc-900 dark:text-white">Kirim hasil & selesaikan</h3>
                                <p class="mt-0.5 text-sm text-zinc-500">Unggah berkas hasil atau cantumkan tautan (mis. Google Drive). Pemesan langsung mendapat notifikasi.</p>
                            </div>
                            <x-file-input name="hasil" label="Berkas hasil" :kompres="false" :maks-kb="$upload['audio_maks_kb'] * 5" accept=".{{ implode(',.', $upload['hasil_mimes']) }}"
                                ikon="heroicon-o-musical-note" judul="Pilih berkas hasil produksi" :hint="'Format: '.strtoupper(implode(', ', $upload['hasil_mimes'])).' · maks. '.\App\Support\Berkas::ukuranLabel($upload['audio_maks_kb'] * 5 * 1024)" />
                            <x-input name="tautan" type="url" label="atau tautan hasil" placeholder="https://drive.google.com/…" />
                            <x-textarea name="catatan" label="Pesan untuk pemesan (opsional)" rows="2" maxlength="1000" />
                            <x-button type="submit" variant="success" icon="heroicon-m-paper-airplane" class="w-full sm:w-auto">Kirim hasil & tandai selesai</x-button>
                        </form>
                    </div>
                    @break

                @case(S::Selesai)
                    <x-card title="Hasil produksi" icon="heroicon-o-gift">
                        <div class="flex flex-wrap gap-2">
                            @if ($j->hasil_berkas)<x-button :href="route('berkas.jasa.hasil', $j)" variant="secondary" icon="heroicon-m-arrow-down-tray">Unduh berkas hasil</x-button>@endif
                            @if ($j->hasil_tautan)<x-button :href="$j->hasil_tautan" target="_blank" rel="noopener" variant="secondary" icon="heroicon-m-arrow-top-right-on-square">Buka tautan</x-button>@endif
                        </div>
                        @if ($j->hasil_catatan)<p class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $j->hasil_catatan }}</p>@endif
                        @if ($j->rating)
                            <div class="mt-4 rounded-xl bg-amber-50 p-4 dark:bg-amber-500/10">
                                <x-rating :nilai="$j->rating" />
                                @if ($j->review)<p class="mt-1 text-sm italic text-zinc-700 dark:text-zinc-300">“{{ $j->review }}”</p>@endif
                            </div>
                        @endif
                    </x-card>
                    @break

                @default
                    <x-alert tone="{{ $j->status === S::Ditolak ? 'danger' : 'info' }}" :title="$j->status->label()">
                        {{ $j->status === S::Ditolak ? 'Alasan: '.($j->keterangan_admin ?: '-') : ($j->alasan_batal ? 'Alasan: '.$j->alasan_batal : $j->status->description()) }}
                    </x-alert>
            @endswitch

            <x-card title="Kebutuhan pemesan" icon="heroicon-o-clipboard-document-list">
                <dl class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    <x-info label="Tenggat diminta">{{ tanggal_lengkap($j->tenggat_produksi) }}</x-info>
                    @forelse ($j->informasi as $info)
                        <x-info :label="$info->nama_field">
                            @if ($info->isBerkas())
                                <a href="{{ route('berkas.jasa.lampiran', [$j, $info]) }}" class="link inline-flex items-center gap-1"><x-heroicon-m-paper-clip class="size-4" />{{ $info->nama_asli ?? 'Unduh berkas' }}</a>
                            @elseif ($info->isTautan())
                                <a href="{{ $info->value_field }}" target="_blank" rel="noopener nofollow" class="link break-all">{{ $info->value_field }}</a>
                            @else
                                <span class="whitespace-pre-line">{{ $info->value_field }}</span>
                            @endif
                        </x-info>
                    @empty
                        <p class="py-3 text-sm text-zinc-500">Tidak ada isian tambahan.</p>
                    @endforelse
                    @if ($j->keterangan)
                        <x-info label="Catatan tambahan"><span class="whitespace-pre-line">{{ $j->keterangan }}</span></x-info>
                    @endif
                    @if ($j->keterangan_admin && $j->status !== S::Ditolak)
                        <x-info label="Catatan admin">{{ $j->keterangan_admin }}</x-info>
                    @endif
                </dl>
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card title="Pemesan" icon="heroicon-o-user">
                <div class="flex items-center gap-3">
                    <x-avatar :user="$u" />
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-zinc-900 dark:text-white">{{ $u->username }}</p>
                        <p class="truncate text-sm text-zinc-500">{{ $u->email }}</p>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    @if ($u->whatsapp_url)
                        <x-button :href="wa_link($u->no_wa, 'Halo '.$u->username.', terkait pesanan jasa '.$j->kode.' ...')" target="_blank" rel="noopener" variant="secondary" size="sm" icon="heroicon-o-chat-bubble-left-right">WhatsApp</x-button>
                    @endif
                    <x-button :href="'mailto:'.$u->email.'?subject='.rawurlencode('Pesanan jasa '.$j->kode)" variant="secondary" size="sm" icon="heroicon-o-envelope">Email</x-button>
                </div>
            </x-card>
            <x-card title="Riwayat aktivitas" icon="heroicon-o-clock">
                <x-timeline :log="$j->log" />
            </x-card>
        </aside>
    </div>

    <x-modal name="tolak" title="Tolak pesanan" description="Alasan akan dikirim ke pemesan." icon="heroicon-o-x-circle" tone="rose" :show="$errors->has('alasan') && old('_aksi') === 'tolak'">
        <form method="POST" action="{{ route('admin.jasa.tolak', $j) }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_aksi" value="tolak">
            <x-textarea name="alasan" label="Alasan penolakan" rows="3" required minlength="5" maxlength="1000" placeholder="Mis. antrean produksi penuh hingga akhir bulan" />
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <x-button variant="secondary" @click="$dispatch('tutup-modal')">Batal</x-button>
                <x-button type="submit" variant="danger">Tolak pesanan</x-button>
            </div>
        </form>
    </x-modal>

    <x-modal name="batal" title="Batalkan pesanan?" description="Pemesan akan diberi tahu beserta alasannya." icon="heroicon-o-no-symbol" tone="rose" :show="$errors->has('alasan') && old('_aksi') === 'batal'">
        <form method="POST" action="{{ route('admin.jasa.batal', $j) }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_aksi" value="batal">
            <x-textarea name="alasan" label="Alasan pembatalan" rows="3" required minlength="5" maxlength="1000" />
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <x-button variant="secondary" @click="$dispatch('tutup-modal')">Kembali</x-button>
                <x-button type="submit" variant="danger">Batalkan pesanan</x-button>
            </div>
        </form>
    </x-modal>

    @if (in_array($j->status, [S::Disetujui, S::Diproduksi], true))
        <x-modal name="jadwal" title="Ubah jadwal produksi" icon="heroicon-o-calendar" :show="$errors->hasAny(['tgl_produksi', 'tenggat_produksi'])">
            <form method="POST" action="{{ route('admin.jasa.jadwal', $j) }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-input name="tgl_produksi" type="date" label="Mulai produksi" :value="$j->tgl_produksi?->toDateString()" required />
                    <x-input name="tenggat_produksi" type="date" label="Target selesai" :value="$j->tenggat_produksi->toDateString()" required />
                </div>
                <x-textarea name="catatan" label="Catatan admin" rows="2" maxlength="1000" :value="$j->keterangan_admin" />
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-button variant="secondary" @click="$dispatch('tutup-modal')">Batal</x-button>
                    <x-button type="submit">Simpan jadwal</x-button>
                </div>
            </form>
        </x-modal>
    @endif
</x-layouts.app>
