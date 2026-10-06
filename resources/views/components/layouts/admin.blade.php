@props(['title' => 'Admin'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} | Admin | {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-stone-100 font-sans text-ink" x-data="{ sidebar: false }" x-on:keydown.escape.window="sidebar = false">
    <a href="#main" class="sr-only z-[60] rounded-btn bg-white px-4 py-2 font-semibold text-brand-700 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>

    <div class="lg:flex">
        <x-admin.sidebar />

        <div class="min-w-0 flex-1">
            <x-admin.topbar />
            <main id="main" class="p-4 sm:p-6 lg:p-8">
                <x-flash />
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
    {{ $scripts ?? '' }}
</body>
</html>