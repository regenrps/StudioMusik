@php
    $langkahError = null;
    if ($errors->hasAny(['ruangan', 'tgl_pinjam'])) {
        $langkahError = 1;
    } elseif ($errors->hasAny(['waktu_mulai', 'durasi', 'aturan'])) {
        $langkahError = 2;
    } elseif ($errors->any()) {
        $langkahError = 3;
    }

    $dataRuangan = $ruangan->map(fn ($r) => [
        'slug' => $r->slug,
        'nama' => $r->nama_ruangan,
        'kapasitas' => $r->kapasitas,
        'lokasi' => $r->lokasi,
        'thumbnail' => $r->thumbnail_url,
    ])->values();
@endphp

<x-layouts.app title="Pinjam Studio">
    <x-page-header title="Pinjam studio" description="Pilih tanggal dan jam yang masih kosong, lalu lengkapi detail peminjaman." :kembali="route('akun.peminjaman.index')" kembali-label="Peminjaman saya" />

    @if ($penghalang)
        <x-card>
            <x-empty icon="heroicon-o-calendar-days" title="Anda masih memiliki peminjaman aktif"
                :description="'Batas peminjaman aktif per orang adalah '.$aturan['maks_booking_aktif'].'. Selesaikan atau batalkan peminjaman '.$penghalang->kode.' ('.tanggal($penghalang->tgl_pinjam).', '.str_replace(':', '.', $penghalang->rentang_waktu).') sebelum mengajukan jadwal baru.'">
                <x-button :href="route('akun.peminjaman.show', $penghalang)" icon="heroicon-o-eye">Lihat peminjaman aktif</x-button>
                <x-button :href="route('jadwal')" variant="secondary">Lihat jadwal studio</x-button>
            </x-empty>
        </x-card>
    @elseif ($ruangan->isEmpty())
        <x-card>
            <x-empty icon="heroicon-o-building-office-2" title="Belum ada ruangan yang bisa dipinjam" description="Silakan hubungi admin studio." />
        </x-card>
    @else
        <form method="POST" action="{{ route('akun.peminjaman.store') }}" enctype="multipart/form-data"
            x-data="wizardPeminjaman({
                ruangan: @js($dataRuangan),
                ruanganAwal: @js(old('ruangan', $dipilih?->slug)),
                tanggalAwal: @js(old('tgl_pinjam', $tanggalAwal)),
                aturan: @js($aturan),
                urlBulan: @js(route('ketersediaan.bulan', '__RUANGAN__')),
                urlHari: @js(route('ketersediaan.hari', '__RUANGAN__')),
                lama: @js(['waktu_mulai' => old('waktu_mulai'), 'durasi' => old('durasi'), 'jumlah_orang' => old('jumlah_orang'), 'ket_keperluan' => old('ket_keperluan'), 'setuju_sop' => old('setuju_sop')]),
                langkahError: @js($langkahError),
            })"
            class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_340px]">
            @csrf
            <input type="hidden" name="ruangan" :value="ruangan">
            <input type="hidden" name="tgl_pinjam" :value="tanggal">
            <input type="hidden" name="waktu_mulai" :value="mulai">
            <input type="hidden" name="durasi" :value="durasi">

            <div class="min-w-0 space-y-6" x-ref="atas">
                @if ($errors->any())
                    <x-alert tone="danger" title="Pengajuan belum dapat dikirim">
                        <ul class="list-disc space-y-0.5 pl-4">
                            @foreach ($errors->all() as $pesan)
                                <li>{{ $pesan }}</li>
                            @endforeach
                        </ul>
                        @unless ($errors->has('jaminan'))
                            <p class="mt-2 font-medium">Demi keamanan, silakan pilih ulang foto KTM/KTP Anda.</p>
                        @endunless
                    </x-alert>
                @endif

                {{-- Indikator langkah --}}
                <ol class="grid grid-cols-3 gap-2" aria-label="Langkah pengajuan">
                    @foreach (['Tanggal', 'Jam', 'Detail'] as $i => $label)
                        <li>
                            <button type="button" @click="ke({{ $i + 1 }})" class="group w-full text-left"
                                :disabled="{{ $i + 1 }} > 1 && ! bolehLanjut{{ max(1, $i) }}">
                                <span class="block h-1.5 rounded-full transition-colors" :class="langkah >= {{ $i + 1 }} ? 'bg-brand-600 dark:bg-brand-500' : 'bg-zinc-200 dark:bg-zinc-800'"></span>
                                <span class="mt-2 flex items-center gap-1.5 text-xs font-semibold sm:text-sm"
                                    :class="langkah === {{ $i + 1 }} ? 'text-brand-700 dark:text-brand-300' : (langkah > {{ $i + 1 }} ? 'text-zinc-700 dark:text-zinc-300' : 'text-zinc-400')">
                                    <span class="grid size-5 place-items-center rounded-full text-[11px]"
                                        :class="langkah > {{ $i + 1 }} ? 'bg-brand-600 text-white' : (langkah === {{ $i + 1 }} ? 'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300' : 'bg-zinc-100 dark:bg-zinc-800')">
                                        <span x-show="langkah <= {{ $i + 1 }}">{{ $i + 1 }}</span>
                                        <x-heroicon-m-check class="size-3" x-show="langkah > {{ $i + 1 }}" x-cloak />
                                    </span>
                                    {{ $label }}
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ol>

                {{-- ============== LANGKAH 1: RUANGAN & TANGGAL ============== --}}
                <section x-show="langkah === 1" x-transition.opacity class="space-y-6">
                    @if ($ruangan->count() > 1)
                        <x-card title="Pilih ruangan">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <template x-for="r in daftarRuangan" :key="r.slug">
                                    <label class="flex cursor-pointer items-center gap-3 rounded-xl p-3 ring-1 transition"
                                        :class="ruangan === r.slug ? 'bg-brand-50 ring-2 ring-brand-500 dark:bg-brand-500/10' : 'ring-zinc-200 hover:bg-zinc-50 dark:ring-zinc-700 dark:hover:bg-zinc-800'">
                                        <input type="radio" class="sr-only" :value="r.slug" x-model="ruangan">
                                        <img :src="r.thumbnail" alt="" class="size-14 rounded-lg object-cover">
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-semibold text-zinc-900 dark:text-white" x-text="r.nama"></span>
                                            <span class="block text-xs text-zinc-500" x-text="`Maks. ${Math.min(r.kapasitas, aturan.maks_orang)} orang`"></span>
                                        </span>
                                    </label>
                                </template>
                            </div>
                        </x-card>
                    @endif

                    <x-card :padding="false">
                        <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-5 py-4 sm:px-6 dark:border-zinc-800">
                            <div>
                                <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Pilih tanggal</h2>
                                <p class="mt-0.5 text-sm text-zinc-500" x-text="ruanganAktif ? ruanganAktif.nama : ''"></p>
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button" @click="gantiBulan(-1)" :disabled="! bisaMundur || memuatBulan" class="grid size-9 place-items-center rounded-lg text-zinc-500 hover:bg-zinc-100 disabled:opacity-30 dark:hover:bg-zinc-800" aria-label="Bulan sebelumnya">
                                    <x-heroicon-m-chevron-left class="size-5" />
                                </button>
                                <p class="w-32 text-center text-sm font-semibold text-zinc-900 capitalize dark:text-white" x-text="labelBulan || '…'"></p>
                                <button type="button" @click="gantiBulan(1)" :disabled="! bisaMaju || memuatBulan" class="grid size-9 place-items-center rounded-lg text-zinc-500 hover:bg-zinc-100 disabled:opacity-30 dark:hover:bg-zinc-800" aria-label="Bulan berikutnya">
                                    <x-heroicon-m-chevron-right class="size-5" />
                                </button>
                            </div>
                        </div>

                        <div class="p-3 sm:p-5">
                            <template x-if="galatBulan">
                                <x-alert tone="danger"><span x-text="galatBulan"></span> <button type="button" class="font-semibold underline" @click="muatBulan()">Coba lagi</button></x-alert>
                            </template>

                            <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-semibold tracking-wide text-zinc-400 uppercase sm:gap-2">
                                @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $h)
                                    <div class="py-1">{{ $h }}</div>
                                @endforeach
                            </div>
                            <div class="relative mt-1 grid grid-cols-7 gap-1 sm:gap-2" :class="memuatBulan && 'opacity-50'">
                                <template x-for="(h, i) in sel" :key="i">
                                    <div>
                                        <template x-if="! h"><div class="aspect-square"></div></template>
                                        <template x-if="h">
                                            <button type="button" @click="pilihTanggal(h)" :disabled="! bisaDipilih(h)"
                                                :title="h.keterangan || ''"
                                                class="relative flex aspect-square w-full flex-col items-center justify-center rounded-xl text-sm font-semibold transition sm:aspect-[5/4]"
                                                :class="{
                                                    'ring-2 ring-brand-600 ring-offset-2 ring-offset-white dark:ring-offset-zinc-900': tanggal === h.tanggal,
                                                    'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:bg-emerald-500/20': h.status === 'tersedia',
                                                    'bg-amber-50 text-amber-800 hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20': h.status === 'sebagian',
                                                    'bg-rose-50 text-rose-400 line-through dark:bg-rose-500/10 dark:text-rose-400/70': h.status === 'penuh',
                                                    'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500': h.status === 'tutup',
                                                    'text-zinc-300 dark:text-zinc-600': ['lewat', 'libur', 'diluar'].includes(h.status),
                                                }">
                                                <span x-text="Number(h.tanggal.slice(8))"></span>
                                                <span class="mt-0.5 hidden text-[10px] font-medium sm:block"
                                                    x-text="{ tersedia: 'Kosong', sebagian: 'Sebagian', penuh: 'Penuh', tutup: 'Tutup' }[h.status] || ''"></span>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-xs text-zinc-500">
                                <span class="inline-flex items-center gap-1.5"><span class="size-3 rounded bg-emerald-100 ring-1 ring-emerald-300 dark:bg-emerald-500/20 dark:ring-emerald-500/40"></span>Kosong</span>
                                <span class="inline-flex items-center gap-1.5"><span class="size-3 rounded bg-amber-100 ring-1 ring-amber-300 dark:bg-amber-500/20 dark:ring-amber-500/40"></span>Sebagian terisi</span>
                                <span class="inline-flex items-center gap-1.5"><span class="size-3 rounded bg-rose-100 ring-1 ring-rose-300 dark:bg-rose-500/20 dark:ring-rose-500/40"></span>Penuh</span>
                                <span class="inline-flex items-center gap-1.5"><span class="size-3 rounded bg-zinc-200 dark:bg-zinc-700"></span>Tutup</span>
                                <span class="inline-flex items-center gap-1.5 text-zinc-400">Tanggal pudar: di luar hari/jangkauan pemesanan</span>
                            </div>
                        </div>
                    </x-card>

                    <template x-if="tanggal && detail && ! memuatHari && jumlahSlotTersedia === 0">
                        <x-alert tone="warning">Tidak ada slot dengan durasi minimal yang tersisa di tanggal ini. Silakan pilih tanggal lain.</x-alert>
                    </template>
                </section>

                {{-- ============== LANGKAH 2: JAM & DURASI ============== --}}
                <section x-show="langkah === 2" x-cloak x-transition.opacity class="space-y-6">
                    <x-card title="Berapa lama?" description="Pilih durasi dulu, lalu jam mulai yang tersedia akan menyesuaikan.">
                        <div class="flex flex-wrap gap-2">
                            <template x-for="d in opsiDurasi" :key="d">
                                <button type="button" @click="durasi = d"
                                    class="rounded-xl px-4 py-2.5 text-sm font-semibold ring-1 transition"
                                    :class="durasi === d ? 'bg-brand-600 text-white ring-brand-600 shadow-sm' : 'bg-white text-zinc-700 ring-zinc-200 hover:ring-brand-300 dark:bg-zinc-900 dark:text-zinc-200 dark:ring-zinc-700'"
                                    x-text="labelDurasi(d)"></button>
                            </template>
                        </div>
                    </x-card>

                    <x-card :padding="false">
                        <div class="border-b border-zinc-100 px-5 py-4 sm:px-6 dark:border-zinc-800">
                            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Jam mulai</h2>
                            <p class="mt-0.5 text-sm text-zinc-500" x-text="labelTanggal(tanggal)"></p>
                        </div>
                        <div class="space-y-5 p-5 sm:p-6">
                            {{-- Timeline hari --}}
                            <template x-if="detail">
                                <div>
                                    <div class="relative h-10 overflow-hidden rounded-xl bg-emerald-50 ring-1 ring-emerald-200 ring-inset dark:bg-emerald-500/10 dark:ring-emerald-500/20">
                                        <template x-for="(t, i) in detail.terisi" :key="i">
                                            <div class="absolute inset-y-0 flex items-center justify-center overflow-hidden border-x border-white/70 text-[10px] font-semibold dark:border-zinc-900/60"
                                                :class="t.jenis === 'tutup' ? 'bg-zinc-300 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300' : (t.status === 'pending' ? 'bg-amber-200 text-amber-800 dark:bg-amber-500/40 dark:text-amber-100' : 'bg-zinc-300 text-zinc-700 dark:bg-zinc-600 dark:text-zinc-200')"
                                                :style="`left:${persen(t.mulai)}%;width:${persen(t.selesai) - persen(t.mulai)}%`" :title="`${t.mulai}–${t.selesai} · ${t.label}`">
                                                <span class="truncate px-1" x-text="t.jenis === 'tutup' ? 'Tutup' : 'Terisi'"></span>
                                            </div>
                                        </template>
                                        <div x-show="mulai" class="absolute inset-y-1 rounded-lg bg-brand-600 shadow-lg ring-2 ring-white transition-all dark:ring-zinc-900"
                                            :style="mulai && `left:${persen(mulai)}%;width:${persen(selesai) - persen(mulai)}%`"></div>
                                    </div>
                                    <div class="mt-1.5 flex justify-between text-[11px] font-medium text-zinc-400 tabular-nums">
                                        <template x-for="j in labelTimeline" :key="j"><span x-text="labelJam(j)"></span></template>
                                    </div>
                                </div>
                            </template>

                            <template x-if="memuatHari">
                                <div class="grid grid-cols-4 gap-2 sm:grid-cols-6">
                                    <template x-for="i in 12"><div class="h-10 animate-pulse rounded-lg bg-zinc-100 dark:bg-zinc-800"></div></template>
                                </div>
                            </template>

                            <template x-if="! memuatHari && slot.length === 0">
                                <x-alert tone="warning">Durasi ini tidak muat pada sisa jam operasional hari tersebut. Pilih durasi lebih pendek atau tanggal lain.</x-alert>
                            </template>

                            <div x-show="! memuatHari" class="space-y-4">
                                <template x-for="grup in slotPerJam" :key="grup.jam">
                                    <div>
                                        <p class="mb-2 text-xs font-semibold text-zinc-400" x-text="`Pukul ${grup.jam}.00`"></p>
                                        <div class="grid grid-cols-3 gap-2 sm:grid-cols-6">
                                            <template x-for="s in grup.items" :key="s.jam">
                                                <button type="button" @click="mulai = s.jam" :disabled="! s.tersedia"
                                                    class="h-10 rounded-lg text-sm font-semibold tabular-nums ring-1 transition"
                                                    :class="mulai === s.jam
                                                        ? 'bg-brand-600 text-white ring-brand-600 shadow-sm'
                                                        : (s.tersedia ? 'bg-white text-zinc-700 ring-zinc-200 hover:bg-brand-50 hover:ring-brand-300 dark:bg-zinc-900 dark:text-zinc-200 dark:ring-zinc-700 dark:hover:bg-brand-500/10' : 'cursor-not-allowed bg-zinc-50 text-zinc-300 ring-zinc-100 line-through dark:bg-zinc-900/40 dark:text-zinc-600 dark:ring-zinc-800')"
                                                    x-text="labelJam(s.jam)"></button>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <p class="text-xs text-zinc-500">Jam yang dicoret bertabrakan dengan jadwal lain atau penutupan studio untuk durasi yang dipilih.</p>
                        </div>
                    </x-card>
                </section>

                {{-- ============== LANGKAH 3: DETAIL ============== --}}
                <section x-show="langkah === 3" x-cloak x-transition.opacity class="space-y-6">
                    <x-card title="Detail peminjaman">
                        <div class="space-y-5">
                            <div>
                                <label class="label" for="jumlah_orang">Jumlah orang <span class="text-rose-500">*</span></label>
                                <div class="flex items-center gap-3">
                                    <div class="inline-flex items-center rounded-xl ring-1 ring-zinc-300 dark:ring-zinc-700">
                                        <button type="button" @click="jumlahOrang = Math.max(1, jumlahOrang - 1)" class="grid size-10 place-items-center rounded-l-xl text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-800" aria-label="Kurangi"><x-heroicon-m-minus class="size-4" /></button>
                                        <input id="jumlah_orang" name="jumlah_orang" type="number" min="1" :max="maksOrang" x-model.number="jumlahOrang"
                                            class="h-10 w-14 border-0 bg-transparent text-center text-sm font-semibold tabular-nums focus:ring-0 dark:text-white">
                                        <button type="button" @click="jumlahOrang = Math.min(maksOrang, jumlahOrang + 1)" class="grid size-10 place-items-center rounded-r-xl text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-800" aria-label="Tambah"><x-heroicon-m-plus class="size-4" /></button>
                                    </div>
                                    <span class="text-sm text-zinc-500" x-text="`Maksimal ${maksOrang} orang`"></span>
                                </div>
                                @error('jumlah_orang')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="label" for="ket_keperluan">Keperluan <span class="text-rose-500">*</span></label>
                                <textarea id="ket_keperluan" name="ket_keperluan" rows="3" x-model="keperluan" maxlength="1000" required
                                    placeholder="Contoh: Latihan band untuk tampil di acara Dies Natalis HMIF"
                                    @class(['input resize-y', 'input-error' => $errors->has('ket_keperluan')])></textarea>
                                <div class="mt-1.5 flex justify-between text-xs">
                                    @error('ket_keperluan')
                                        <p class="font-medium text-rose-600">{{ $message }}</p>
                                    @else
                                        <p class="text-zinc-500" :class="keperluan.trim().length > 0 && keperluan.trim().length < 10 && 'text-amber-600'">Minimal 10 karakter.</p>
                                    @enderror
                                    <span class="text-zinc-400 tabular-nums" x-text="`${keperluan.length}/1000`"></span>
                                </div>
                            </div>

                            <x-file-input name="jaminan" label="Foto KTM / KTP (jaminan)" required accept="image/jpeg,image/png,image/webp"
                                ikon="heroicon-o-identification" judul="Unggah foto KTM atau KTP"
                                hint="Pastikan nama dan foto terbaca jelas. Foto besar otomatis diperkecil." />

                            <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/50" x-data="{ sop: false }">
                                <label class="flex cursor-pointer items-start gap-3">
                                    <input type="checkbox" name="setuju_sop" value="1" x-model="setuju" class="checkbox mt-0.5">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                        Saya telah membaca dan akan mematuhi <button type="button" @click.prevent="sop = ! sop" class="link">SOP peminjaman studio</button>, termasuk mengganti alat yang rusak.
                                    </span>
                                </label>
                                <ol x-show="sop" x-collapse x-cloak class="mt-3 ml-7 list-decimal space-y-1.5 pl-4 text-sm text-zinc-600 dark:text-zinc-400">
                                    @foreach (config('studio.sop') as $s)
                                        <li>{{ $s }}</li>
                                    @endforeach
                                </ol>
                                @error('setuju_sop')<p class="mt-1.5 ml-7 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </x-card>
                </section>

                {{-- Navigasi langkah --}}
                <div class="flex items-center justify-between gap-3">
                    <x-button variant="ghost" icon="heroicon-m-arrow-left" x-show="langkah > 1" x-cloak @click="ke(langkah - 1)">Kembali</x-button>
                    <span x-show="langkah === 1"></span>
                    <x-button x-show="langkah === 1" icon-right="heroicon-m-arrow-right" x-bind:disabled="! bolehLanjut1" @click="ke(2)">Pilih jam</x-button>
                    <x-button x-show="langkah === 2" x-cloak icon-right="heroicon-m-arrow-right" x-bind:disabled="! bolehLanjut2" @click="ke(3)">Lanjut ke detail</x-button>
                    <x-button x-show="langkah === 3" x-cloak type="submit" icon="heroicon-m-paper-airplane" x-bind:disabled="! bolehKirim">Kirim pengajuan</x-button>
                </div>
            </div>

            {{-- Ringkasan --}}
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="card overflow-hidden">
                    <template x-if="ruanganAktif">
                        <img :src="ruanganAktif.thumbnail" alt="" class="hidden h-36 w-full object-cover lg:block">
                    </template>
                    <div class="p-5">
                        <p class="text-xs font-bold tracking-wider text-zinc-400 uppercase">Ringkasan</p>
                        <p class="mt-1 text-base font-semibold text-zinc-900 dark:text-white" x-text="ruanganAktif?.nama"></p>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-start gap-3">
                                <x-heroicon-o-calendar class="mt-0.5 size-5 shrink-0 text-zinc-400" />
                                <div>
                                    <dt class="text-xs text-zinc-500">Tanggal</dt>
                                    <dd class="font-medium text-zinc-900 dark:text-white" x-text="tanggal ? labelTanggal(tanggal) : 'Belum dipilih'" :class="! tanggal && 'text-zinc-400 dark:text-zinc-500'"></dd>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <x-heroicon-o-clock class="mt-0.5 size-5 shrink-0 text-zinc-400" />
                                <div>
                                    <dt class="text-xs text-zinc-500">Waktu</dt>
                                    <dd class="font-medium text-zinc-900 dark:text-white" :class="! mulai && 'text-zinc-400 dark:text-zinc-500'"
                                        x-text="mulai ? `${labelJam(mulai)}–${labelJam(selesai)} (${labelDurasi(durasi)})` : 'Belum dipilih'"></dd>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <x-heroicon-o-user-group class="mt-0.5 size-5 shrink-0 text-zinc-400" />
                                <div>
                                    <dt class="text-xs text-zinc-500">Jumlah orang</dt>
                                    <dd class="font-medium text-zinc-900 dark:text-white" x-text="`${jumlahOrang} orang`"></dd>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <x-heroicon-o-map-pin class="mt-0.5 size-5 shrink-0 text-zinc-400" />
                                <div>
                                    <dt class="text-xs text-zinc-500">Lokasi</dt>
                                    <dd class="font-medium text-zinc-900 dark:text-white" x-text="ruanganAktif?.lokasi || @js(config('studio.lokasi'))"></dd>
                                </div>
                            </div>
                        </dl>
                    </div>
                    <div class="border-t border-zinc-100 bg-zinc-50/70 p-5 text-xs leading-relaxed text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/60">
                        <p class="flex gap-2"><x-heroicon-o-information-circle class="size-4 shrink-0" /> Pengajuan ditinjau admin. Status dikabarkan lewat email dan notifikasi.</p>
                        <p class="mt-2 flex gap-2"><x-heroicon-o-shield-check class="size-4 shrink-0" /> Foto KTM/KTP disimpan privat dan hanya dapat dilihat oleh Anda dan pengelola studio.</p>
                    </div>
                </div>
            </aside>
        </form>
    @endif
</x-layouts.app>
