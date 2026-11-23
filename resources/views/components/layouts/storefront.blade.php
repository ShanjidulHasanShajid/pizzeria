{{-- $site (SiteLayout), $logoUrl and $faviconUrl come from StorefrontLayoutComposer. --}}
@props(['title' => null, 'description' => 'Fresh pizza, pies, pasta and more, delivered across Dhaka.'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' | ' : '' }}{{ $site->settings->siteName }}</title>
    <meta name="description" content="{{ $description }}">
    @if ($faviconUrl)
        <link rel="icon" href="{{ $faviconUrl }}" type="image/webp">
    @else
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @endif
    {{ $meta ?? '' }}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-screen flex-col bg-cream font-sans text-ink" x-data="{ drawer: false }">
    <a href="#main" class="sr-only z-[60] rounded-btn bg-white px-4 py-2 font-semibold text-brand-700 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>

    <x-storefront.utility-bar :notice="$site->settings->deliveryNotice" :phone="$site->settings->phone" />
    <x-storefront.header :site-name="$site->settings->siteName" :logo-url="$logoUrl" />
    <x-storefront.navbar :items="$site->navItems" :groups="$site->menuGroups" />
    <x-storefront.mobile-drawer :site-name="$site->settings->siteName" :items="$site->navItems" :groups="$site->menuGroups" />

    <main id="main" class="flex-1 pb-20 lg:pb-0">
        <div class="page-container empty:hidden pt-4">
            <x-flash />
        </div>
        {{ $slot }}
    </main>

    <x-storefront.footer :settings="$site->settings" :locations="$site->locations" :logo-url="$logoUrl" />
    <x-storefront.bottom-bar />

    @livewireScripts
    {{ $scripts ?? '' }}
</body>
</html>
