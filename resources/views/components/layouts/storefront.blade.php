@props(['title' => null, 'description' => 'Fresh pizza, pies, pasta and more, delivered across Dhaka.'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' | ' : '' }}{{ config('app.name') }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    {{ $meta ?? '' }}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-screen flex-col bg-cream font-sans text-ink" x-data="{ drawer: false }">
    <a href="#main" class="sr-only z-[60] rounded-btn bg-white px-4 py-2 font-semibold text-brand-700 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>

    <x-storefront.utility-bar />
    <x-storefront.header />
    <x-storefront.navbar />
    <x-storefront.mobile-drawer />

    <main id="main" class="flex-1 pb-20 lg:pb-0">
        <div class="page-container empty:hidden pt-4">
            <x-flash />
        </div>
        {{ $slot }}
    </main>

    <x-storefront.footer />
    <x-storefront.bottom-bar />

    @livewireScripts
    {{ $scripts ?? '' }}
</body>
</html>