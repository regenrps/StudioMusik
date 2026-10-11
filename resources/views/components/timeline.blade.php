@props(['log'])
{{-- Riwayat aktivitas (LogAktivitas) suatu data --}}
@if ($log->isEmpty())
    <p class="text-sm text-zinc-500">Belum ada riwayat.</p>
@else
    <ol class="relative space-y-5">
        @foreach ($log as $item)
            @php
                $ikon = match ($item->aksi) {
                    'diajukan' => ['heroicon-m-paper-airplane', 'bg-brand-500'],
                    'disetujui' => ['heroicon-m-check', 'bg-sky-500'],
                    'ditolak' => ['heroicon-m-x-mark', 'bg-rose-500'],
                    'dibatalkan' => ['heroicon-m-no-symbol', 'bg-zinc-400'],
                    'checkin', 'diproduksi' => ['heroicon-m-play', 'bg-violet-500'],
                    'checkout', 'selesai', 'diselesaikan' => ['heroicon-m-check-badge', 'bg-emerald-500'],
                    'diulas' => ['heroicon-m-star', 'bg-amber-500'],
                    'kedaluwarsa', 'tidak_hadir' => ['heroicon-m-clock', 'bg-orange-500'],
                    default => ['heroicon-m-pencil', 'bg-zinc-400'],
                };
            @endphp
            <li class="relative flex gap-3">
                @if (! $loop->last)
                    <span class="absolute top-7 bottom-[-1.25rem] left-[0.6875rem] w-px bg-zinc-200 dark:bg-zinc-800" aria-hidden="true"></span>
                @endif
                <span class="relative z-10 grid size-6 shrink-0 place-items-center rounded-full text-white ring-4 ring-white dark:ring-zinc-900 {{ $ikon[1] }}">
                    <x-dynamic-component :component="$ikon[0]" class="size-3.5" />
                </span>
                <div class="min-w-0 flex-1 pt-0.5">
                    <p class="text-sm text-zinc-800 dark:text-zinc-200">
                        {{ $item->deskripsi }}
                    </p>
                    @if (! empty($item->properti['alasan']) || ! empty($item->properti['catatan']))
                        <p class="mt-1 rounded-lg bg-zinc-50 px-3 py-2 text-sm text-zinc-600 dark:bg-zinc-800/60 dark:text-zinc-300">
                            “{{ $item->properti['alasan'] ?? $item->properti['catatan'] }}”
                        </p>
                    @endif
                    <p class="mt-1 text-xs text-zinc-500">
                        {{ $item->user ? $item->user->username.($item->user->isStaff() ? ' ('.$item->user->user_role->label().')' : '') : 'Sistem' }}
                        · <time datetime="{{ $item->created_at->toIso8601String() }}" title="{{ tanggal_waktu($item->created_at) }}">{{ relatif($item->created_at) }}</time>
                    </p>
                </div>
            </li>
        @endforeach
    </ol>
@endif
