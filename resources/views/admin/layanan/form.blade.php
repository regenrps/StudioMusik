@php
    $baru = ! $jasa->exists;
    $fieldAwal = old('fields', $jasa->exists
        ? $jasa->field->map(fn ($f) => ['id' => $f->id, 'nama_field' => $f->nama_field, 'jenis_field' => $f->jenis_field->value, 'is_required' => $f->is_required, 'bantuan' => $f->bantuan])->values()->all()
        : [
            ['id' => null, 'nama_field' => 'Genre', 'jenis_field' => 'text', 'is_required' => true, 'bantuan' => 'Mis. pop, rock, EDM'],
            ['id' => null, 'nama_field' => 'Durasi', 'jenis_field' => 'text', 'is_required' => true, 'bantuan' => 'Mis. 1 menit 30 detik'],
            ['id' => null, 'nama_field' => 'File referensi', 'jenis_field' => 'file', 'is_required' => false, 'bantuan' => 'Contoh musik yang diinginkan'],
        ]);
    $fieldAwal = array_map(fn ($f) => ['id' => $f['id'] ?? null, 'nama_field' => $f['nama_field'] ?? '', 'jenis_field' => $f['jenis_field'] ?? 'text', 'is_required' => (bool) ($f['is_required'] ?? false), 'bantuan' => $f['bantuan'] ?? ''], $fieldAwal);
@endphp
<x-layouts.app :title="$baru ? 'Tambah Layanan' : 'Ubah '.$jasa->nama_jenis_jasa" lebar="max-w-5xl">
    <x-page-header :title="$baru ? 'Tambah layanan' : 'Ubah layanan'" :kembali="route('admin.layanan.index')" kembali-label="Layanan jasa" />

    <form method="POST" action="{{ $baru ? route('admin.layanan.store') : route('admin.layanan.update', $jasa) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @unless ($baru) @method('PUT') @endunless

        <x-card title="Informasi layanan" icon="heroicon-o-information-circle">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-[1fr_280px]">
                <div class="space-y-5">
                    <x-input name="nama_jenis_jasa" label="Nama layanan" :value="$jasa->nama_jenis_jasa" required maxlength="80" placeholder="Mis. Jingle" />
                    <x-input name="ringkasan" label="Ringkasan" :value="$jasa->ringkasan" maxlength="200" hint="Satu kalimat yang tampil di kartu layanan." />
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-input name="estimasi_hari" type="number" min="1" max="180" label="Estimasi pengerjaan (hari)" :value="$jasa->estimasi_hari" required />
                        <x-input name="harga_mulai" type="number" min="0" label="Harga mulai (Rp)" :value="$jasa->harga_mulai" hint="Kosongkan bila gratis untuk civitas." prefix="Rp" />
                    </div>
                    <x-checkbox name="is_active" label="Aktif — tampil di katalog dan bisa dipesan" :checked="$jasa->is_active" />
                </div>
                <x-file-input name="gambar" label="Gambar sampul" :existing="$jasa->gambar ? $jasa->gambar_url : null" :required="$baru" accept="image/jpeg,image/png,image/webp" />
            </div>
        </x-card>

        <x-card title="Deskripsi & ketentuan" description="Mendukung format Markdown sederhana: **tebal**, daftar bernomor (1. ...), dan daftar poin (- ...)." icon="heroicon-o-document-text">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <x-textarea name="deskripsi" label="Deskripsi" :value="$jasa->deskripsi" rows="10" required maxlength="5000" />
                <x-textarea name="sk" label="Syarat & ketentuan" :value="$jasa->sk" rows="10" required maxlength="5000" />
            </div>
        </x-card>

        <x-card title="Formulir pemesanan" description="Isian yang harus dilengkapi pemesan. Urutan di sini sama dengan urutan di formulir." icon="heroicon-o-queue-list">
            <div x-data="fieldBuilder(@js($fieldAwal))" class="space-y-3">
                <template x-if="fields.length === 0">
                    <p class="rounded-xl border-2 border-dashed border-zinc-200 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700">Belum ada field. Pemesan hanya akan mengisi judul, tenggat, dan catatan.</p>
                </template>
                <template x-for="(f, i) in fields" :key="f._k">
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 p-4 dark:border-zinc-700 dark:bg-zinc-800/30">
                        <input type="hidden" :name="`fields[${i}][id]`" :value="f.id ?? ''">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                            <span class="hidden size-7 shrink-0 place-items-center rounded-lg bg-white text-xs font-bold text-zinc-500 ring-1 ring-zinc-200 sm:grid dark:bg-zinc-900 dark:ring-zinc-700" x-text="i + 1"></span>
                            <div class="grid grid-cols-1 flex-1 gap-3 sm:grid-cols-[1fr_180px]">
                                <input type="text" data-nama-field :name="`fields[${i}][nama_field]`" x-model="f.nama_field" required maxlength="80" placeholder="Nama field, mis. Lirik" class="input" aria-label="Nama field">
                                <select :name="`fields[${i}][jenis_field]`" x-model="f.jenis_field" class="input" aria-label="Jenis field">
                                    @foreach (\App\Enums\TipeField::options() as $nilai => $label)
                                        <option value="{{ $nilai }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input type="text" :name="`fields[${i}][bantuan]`" x-model="f.bantuan" maxlength="200" placeholder="Teks bantuan (opsional)" class="input sm:col-span-2" aria-label="Teks bantuan">
                            </div>
                            <div class="flex items-center gap-1 sm:flex-col sm:items-end">
                                <label class="mr-auto inline-flex items-center gap-2 text-xs font-medium text-zinc-600 sm:mr-0 dark:text-zinc-300">
                                    <input type="hidden" :name="`fields[${i}][is_required]`" :value="f.is_required ? 1 : 0">
                                    <input type="checkbox" x-model="f.is_required" class="checkbox"> Wajib
                                </label>
                                <div class="flex">
                                    <button type="button" @click="geser(i, -1)" :disabled="i === 0" class="rounded-lg p-1.5 text-zinc-400 hover:bg-white hover:text-zinc-700 disabled:opacity-30 dark:hover:bg-zinc-800" aria-label="Naikkan"><x-heroicon-m-arrow-up class="size-4" /></button>
                                    <button type="button" @click="geser(i, 1)" :disabled="i === fields.length - 1" class="rounded-lg p-1.5 text-zinc-400 hover:bg-white hover:text-zinc-700 disabled:opacity-30 dark:hover:bg-zinc-800" aria-label="Turunkan"><x-heroicon-m-arrow-down class="size-4" /></button>
                                    <button type="button" @click="hapus(i)" class="rounded-lg p-1.5 text-zinc-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10" aria-label="Hapus field"><x-heroicon-m-trash class="size-4" /></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <div class="flex flex-wrap gap-2 pt-1">
                    <x-button variant="soft" size="sm" icon="heroicon-m-plus" @click="tambah('text')">Teks</x-button>
                    <x-button variant="soft" size="sm" icon="heroicon-m-plus" @click="tambah('textarea')">Paragraf</x-button>
                    <x-button variant="soft" size="sm" icon="heroicon-m-plus" @click="tambah('file')">Unggah berkas</x-button>
                    <x-button variant="soft" size="sm" icon="heroicon-m-plus" @click="tambah('url')">Tautan</x-button>
                </div>
                @if ($errors->has('fields.*'))
                    <x-alert tone="danger">Periksa kembali field formulir: {{ $errors->first('fields.*') }}</x-alert>
                @endif
            </div>
        </x-card>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-button :href="route('admin.layanan.index')" variant="secondary">Batal</x-button>
            <x-button type="submit" icon="heroicon-m-check">{{ $baru ? 'Simpan layanan' : 'Simpan perubahan' }}</x-button>
        </div>
    </form>
</x-layouts.app>
