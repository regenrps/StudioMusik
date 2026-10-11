@php
    use App\Enums\StatusPesananJasa as S;
    $j = $pesanan;
    $tahap = $j->status->step();
    $langkah = ['Diajukan', 'Antrean', 'Produksi', 'Selesai'];
@endphp
<x-layouts.app :title="$j->judul">
    <x-page-header :title="$j->judul" :kembali="route('akun.jasa.index')" kembali-label="Jasa musik">
        <x-slot:meta>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <x-status :status="$j->status" />
                <x-badge tone="zinc">{{ $j->jasa->nama_jenis_jasa }}</x-badge>
                <button type="button" x-data @click="$salin(@js($j->kode), 'Kode pesanan disalin')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-1 font-mono text-xs font-semibold text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300">
                    {{ $j->kode }} <x-heroicon-o-clipboard-document class="size-3.5" />
                </button>
            </div>
        </x-slot:meta>
        @if ($j->bisaDibatalkanPemesan())
            <x-button variant="danger-soft" icon="heroicon-o-x-circle" x-data @click="$dispatch('buka-modal', 'batal')">Batalkan</x-button>
        @endif
    </x-page-header>

    @if (session('baru'))
        <x-alert tone="success" title="Pesanan berhasil dikirim" class="mb-6">Admin akan meninjau pesanan dan menentukan jadwal produksi. Anda akan diberi tahu lewat email dan notifikasi.</x-alert>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_360px]">
        <div class="min-w-0 space-y-6">
            @if ($tahap > 0)
                <div class="card p-5 sm:p-6">
                    <ol class="flex items-center">
                        @foreach ($langkah as $i => $label)
                            @php $n = $i + 1; @endphp
                            <li @class(['flex items-center', 'flex-1' => ! $loop->last])>
                                <div class="flex flex-col items-center gap-1.5">
                                    <span @class([
                                        'grid size-8 place-items-center rounded-full text-xs font-bold ring-4',
                                        'bg-brand-600 text-white ring-brand-100 dark:ring-brand-500/20' => $n < $tahap || $tahap === 4,
                                        'border-2 border-brand-600 bg-white text-brand-700 ring-brand-200 dark:bg-zinc-900 dark:text-brand-300 dark:ring-brand-500/20' => $n === $tahap && $tahap !== 4,
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

            @switch($j->status)
                @case(S::Menunggu)
                    <x-alert tone="warning" title="Menunggu peninjauan admin" icon="heroicon-s-clock">Admin akan memeriksa detail pesanan dan menjadwalkan produksi. Anda masih bisa membatalkan selama pesanan belum diterima.</x-alert>
                    @break
                @case(S::Disetujui)
                @case(S::Diproduksi)
                    <div class="card p-5 sm:p-6">
                        <div class="flex items-start gap-4">
                            <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300">
                                <x-dynamic-component :component="$j->status->icon()" class="size-6" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-zinc-900 dark:text-white">{{ $j->status->label() }}</p>
                                <p class="mt-0.5 text-sm text-zinc-500">{{ $j->status->description() }}</p>
                                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                    <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800/60">
                                        <p class="text-xs text-zinc-500">Mulai produksi</p>
                                        <p class="font-semibold text-zinc-900 dark:text-white">{{ tanggal($j->tgl_produksi) }}</p>
                                    </div>
                                    <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800/60">
                                        <p class="text-xs text-zinc-500">Target selesai</p>
                                        <p class="font-semibold text-zinc-900 dark:text-white">{{ tanggal($j->tenggat_produksi) }}</p>
                                        @if (! is_null($j->sisaHari()))
                                            <p @class(['text-xs font-medium', 'text-rose-600' => $j->sisaHari() < 0, 'text-zinc-500' => $j->sisaHari() >= 0])>
                                                {{ $j->sisaHari() < 0 ? 'Terlambat '.abs($j->sisaHari()).' hari' : ($j->sisaHari() === 0 ? 'Hari ini' : $j->sisaHari().' hari lagi') }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                @if ($j->keterangan_admin)
                                    <p class="mt-4 rounded-xl bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:bg-sky-500/10 dark:text-sky-100"><strong>Catatan admin:</strong> {{ $j->keterangan_admin }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    @break
                @case(S::Selesai)
                    <div class="card overflow-hidden">
                        <div class="bg-gradient-to-r from-emerald-500 to-teal-500 px-5 py-4 text-white sm:px-6">
                            <p class="text-sm text-white/80">Selesai {{ $j->selesai_at ? relatif($j->selesai_at) : '' }}</p>
                            <p class="text-lg font-bold">Hasil produksi siap!</p>
                        </div>
                        <div class="space-y-4 p-5 sm:p-6">
                            @if ($j->hasil_catatan)
                                <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $j->hasil_catatan }}</p>
                            @endif
                            <div class="flex flex-col gap-2 sm:flex-row">
                                @if ($j->hasil_berkas)
                                    <x-button :href="route('berkas.jasa.hasil', $j)" icon="heroicon-m-arrow-down-tray">Unduh hasil</x-button>
                                @endif
                                @if ($j->hasil_tautan)
                                    <x-button :href="$j->hasil_tautan" target="_blank" rel="noopener" variant="secondary" icon="heroicon-m-arrow-top-right-on-square">Buka tautan hasil</x-button>
                                @endif
                            </div>
                        </div>
                    </div>
                    @if ($j->bisaDiulas())
                        <x-card title="Beri ulasan" description="Apakah hasilnya sesuai harapan?" icon="heroicon-o-star">
                            <form method="POST" action="{{ route('akun.jasa.ulasan', $j) }}" class="space-y-4">
                                @csrf
                                <x-rating-input name="rating" />
                                <x-textarea name="review" rows="3" placeholder="Ceritakan pengalaman Anda (opsional)" maxlength="1000" />
                                <x-button type="submit" class="w-full">Kirim ulasan</x-button>
                            </form>
                        </x-card>
                    @elseif ($j->rating)
                        <x-alert tone="success" title="Terima kasih atas ulasannya">
                            <x-rating :nilai="$j->rating" />
                            @if ($j->review)<span class="mt-1 block italic">“{{ $j->review }}”</span>@endif
                        </x-alert>
                    @endif
                    @break
                @case(S::Ditolak)
                    <x-alert tone="danger" title="Pesanan ditolak">Alasan: {{ $j->keterangan_admin ?: 'tidak disebutkan' }}</x-alert>
                    @break
                @default
                    <x-alert tone="info" title="Pesanan dibatalkan">{{ $j->alasan_batal ? 'Alasan: '.$j->alasan_batal : $j->status->description() }}</x-alert>
            @endswitch

            <x-card title="Detail pesanan" icon="heroicon-o-document-text">
                <dl class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    <x-info label="Layanan">{{ $j->jasa->nama_jenis_jasa }}</x-info>
                    <x-info label="Tenggat diminta">{{ tanggal_lengkap($j->tenggat_produksi) }}</x-info>
                    @foreach ($j->informasi as $info)
                        <x-info :label="$info->nama_field">
                            @if ($info->isBerkas())
                                <a href="{{ route('berkas.jasa.lampiran', [$j, $info]) }}" class="link inline-flex items-center gap-1"><x-heroicon-m-paper-clip class="size-4" />{{ $info->nama_asli ?? 'Unduh berkas' }}</a>
                            @elseif ($info->isTautan())
                                <a href="{{ $info->value_field }}" target="_blank" rel="noopener nofollow" class="link break-all">{{ $info->value_field }}</a>
                            @else
                                <span class="whitespace-pre-line">{{ $info->value_field }}</span>
                            @endif
                        </x-info>
                    @endforeach
                    @if ($j->keterangan)
                        <x-info label="Catatan tambahan"><span class="whitespace-pre-line">{{ $j->keterangan }}</span></x-info>
                    @endif
                    <x-info label="Dipesan">{{ tanggal_waktu($j->created_at) }}</x-info>
                </dl>
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card title="Riwayat" icon="heroicon-o-clock">
                <x-timeline :log="$j->log" />
            </x-card>
            <div class="card p-5">
                <p class="text-sm font-semibold text-zinc-900 dark:text-white">Ada pertanyaan?</p>
                <p class="mt-1 text-sm text-zinc-500">Sertakan kode <span class="font-mono font-semibold">{{ $j->kode }}</span>.</p>
                <x-button :href="wa_link(config('studio.kontak.whatsapp'), 'Halo admin, saya ingin bertanya tentang pesanan jasa '.$j->kode.'.')" target="_blank" rel="noopener" variant="secondary" class="mt-4 w-full" icon="heroicon-o-chat-bubble-left-right">Chat admin</x-button>
            </div>
        </aside>
    </div>

    @if ($j->bisaDibatalkanPemesan())
        <x-modal name="batal" title="Batalkan pesanan?" description="Pesanan yang dibatalkan tidak dapat dipulihkan." icon="heroicon-o-exclamation-triangle" tone="rose" :show="$errors->has('alasan')">
            <form method="POST" action="{{ route('akun.jasa.batal', $j) }}" class="space-y-4">
                @csrf
                <x-textarea name="alasan" label="Alasan pembatalan" rows="3" required minlength="5" maxlength="500" />
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-button variant="secondary" @click="$dispatch('tutup-modal')">Tidak jadi</x-button>
                    <x-button type="submit" variant="danger">Ya, batalkan</x-button>
                </div>
            </form>
        </x-modal>
    @endif
</x-layouts.app>
