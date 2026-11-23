{{-- Data from HomePresenter. A section without content is left out. The meal deals strip comes in Phase 15. --}}
<x-layouts.storefront title="Fresh pizza delivered in Dhaka">
    <h1 class="sr-only">{{ $siteName }}</h1>

    @if ($slides !== [])
        <x-storefront.hero-slider :slides="$slides" />
    @endif
    @if ($badges !== [])
        <x-storefront.trust-strip :badges="$badges" />
    @endif
    @if ($banners !== [])
        <x-storefront.promo-banners :banners="$banners" />
    @endif
    @if ($hotProducts !== [])
        <x-storefront.product-row title="Hot right now" :products="$hotProducts" :href="route('menu.index')" />
    @endif
    @if ($tabs !== [])
        <x-storefront.tabbed-showcase :tabs="$tabs" />
    @endif
    @foreach ($rows as $row)
        <x-storefront.category-row :category="$row" />
    @endforeach
    @if ($builderTitle !== '')
        <x-storefront.builder-teaser :title="$builderTitle" :text="$builderText" />
    @endif
    @if ($hoursText !== '')
        <x-storefront.hours-strip :text="$hoursText" />
    @endif
    @if ($gallery !== [])
        <x-storefront.gallery-grid :images="$gallery" />
    @endif
</x-layouts.storefront>
