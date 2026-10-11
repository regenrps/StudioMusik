<x-layouts.app title="Profil" lebar="max-w-4xl">
    <x-page-header title="Profil & keamanan" description="Kelola data diri yang digunakan untuk peminjaman dan notifikasi." />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[260px_1fr]">
        <aside class="space-y-4">
            <div class="card p-6 text-center">
                <x-avatar :user="$user" size="xl" class="mx-auto" />
                <p class="mt-4 font-semibold text-zinc-900 dark:text-white">{{ $user->username }}</p>
                <p class="text-sm text-zinc-500">{{ $user->email }}</p>
                <x-badge :tone="$user->isStaff() ? 'brand' : 'zinc'" class="mt-3">{{ $user->user_role->label() }}</x-badge>
                <p class="mt-4 text-xs text-zinc-400">Bergabung {{ tanggal($user->created_at, 'F Y') }}</p>
            </div>
            @if ($statistik)
                <div class="card divide-y divide-zinc-100 dark:divide-zinc-800">
                    <div class="flex items-center justify-between px-5 py-3 text-sm"><span class="text-zinc-500">Peminjaman</span><span class="font-semibold tabular-nums">{{ $statistik['peminjaman'] }}</span></div>
                    <div class="flex items-center justify-between px-5 py-3 text-sm"><span class="text-zinc-500">Sesi selesai</span><span class="font-semibold tabular-nums">{{ $statistik['selesai'] }}</span></div>
                    <div class="flex items-center justify-between px-5 py-3 text-sm"><span class="text-zinc-500">Pesanan jasa</span><span class="font-semibold tabular-nums">{{ $statistik['jasa'] }}</span></div>
                </div>
            @endif
        </aside>

        <div class="space-y-6">
            <x-card title="Data diri" description="Nomor WhatsApp dipakai admin untuk menghubungi Anda bila diperlukan." icon="heroicon-o-user">
                <form method="POST" action="{{ route('profil.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')
                    <x-input name="username" label="Nama lengkap" :value="$user->username" required autocomplete="name" />
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-input name="nim_nip" label="NIM / NIP" :value="$user->nim_nip" placeholder="Opsional" />
                        <x-input name="no_wa" type="tel" label="Nomor WhatsApp" :value="$user->no_wa" required inputmode="tel" />
                    </div>
                    <x-input name="email" type="email" label="Email" :value="$user->email" required autocomplete="email" />
                    <div class="flex justify-end">
                        <x-button type="submit" icon="heroicon-m-check">Simpan perubahan</x-button>
                    </div>
                </form>
            </x-card>

            <x-card title="Ganti kata sandi" description="Gunakan kata sandi yang kuat dan tidak dipakai di layanan lain." icon="heroicon-o-lock-closed">
                <form method="POST" action="{{ route('profil.password') }}" class="space-y-5">
                    @csrf
                    @method('PUT')
                    <x-input name="current_password" type="password" label="Kata sandi saat ini" bag="password" required autocomplete="current-password" />
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-input name="password" type="password" label="Kata sandi baru" bag="password" required autocomplete="new-password" hint="Min. 8 karakter, huruf & angka." />
                        <x-input name="password_confirmation" type="password" label="Ulangi kata sandi baru" bag="password" required autocomplete="new-password" />
                    </div>
                    <div class="flex justify-end">
                        <x-button type="submit" variant="secondary" icon="heroicon-m-key">Perbarui kata sandi</x-button>
                    </div>
                </form>
            </x-card>

            <x-card title="Sesi" icon="heroicon-o-arrow-right-on-rectangle">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-zinc-500">Terakhir masuk {{ $user->last_login_at ? relatif($user->last_login_at) : '—' }}.</p>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-button type="submit" variant="danger-soft" icon="heroicon-o-arrow-right-on-rectangle">Keluar</x-button>
                    </form>
                </div>
            </x-card>
        </div>
    </div>
</x-layouts.app>
