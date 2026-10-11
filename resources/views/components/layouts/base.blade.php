@props(['title' => null, 'description' => null, 'bodyClass' => ''])
<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $description ?? 'Pinjam studio musik dan pesan jasa musik (jingle, aransemen, minus one) di '.config('studio.nama').' secara online.' }}">
    <meta name="theme-color" content="#18181b" media="(prefers-color-scheme: dark)">
    <meta name="theme-color" content="#fafafa" media="(prefers-color-scheme: light)">
    <meta property="og:title" content="{{ $title ? $title.' · ' : '' }}{{ config('studio.nama') }}">
    <meta property="og:type" content="website">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('studio.nama') }}</title>
    <link rel="icon" href="{{ asset('assets/img/logo-itera.png') }}" type="image/png">

    {{-- Terapkan tema sebelum render agar tidak berkedip --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('tema') || 'sistem';
                if (t === 'gelap' || (t === 'sistem' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body {{ $attributes->class(['h-full', $bodyClass]) }}>
    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[200] focus:rounded-lg focus:bg-brand-600 focus:px-4 focus:py-2 focus:text-white">
        Lompat ke konten
    </a>

    {{ $slot }}

    <x-toast />
    @stack('scripts')
</body>

</html>
