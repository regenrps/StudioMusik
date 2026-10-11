<x-layouts.auth title="Masuk" heading="Selamat datang kembali" subheading="Masuk untuk meminjam studio dan memantau pesanan Anda.">
    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        <x-input name="email" type="email" label="Email" autocomplete="email" required autofocus placeholder="nama@student.itera.ac.id" />

        <div x-data="{ lihat: false }">
            <div class="mb-1.5 flex items-center justify-between">
                <label for="f-password" class="label mb-0">Kata sandi</label>
                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400">Lupa kata sandi?</a>
            </div>
            <div class="relative">
                <input id="f-password" name="password" :type="lihat ? 'text' : 'password'" autocomplete="current-password" required
                    @class(['input pr-11', 'input-error' => $errors->has('password')])>
                <button type="button" @click="lihat = ! lihat" class="absolute inset-y-0 right-0 grid w-11 place-items-center text-zinc-400 hover:text-zinc-600" :aria-label="lihat ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                    <x-heroicon-o-eye class="size-5" x-show="! lihat" />
                    <x-heroicon-o-eye-slash class="size-5" x-show="lihat" x-cloak />
                </button>
            </div>
            @error('password')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
        </div>

        <x-checkbox name="ingat" label="Ingat saya di perangkat ini" />

        <x-button type="submit" size="lg" class="w-full">Masuk</x-button>
    </form>

    <p class="mt-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
        Belum punya akun?
        <a href="{{ route('register') }}" class="link">Daftar gratis</a>
    </p>
</x-layouts.auth>
