<x-layouts.auth title="Atur ulang kata sandi" heading="Buat kata sandi baru" subheading="Gunakan minimal 8 karakter dengan kombinasi huruf dan angka.">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-input name="email" type="email" label="Email" :value="$email" autocomplete="email" required />
        <x-input name="password" type="password" label="Kata sandi baru" autocomplete="new-password" required autofocus />
        <x-input name="password_confirmation" type="password" label="Ulangi kata sandi baru" autocomplete="new-password" required />
        <x-button type="submit" size="lg" class="w-full">Simpan kata sandi</x-button>
    </form>
</x-layouts.auth>
