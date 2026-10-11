@php $baru = ! $tutorial->exists; @endphp
<x-layouts.app :title="$baru ? 'Tambah Tutorial' : 'Ubah Tutorial'" lebar="max-w-5xl">
    <x-page-header :title="$baru ? 'Tambah tutorial' : 'Ubah tutorial'" :kembali="route('admin.tutorial.index')" kembali-label="Tutorial alat" />

    <form method="POST" action="{{ $baru ? route('admin.tutorial.store') : route('admin.tutorial.update', $tutorial) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @unless ($baru) @method('PUT') @endunless
        <x-card>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-[1fr_280px]">
                <div class="space-y-5">
                    <x-input name="nama_alat" label="Nama alat" :value="$tutorial->nama_alat" required maxlength="120" placeholder="Mis. Mixer Analog – Proel MLX 2842" />
                    <div>
                        <x-input name="kategori" label="Kategori" :value="$tutorial->kategori" maxlength="60" list="daftar-kategori" placeholder="Mis. Amplifier, Mixer, Drum" />
                        <datalist id="daftar-kategori">
                            @foreach ($kategori as $k)<option value="{{ $k }}">@endforeach
                        </datalist>
                    </div>
                    <x-input name="video_url" type="url" label="Tautan video (opsional)" :value="$tutorial->video_url" placeholder="https://youtube.com/watch?v=…" hint="Video YouTube akan disematkan di halaman tutorial." />
                </div>
                <x-file-input name="gambar_alat" label="Foto alat" :existing="$tutorial->gambar_alat ? $tutorial->gambar_url : null" :required="$baru" accept="image/jpeg,image/png,image/webp" />
            </div>
        </x-card>
        <x-card title="Langkah penggunaan" description="Gunakan Markdown: baris diawali '1.' untuk langkah bernomor, '-' untuk poin, **teks** untuk tebal.">
            <x-textarea name="deskripsi" :value="$tutorial->deskripsi" rows="14" required maxlength="20000"
                placeholder="1. Pastikan semua fader dalam posisi 0.&#10;2. Nyalakan mixer terlebih dahulu, baru amplifier.&#10;3. ..." />
        </x-card>
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-button :href="route('admin.tutorial.index')" variant="secondary">Batal</x-button>
            <x-button type="submit" icon="heroicon-m-check">Simpan</x-button>
        </div>
    </form>
</x-layouts.app>
