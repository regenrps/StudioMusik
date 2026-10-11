<x-layouts.auth title="Lupa kata sandi" heading="Lupa kata sandi?" subheading="Masukkan email akun Anda. Kami akan mengirim tautan untuk membuat kata sandi baru.">
    @if (session('status'))
        <x-alert tone="success" class="mb-6">{{ session('status') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <x-input name="email" type="email" label="Email" autocomplete="email" required autofocus />
        <x-button type="submit" size="lg" class="w-full">Kirim tautan atur ulang</x-button>
    </form>

    <p class="mt-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
        Ingat kata sandi? <a href="{{ route('login') }}" class="link">Kembali masuk</a>
    </p>
</x-layouts.auth>
