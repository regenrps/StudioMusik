@php
    $baru = ! $laporan->exists;
    $opsiRuangan = ['' => '— Tidak spesifik —'] + $ruangan->pluck('nama_ruangan', 'id_ruangan')->all();
@endphp
<x-layouts.app :title="$baru ? 'Buat Laporan' : 'Ubah Laporan'" lebar="max-w-4xl">
    <x-page-header :title="$baru ? 'Buat laporan masalah' : 'Ubah laporan'" :kembali="$baru ? route('admin.laporan.index') : route('admin.laporan.show', $laporan)" />

    <form method="POST" action="{{ $baru ? route('admin.laporan.store') : route('admin.laporan.update', $laporan) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @unless ($baru) @method('PUT') @endunless

        @if ($peminjaman)
            <x-alert tone="info" title="Terkait peminjaman {{ $peminjaman->kode }}">
                {{ $peminjaman->user?->username }} · {{ tanggal($peminjaman->tgl_pinjam) }} {{ str_replace(':', '.', $peminjaman->rentang_waktu) }}
            </x-alert>
        @endif

        <x-card>
            <div class="space-y-5">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-select name="jenis_laporan" label="Jenis masalah" :options="array_combine(\App\Models\Laporan::JENIS, \App\Models\Laporan::JENIS)" :value="$laporan->jenis_laporan" placeholder="Pilih jenis" required />
                    <x-input name="tgl_laporan" type="date" label="Tanggal kejadian" :value="$laporan->tgl_laporan?->toDateString()" max="{{ today()->toDateString() }}" required />
                </div>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-select name="id_ruangan" label="Ruangan" :options="$opsiRuangan" :value="$laporan->id_ruangan" />
                    <x-input name="kode_peminjaman" label="Kode peminjaman terkait" :value="$peminjaman?->kode" placeholder="STD-…" hint="Opsional. Hubungkan dengan sesi peminjaman tertentu." />
                </div>
                <x-textarea name="keterangan" label="Uraian masalah" :value="$laporan->keterangan" rows="5" required minlength="10" maxlength="3000" placeholder="Apa yang terjadi, alat apa yang terdampak, dan kondisinya saat ini." />

                @unless ($baru)
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-[220px_1fr]">
                        <x-select name="status" label="Status" :options="\App\Enums\StatusLaporan::options()" :value="$laporan->status" required />
                        <x-textarea name="tindak_lanjut" label="Tindak lanjut" :value="$laporan->tindak_lanjut" rows="3" maxlength="3000" placeholder="Mis. senar diganti, peminjam bertanggung jawab mengganti kabel." />
                    </div>
                @endunless

                <div>
                    <label class="label" for="f-gambar">Foto bukti</label>
                    @if (! $baru && count($laporan->daftarGambar()))
                        <div class="mb-3 grid grid-cols-3 gap-3 sm:grid-cols-6">
                            @foreach ($laporan->daftarGambar() as $i => $g)
                                <label class="group relative block cursor-pointer overflow-hidden rounded-xl" x-data="{ hapus: false }">
                                    <img src="{{ route('berkas.laporan', [$laporan, $i]) }}" alt="" class="aspect-square w-full object-cover transition" :class="hapus && 'opacity-30 grayscale'">
                                    <input type="checkbox" name="hapus_gambar[]" value="{{ $i }}" x-model="hapus" class="checkbox absolute top-2 right-2">
                                    <span x-show="hapus" x-cloak class="absolute inset-x-0 bottom-0 bg-rose-600 py-0.5 text-center text-[10px] font-bold text-white">DIHAPUS</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    <input id="f-gambar" type="file" name="gambar[]" multiple accept="image/jpeg,image/png,image/webp"
                        class="block w-full text-sm text-zinc-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100 dark:text-zinc-400 dark:file:bg-brand-500/10 dark:file:text-brand-300">
                    <p class="mt-1.5 text-xs text-zinc-500">Maks. 6 foto. Foto disimpan privat.</p>
                    @error('gambar.*')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </x-card>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-button :href="route('admin.laporan.index')" variant="secondary">Batal</x-button>
            <x-button type="submit" icon="heroicon-m-check">{{ $baru ? 'Kirim laporan' : 'Simpan perubahan' }}</x-button>
        </div>
    </form>
</x-layouts.app>
