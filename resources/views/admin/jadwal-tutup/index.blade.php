@php
    $opsiRuangan = ['' => 'Semua ruangan'] + $ruangan->pluck('nama_ruangan', 'id_ruangan')->all();
@endphp
<x-layouts.app title="Jadwal Tutup">
    <x-page-header title="Jadwal tutup studio" description="Blokir tanggal/jam saat UTS, UAS, kegiatan UKMBSM, atau perawatan alat. Slot yang ditutup tidak bisa dipesan.">
        <x-button icon="heroicon-m-plus" x-data @click="$dispatch('buka-modal', 'tutup-baru')">Tambah penutupan</x-button>
    </x-page-header>

    @if (session('terdampak'))
        <x-alert tone="warning" title="Ada peminjaman aktif pada rentang ini" class="mb-6">
            {{ session('terdampak')['jumlah'] }} peminjaman aktif bertabrakan dengan penutupan.
            <a href="{{ session('terdampak')['url'] }}" class="font-semibold underline">Tinjau & batalkan bila perlu →</a>
        </x-alert>
    @endif

    <x-tabs class="mb-4" :items="[
        ['label' => 'Mendatang', 'href' => route('admin.jadwal-tutup.index'), 'aktif' => $tab === 'mendatang', 'jumlah' => null],
        ['label' => 'Sudah lewat', 'href' => route('admin.jadwal-tutup.index', ['tab' => 'lalu']), 'aktif' => $tab === 'lalu', 'jumlah' => null],
    ]" />

    <div class="card overflow-hidden">
        @forelse ($jadwal as $t)
            <div class="flex flex-col gap-3 border-b border-zinc-100 px-4 py-4 last:border-0 sm:flex-row sm:items-center sm:px-6 dark:border-zinc-800" x-data>
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800"><x-heroicon-o-no-symbol class="size-5" /></span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $t->keterangan }}</p>
                    <p class="text-sm text-zinc-500">{{ $t->rentang_label }} · {{ $t->ruangan?->nama_ruangan ?? 'Semua ruangan' }}</p>
                    <p class="text-xs text-zinc-400">Dibuat {{ $t->pembuat?->username ?? 'sistem' }} · {{ relatif($t->created_at) }}</p>
                </div>
                <div class="flex gap-2">
                    <x-button variant="secondary" size="sm" icon="heroicon-o-pencil-square" @click="$dispatch('buka-modal', 'tutup-{{ $t->id }}')">Ubah</x-button>
                    <form method="POST" action="{{ route('admin.jadwal-tutup.destroy', $t) }}" data-konfirmasi="Hapus penutupan ini? Slot akan kembali bisa dipesan.">
                        @csrf @method('DELETE')
                        <x-button type="submit" variant="ghost" size="icon-sm" icon="heroicon-o-trash" class="hover:text-rose-600" aria-label="Hapus" />
                    </form>
                </div>
            </div>

            <x-modal :name="'tutup-'.$t->id" title="Ubah penutupan" icon="heroicon-o-no-symbol">
                @include('admin.jadwal-tutup._form', ['aksi' => route('admin.jadwal-tutup.update', $t), 'metode' => 'PUT', 'item' => $t])
            </x-modal>
        @empty
            <x-empty icon="heroicon-o-calendar" :title="$tab === 'mendatang' ? 'Tidak ada penutupan terjadwal' : 'Belum ada riwayat penutupan'" description="Tambahkan penutupan untuk periode ujian atau kegiatan khusus." />
        @endforelse
    </div>

    @if ($jadwal->hasPages())<div class="mt-6">{{ $jadwal->links() }}</div>@endif

    <x-modal name="tutup-baru" title="Tambah penutupan" description="Peminjam tidak dapat memesan slot pada rentang ini." icon="heroicon-o-no-symbol" :show="$errors->any()">
        @include('admin.jadwal-tutup._form', ['aksi' => route('admin.jadwal-tutup.store'), 'metode' => 'POST', 'item' => null])
    </x-modal>
</x-layouts.app>
