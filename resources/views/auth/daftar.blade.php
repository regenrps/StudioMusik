<x-layouts.auth title="Daftar" heading="Buat akun peminjam" subheading="Khusus civitas akademika ITERA. Hanya butuh satu menit.">
    <form method="POST" action="{{ route('register') }}" class="space-y-5" x-data="{ lihat: false, sandi: '' }">
        @csrf
        <x-input name="username" label="Nama lengkap" autocomplete="name" required autofocus placeholder="Sesuai KTM/KTP" />

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-input name="nim_nip" label="NIM / NIP" inputmode="numeric" placeholder="Opsional" hint="Membantu verifikasi admin." />
            <x-input name="no_wa" type="tel" label="Nomor WhatsApp" inputmode="tel" autocomplete="tel" required placeholder="08xxxxxxxxxx" />
        </div>

        <x-input name="email" type="email" label="Email" autocomplete="email" required placeholder="nama@student.itera.ac.id" hint="Notifikasi status peminjaman dikirim ke email ini." />

        <div>
            <label for="f-password" class="label">Kata sandi <span class="text-rose-500">*</span></label>
            <div class="relative">
                <input id="f-password" name="password" :type="lihat ? 'text' : 'password'" x-model="sandi" autocomplete="new-password" required minlength="8"
                    @class(['input pr-11', 'input-error' => $errors->has('password')])>
                <button type="button" @click="lihat = ! lihat" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-zinc-400 hover:text-zinc-600" aria-label="Tampilkan kata sandi">
                    <x-heroicon-o-eye class="size-5" x-show="! lihat" />
                    <x-heroicon-o-eye-slash class="size-5" x-show="lihat" x-cloak />
                </button>
            </div>
            {{-- Indikator kekuatan --}}
            <div class="mt-2 flex gap-1" aria-hidden="true">
                @foreach ([1, 2, 3, 4] as $i)
                    <span class="h-1 flex-1 rounded-full bg-zinc-200 transition dark:bg-zinc-800"
                        :class="(() => { let s = 0; if (sandi.length >= 8) s++; if (/[A-Za-z]/.test(sandi) && /\d/.test(sandi)) s++; if (/[A-Z]/.test(sandi) && /[a-z]/.test(sandi)) s++; if (/[^A-Za-z0-9]/.test(sandi) || sandi.length >= 12) s++; return s >= {{ $i }} ? ['', 'bg-rose-500', 'bg-amber-500', 'bg-sky-500', 'bg-emerald-500'][s] : '' })()"></span>
                @endforeach
            </div>
            @error('password')
                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
            @else
                <p class="mt-1.5 text-xs text-zinc-500">Minimal 8 karakter, kombinasi huruf dan angka.</p>
            @enderror
        </div>

        <x-input name="password_confirmation" type="password" label="Ulangi kata sandi" autocomplete="new-password" required />

        <x-checkbox name="setuju">
            Saya menyetujui <a href="{{ route('beranda') }}#sop" target="_blank" class="link">SOP peminjaman studio</a> dan bertanggung jawab atas alat yang digunakan.
        </x-checkbox>

        <x-button type="submit" size="lg" class="w-full">Buat akun</x-button>
    </form>

    <p class="mt-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
        Sudah punya akun? <a href="{{ route('login') }}" class="link">Masuk</a>
    </p>
</x-layouts.auth>
