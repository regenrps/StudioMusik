@php
    use App\Enums\Role;
    $saya = auth()->user();
    $tab = fn (string $label, ?string $peran) => ['label' => $label, 'href' => route('admin.pengguna.index', array_filter(['peran' => $peran, 'q' => $filter['q'] ?: null, 'status' => $filter['status']])), 'aktif' => $filter['peran'] === $peran, 'jumlah' => $peran ? (int) ($hitung[$peran] ?? 0) : $hitung->sum()];
@endphp
<x-layouts.app title="Pengguna">
    <x-page-header title="Pengguna" description="Kelola akun peminjam dan staf, termasuk peran dan status aktif.">
        <x-button icon="heroicon-m-user-plus" x-data @click="$dispatch('buka-modal', 'tambah-pengguna')">Tambah akun</x-button>
    </x-page-header>

    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <x-tabs :items="[$tab('Semua', null), $tab('Peminjam', 'user'), $tab('Admin', 'admin'), $tab('K3L', 'k3l')]" />
        <form method="GET" class="flex gap-2" x-data>
            @if ($filter['peran'])<input type="hidden" name="peran" value="{{ $filter['peran'] }}">@endif
            <div class="relative flex-1 lg:w-72">
                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-zinc-400" />
                <input type="search" name="q" value="{{ $filter['q'] }}" placeholder="Nama, email, NIM, WA…" class="input pl-11" aria-label="Cari pengguna">
            </div>
            <select name="status" class="input w-36" @change="$el.form.submit()" aria-label="Status">
                <option value="">Semua status</option>
                <option value="aktif" @selected($filter['status'] === 'aktif')>Aktif</option>
                <option value="nonaktif" @selected($filter['status'] === 'nonaktif')>Nonaktif</option>
            </select>
        </form>
    </div>

    <div class="card overflow-hidden">
        @forelse ($pengguna as $u)
            <div class="flex flex-col gap-3 border-b border-zinc-100 px-4 py-4 last:border-0 sm:flex-row sm:items-center sm:px-6 dark:border-zinc-800" x-data>
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <x-avatar :user="$u" size="sm" :class="$u->is_active ? '' : 'opacity-40'" />
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate font-semibold text-zinc-900 dark:text-white">{{ $u->username }}</p>
                            <x-badge size="sm" :tone="$u->user_role === Role::Admin ? 'brand' : ($u->user_role === Role::K3L ? 'sky' : 'zinc')">{{ $u->user_role->label() }}</x-badge>
                            @unless ($u->is_active)<x-badge size="sm" tone="rose">Nonaktif</x-badge>@endunless
                            @if ($u->is($saya))<x-badge size="sm" tone="emerald">Anda</x-badge>@endif
                        </div>
                        <p class="truncate text-sm text-zinc-500">{{ $u->email }} · {{ $u->no_wa }}@if ($u->nim_nip) · {{ $u->nim_nip }}@endif</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 pl-11 text-xs text-zinc-500 sm:pl-0">
                    <span title="Peminjaman"><x-heroicon-m-calendar-days class="mr-0.5 inline size-4" />{{ $u->peminjaman_count }}</span>
                    <span title="Pesanan jasa"><x-heroicon-m-musical-note class="mr-0.5 inline size-4" />{{ $u->pesanan_jasa_count }}</span>
                    @if ($u->tidak_hadir_count)<span class="text-orange-600" title="Tidak hadir"><x-heroicon-m-user-minus class="mr-0.5 inline size-4" />{{ $u->tidak_hadir_count }}</span>@endif
                    <span class="hidden w-28 text-right md:inline" title="Terakhir masuk">{{ $u->last_login_at ? relatif($u->last_login_at) : 'belum masuk' }}</span>
                    <x-dropdown width="w-52">
                        <x-slot:trigger>
                            <x-button variant="ghost" size="icon-sm" icon="heroicon-m-ellipsis-vertical" aria-label="Aksi" />
                        </x-slot:trigger>
                        <x-dropdown-link type="button" icon="heroicon-o-pencil-square" @click="$dispatch('buka-modal', 'ubah-{{ $u->id_user }}')">Ubah peran / sandi</x-dropdown-link>
                        @if ($u->whatsapp_url)<x-dropdown-link :href="$u->whatsapp_url" icon="heroicon-o-chat-bubble-left-right" target="_blank">WhatsApp</x-dropdown-link>@endif
                        @unless ($u->is($saya))
                            <form method="POST" action="{{ route('admin.pengguna.status', $u) }}" data-konfirmasi="{{ $u->is_active ? 'Nonaktifkan akun ini? Pengguna tidak dapat masuk.' : 'Aktifkan kembali akun ini?' }}">
                                @csrf
                                <x-dropdown-link :icon="$u->is_active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open'" :danger="$u->is_active">{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</x-dropdown-link>
                            </form>
                        @endunless
                    </x-dropdown>
                </div>
            </div>

            <x-modal :name="'ubah-'.$u->id_user" :title="'Ubah akun '.$u->username" icon="heroicon-o-user">
                <form method="POST" action="{{ route('admin.pengguna.update', $u) }}" class="space-y-4">
                    @csrf @method('PUT')
                    @if ($u->is($saya))
                        <input type="hidden" name="user_role" value="{{ $u->user_role->value }}">
                        <x-alert tone="info">Anda tidak dapat mengubah peran akun sendiri.</x-alert>
                    @else
                        <x-select name="user_role" :id="'peran-'.$u->id_user" label="Peran" :options="Role::options()" :value="$u->user_role" required />
                    @endif
                    <x-input name="password" :id="'sandi-'.$u->id_user" type="password" label="Atur ulang kata sandi" autocomplete="new-password" hint="Kosongkan jika tidak ingin mengubah. Min. 8 karakter, huruf & angka." />
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <x-button variant="secondary" @click="$dispatch('tutup-modal')">Batal</x-button>
                        <x-button type="submit">Simpan</x-button>
                    </div>
                </form>
            </x-modal>
        @empty
            <x-empty icon="heroicon-o-users" title="Pengguna tidak ditemukan" />
        @endforelse
    </div>
    @if ($pengguna->hasPages())<div class="mt-6">{{ $pengguna->links() }}</div>@endif

    <x-modal name="tambah-pengguna" title="Tambah akun" description="Buat akun staf (admin/K3L) atau peminjam secara manual." icon="heroicon-o-user-plus" :show="$errors->tambah->any()">
        <form method="POST" action="{{ route('admin.pengguna.store') }}" class="space-y-4">
            @csrf
            <x-input name="username" id="baru-username" label="Nama lengkap" bag="tambah" required />
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-input name="email" id="baru-email" type="email" label="Email" bag="tambah" required />
                <x-input name="no_wa" id="baru-wa" type="tel" label="WhatsApp" bag="tambah" required placeholder="08…" />
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-select name="user_role" id="baru-peran" label="Peran" :options="Role::options()" value="k3l" bag="tambah" required />
                <x-input name="nim_nip" id="baru-nim" label="NIM / NIP" bag="tambah" />
            </div>
            <x-input name="password" id="baru-sandi" type="text" label="Kata sandi awal" bag="tambah" required :value="\Illuminate\Support\Str::password(10, symbols: false)" hint="Bagikan ke pemilik akun dan minta segera menggantinya." />
            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <x-button variant="secondary" @click="$dispatch('tutup-modal')">Batal</x-button>
                <x-button type="submit" icon="heroicon-m-user-plus">Buat akun</x-button>
            </div>
        </form>
    </x-modal>
</x-layouts.app>
