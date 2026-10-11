@php
    $sepanjangHari = $item ? $item->sepanjangHari() : (bool) old('sepanjang_hari', true);
    $pref = 'jt-'.($item?->id ?? 'baru');
@endphp
<form method="POST" action="{{ $aksi }}" class="space-y-4" x-data="{ sepanjang: @js($sepanjangHari) }">
    @csrf
    @if ($metode !== 'POST') @method($metode) @endif

    <x-input name="keterangan" id="{{ $pref }}-keterangan" label="Keterangan" :value="$item?->keterangan" required maxlength="150" placeholder="Mis. UAS Semester Ganjil" />
    <x-select name="id_ruangan" id="{{ $pref }}-id_ruangan" label="Ruangan" :options="$opsiRuangan" :value="$item?->id_ruangan" />
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-input name="tgl_mulai" id="{{ $pref }}-tgl_mulai" type="date" label="Dari tanggal" :value="$item?->tgl_mulai?->toDateString()" required />
        <x-input name="tgl_selesai" id="{{ $pref }}-tgl_selesai" type="date" label="Sampai tanggal" :value="$item?->tgl_selesai?->toDateString()" required />
    </div>
    <label class="flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">
        <input type="hidden" name="sepanjang_hari" :value="sepanjang ? 1 : 0">
        <input type="checkbox" x-model="sepanjang" class="checkbox"> Tutup sepanjang hari
    </label>
    <div x-show="! sepanjang" x-collapse class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-input name="jam_mulai" id="{{ $pref }}-jam_mulai" type="time" label="Jam mulai" :value="$item?->jam_mulai ? substr($item->jam_mulai, 0, 5) : null" step="600" />
        <x-input name="jam_selesai" id="{{ $pref }}-jam_selesai" type="time" label="Jam selesai" :value="$item?->jam_selesai ? substr($item->jam_selesai, 0, 5) : null" step="600" />
    </div>
    <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
        <x-button variant="secondary" @click="$dispatch('tutup-modal')">Batal</x-button>
        <x-button type="submit" icon="heroicon-m-check">Simpan</x-button>
    </div>
</form>
