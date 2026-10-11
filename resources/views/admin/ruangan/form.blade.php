@php $baru = ! $ruangan->exists; @endphp
<x-layouts.app :title="$baru ? 'Tambah Ruangan' : 'Ubah '.$ruangan->nama_ruangan" lebar="max-w-5xl">
    <x-page-header :title="$baru ? 'Tambah ruangan' : 'Ubah ruangan'" :kembali="route('admin.ruangan.index')" kembali-label="Ruangan" />

    <form method="POST" action="{{ $baru ? route('admin.ruangan.store') : route('admin.ruangan.update', $ruangan) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @unless ($baru) @method('PUT') @endunless

        <x-card title="Informasi ruangan" icon="heroicon-o-building-office-2">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-[1fr_280px]">
                <div class="space-y-5">
                    <x-input name="nama_ruangan" label="Nama ruangan" :value="$ruangan->nama_ruangan" required maxlength="100" />
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-input name="kapasitas" type="number" min="1" max="200" label="Kapasitas (orang)" :value="$ruangan->kapasitas" required />
                        <x-input name="lokasi" label="Lokasi" :value="$ruangan->lokasi" maxlength="200" placeholder="Gedung D Lantai 3" />
                    </div>
                    <x-textarea name="deskripsi" label="Deskripsi" :value="$ruangan->deskripsi" rows="4" maxlength="3000" hint="Mendukung Markdown sederhana." />
                    <x-textarea name="fasilitas" label="Fasilitas" :value="$ruangan->fasilitas" rows="5" maxlength="3000" hint="Satu fasilitas per baris, mis. Drum set akustik, Ampli gitar Fender, AC." />
                    <x-checkbox name="is_active" label="Aktif — dapat dipinjam" :checked="$ruangan->is_active" hint="Nonaktifkan sementara saat renovasi. Untuk penutupan terjadwal gunakan menu Jadwal Tutup." />
                </div>
                <x-file-input name="thumbnail" label="Foto utama" :existing="$ruangan->thumbnail ? $ruangan->thumbnail_url : null" :required="$baru" accept="image/jpeg,image/png,image/webp" />
            </div>
        </x-card>

        <x-card title="Galeri foto" description="Foto tambahan yang tampil di halaman detail ruangan." icon="heroicon-o-photo">
            @if (! $baru && $ruangan->foto->isNotEmpty())
                <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ($ruangan->foto as $foto)
                        <div class="group relative overflow-hidden rounded-xl">
                            <img src="{{ $foto->url }}" alt="" class="aspect-[4/3] w-full object-cover">
                            <button type="submit" form="hapus-foto-{{ $foto->id_detail_ruangan }}" class="absolute top-2 right-2 grid size-8 place-items-center rounded-lg bg-white/90 text-rose-600 opacity-0 shadow transition group-hover:opacity-100 focus:opacity-100" aria-label="Hapus foto">
                                <x-heroicon-m-trash class="size-4" />
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
            <label class="label" for="f-foto">Tambah foto</label>
            <input id="f-foto" type="file" name="foto[]" multiple accept="image/jpeg,image/png,image/webp"
                class="block w-full text-sm text-zinc-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100 dark:text-zinc-400 dark:file:bg-brand-500/10 dark:file:text-brand-300">
            <p class="mt-1.5 text-xs text-zinc-500">Boleh memilih beberapa foto sekaligus (maks. 10, masing-masing {{ \App\Support\Berkas::ukuranLabel(config('studio.upload.gambar_maks_kb') * 1024) }}).</p>
            @error('foto.*')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
        </x-card>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-button :href="route('admin.ruangan.index')" variant="secondary">Batal</x-button>
            <x-button type="submit" icon="heroicon-m-check">{{ $baru ? 'Simpan ruangan' : 'Simpan perubahan' }}</x-button>
        </div>
    </form>

    @unless ($baru)
        @foreach ($ruangan->foto as $foto)
            <form id="hapus-foto-{{ $foto->id_detail_ruangan }}" method="POST" action="{{ route('admin.ruangan.foto.destroy', [$ruangan, $foto]) }}" data-konfirmasi="Hapus foto ini?" class="hidden">
                @csrf @method('DELETE')
            </form>
        @endforeach
    @endunless
</x-layouts.app>
