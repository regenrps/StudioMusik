@php
    use App\Enums\TipeField;
    $upload = config('studio.upload');
@endphp
<x-layouts.app :title="'Pesan '.$jasa->nama_jenis_jasa">
    <x-page-header :title="'Pesan '.$jasa->nama_jenis_jasa" description="Semakin jelas detail yang Anda berikan, semakin sesuai hasilnya." :kembali="route('akun.jasa.index')" kembali-label="Jasa musik" />

    <form method="POST" action="{{ route('akun.jasa.store', $jasa) }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_340px]"
        x-data="{ setuju: {{ old('setuju_sk') ? 'true' : 'false' }} }">
        @csrf
        <div class="min-w-0 space-y-6">
            @if ($errors->any())
                <x-alert tone="danger" title="Periksa kembali isian Anda">
                    <ul class="list-disc space-y-0.5 pl-4">
                        @foreach ($errors->all() as $pesan)<li>{{ $pesan }}</li>@endforeach
                    </ul>
                    @if ($jasa->field->contains(fn ($f) => $f->jenis_field === TipeField::Berkas))
                        <p class="mt-2 font-medium">Berkas lampiran perlu dipilih ulang.</p>
                    @endif
                </x-alert>
            @endif

            <x-card title="Tentang proyek" icon="heroicon-o-pencil-square">
                <div class="space-y-5">
                    <x-input name="judul" label="Judul proyek" required maxlength="120" placeholder="Mis. Jingle PPLK ITERA 2026" hint="Nama singkat agar mudah dikenali." />
                    <x-input name="tenggat_produksi" type="date" label="Tenggat yang diinginkan" required
                        :value="$tenggatSaran->toDateString()" min="{{ $tenggatMin->toDateString() }}" max="{{ $tenggatMaks->toDateString() }}"
                        :hint="'Antara '.tanggal($tenggatMin).' dan '.tanggal($tenggatMaks).'. Estimasi pengerjaan layanan ini ± '.$jasa->estimasi_hari.' hari.'" />
                </div>
            </x-card>

            @if ($jasa->field->isNotEmpty())
                <x-card title="Detail kebutuhan" icon="heroicon-o-clipboard-document-list">
                    <div class="space-y-5">
                        @foreach ($jasa->field as $field)
                            @php
                                $nama = 'fields['.$field->input_name.']';
                            @endphp
                            @switch($field->jenis_field)
                                @case(TipeField::Paragraf)
                                    <x-textarea :name="$nama" :label="$field->nama_field" :hint="$field->bantuan" rows="5" maxlength="5000" :required="$field->is_required" />
                                    @break
                                @case(TipeField::Angka)
                                    <x-input :name="$nama" type="number" step="any" min="0" :label="$field->nama_field" :hint="$field->bantuan" :required="$field->is_required" />
                                    @break
                                @case(TipeField::Tautan)
                                    <x-input :name="$nama" type="url" :label="$field->nama_field" :hint="$field->bantuan ?? 'Mis. tautan YouTube, Google Drive, atau Spotify.'" placeholder="https://" :required="$field->is_required" />
                                    @break
                                @case(TipeField::Berkas)
                                    <x-file-input :name="$field->input_name" :label="$field->nama_field" :kompres="false" :maks-kb="$upload['audio_maks_kb']"
                                        accept=".{{ implode(',.', $upload['lampiran_mimes']) }}" ikon="heroicon-o-musical-note" judul="Pilih berkas atau seret ke sini"
                                        :hint="($field->bantuan ? $field->bantuan.' · ' : '').'Format: '.strtoupper(implode(', ', $upload['lampiran_mimes'])).' · maks. '.\App\Support\Berkas::ukuranLabel($upload['audio_maks_kb'] * 1024)"
                                        :required="$field->is_required" />
                                    @break
                                @default
                                    <x-input :name="$nama" :label="$field->nama_field" :hint="$field->bantuan" maxlength="255" :required="$field->is_required" />
                            @endswitch
                        @endforeach
                    </div>
                </x-card>
            @endif

            <x-card title="Catatan tambahan" icon="heroicon-o-chat-bubble-bottom-center-text">
                <x-textarea name="keterangan" rows="4" maxlength="2000" placeholder="Nuansa, tempo, instrumen yang diinginkan, atau hal lain yang perlu diketahui tim (opsional)." />
            </x-card>

            <div class="card p-5">
                <label class="flex cursor-pointer items-start gap-3">
                    <input type="checkbox" name="setuju_sk" value="1" x-model="setuju" class="checkbox mt-0.5">
                    <span class="text-sm text-zinc-700 dark:text-zinc-300">Saya telah membaca dan menyetujui <a href="{{ route('layanan.show', $jasa) }}#sk" target="_blank" class="link">syarat & ketentuan layanan {{ $jasa->nama_jenis_jasa }}</a>.</span>
                </label>
                @error('setuju_sk')<p class="mt-1.5 ml-7 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                <x-button type="submit" size="lg" class="mt-5 w-full sm:w-auto" icon="heroicon-m-paper-airplane" x-bind:disabled="! setuju">Kirim pesanan</x-button>
            </div>
        </div>

        <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
            <div class="card overflow-hidden">
                <img src="{{ $jasa->gambar_url }}" alt="" class="h-36 w-full object-cover">
                <div class="p-5">
                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $jasa->nama_jenis_jasa }}</p>
                    <dl class="mt-3 divide-y divide-zinc-100 dark:divide-zinc-800">
                        <x-info label="Estimasi" icon="heroicon-o-clock">± {{ $jasa->estimasi_hari }} hari</x-info>
                        <x-info label="Biaya" icon="heroicon-o-banknotes">{{ $jasa->harga_label }}</x-info>
                    </dl>
                </div>
            </div>
            <div class="card p-5" id="sk">
                <p class="text-sm font-semibold text-zinc-900 dark:text-white">Syarat & ketentuan</p>
                <div class="prose-konten mt-3 max-h-72 overflow-y-auto pr-1 text-xs scrollbar-tipis">{!! konten($jasa->sk) !!}</div>
            </div>
        </aside>
    </form>
</x-layouts.app>
