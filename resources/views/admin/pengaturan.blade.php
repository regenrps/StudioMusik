@php
    $hari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
    $hariAktif = array_map('intval', (array) old('hari_operasional', $nilai['hari_operasional']));
@endphp
<x-layouts.app title="Pengaturan" lebar="max-w-5xl">
    <x-page-header title="Pengaturan studio" description="Perubahan langsung berlaku untuk pengajuan baru. Peminjaman yang sudah ada tidak terpengaruh." />

    <form method="POST" action="{{ route('admin.pengaturan.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-card title="Jadwal operasional" description="Menentukan kapan studio bisa dipesan." icon="heroicon-o-clock">
            <div class="space-y-6">
                <div>
                    <p class="label">Hari operasional</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($hari as $n => $nama)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="hari_operasional[]" value="{{ $n }}" @checked(in_array($n, $hariAktif, true)) class="peer sr-only">
                                <span class="inline-flex h-10 items-center rounded-xl px-4 text-sm font-semibold ring-1 ring-zinc-300 transition peer-checked:bg-brand-600 peer-checked:text-white peer-checked:ring-brand-600 peer-focus-visible:outline-2 peer-focus-visible:outline-brand-500 dark:ring-zinc-700">{{ $nama }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('hari_operasional')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <x-input name="jam_buka" type="time" label="Jam buka" :value="$nilai['jam_buka']" required />
                    <x-input name="jam_tutup" type="time" label="Jam tutup" :value="$nilai['jam_tutup']" required />
                    <x-select name="interval_menit" label="Pilihan jam mulai tiap" :options="[5 => '5 menit', 10 => '10 menit', 15 => '15 menit', 30 => '30 menit', 60 => '60 menit']" :value="$nilai['interval_menit']" required />
                    <div></div>
                    <x-input name="durasi_min_menit" type="number" min="15" max="720" step="15" label="Durasi minimum (menit)" :value="$nilai['durasi_min_menit']" required />
                    <x-input name="durasi_maks_menit" type="number" min="15" max="720" step="15" label="Durasi maksimum (menit)" :value="$nilai['durasi_maks_menit']" required />
                </div>
            </div>
        </x-card>

        <x-card title="Aturan pemesanan" icon="heroicon-o-adjustments-horizontal">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <x-input name="batas_hari_pemesanan" type="number" min="1" max="180" label="Pesan paling jauh (hari ke depan)" :value="$nilai['batas_hari_pemesanan']" required />
                <x-input name="jeda_minimal_menit" type="number" min="0" max="2880" label="Ajukan paling lambat (menit sebelum mulai)" :value="$nilai['jeda_minimal_menit']" required hint="Memberi waktu admin untuk meninjau." />
                <x-input name="maks_booking_aktif" type="number" min="0" max="20" label="Maks. peminjaman aktif per orang" :value="$nilai['maks_booking_aktif']" required hint="0 = tanpa batas." />
                <x-input name="maks_orang" type="number" min="1" max="200" label="Maks. orang per sesi" :value="$nilai['maks_orang']" required />
                <x-input name="batas_pembatalan_jam" type="number" min="0" max="168" label="Batal mandiri paling lambat (jam)" :value="$nilai['batas_pembatalan_jam']" required hint="Untuk peminjaman yang sudah disetujui." />
                <x-input name="pengingat_menit" type="number" min="0" max="2880" label="Kirim pengingat (menit sebelum mulai)" :value="$nilai['pengingat_menit']" required hint="0 = tidak mengirim pengingat." />
            </div>
        </x-card>

        <x-card title="Jasa musik" icon="heroicon-o-musical-note">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-input name="jasa_tenggat_min_hari" type="number" min="1" max="60" label="Tenggat minimum (hari dari pemesanan)" :value="$nilai['jasa_tenggat_min_hari']" required />
                <x-input name="jasa_tenggat_maks_hari" type="number" min="1" max="365" label="Tenggat maksimum (hari dari pemesanan)" :value="$nilai['jasa_tenggat_maks_hari']" required />
            </div>
        </x-card>

        <x-card title="Komunikasi" icon="heroicon-o-megaphone">
            <div class="space-y-5">
                <x-input name="email_notifikasi" label="Email tambahan penerima notifikasi admin" :value="$nilai['email_notifikasi']" hint="Pisahkan dengan koma. Semua akun admin aktif otomatis menerima notifikasi." />
                <x-textarea name="pengumuman" label="Pengumuman" :value="$nilai['pengumuman']" rows="2" maxlength="500" hint="Tampil di beranda publik dan dasbor peminjam. Kosongkan untuk menyembunyikan." />
            </div>
        </x-card>

        <div class="sticky bottom-4 z-10 flex justify-end">
            <x-button type="submit" size="lg" icon="heroicon-m-check" class="shadow-lift">Simpan pengaturan</x-button>
        </div>
    </form>
</x-layouts.app>
