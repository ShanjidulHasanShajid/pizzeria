@inject('placeholder', 'App\Modules\Shared\Presentation\Support\PlaceholderData')
<x-layouts.storefront title="Fresh pizza delivered in Dhaka">
    <x-storefront.hero-slider :slides="$placeholder->heroSlides()" />
    <x-storefront.trust-strip :badges="$placeholder->trustBadges()" />
    <x-storefront.promo-banners :banners="$placeholder->promoBanners()" />
    <x-storefront.product-row title="Hot right now" :products="$placeholder->products(8)" :href="route('menu.index')" />
    <x-storefront.tabbed-showcase :tabs="$placeholder->showcaseTabs()" />
    @foreach ($placeholder->featuredCategories() as $category)
        <x-storefront.category-row :category="$category" />
    @endforeach
    <x-storefront.builder-teaser />
    <x-storefront.combos-strip :combos="$placeholder->combos()" />
    <x-storefront.hours-strip :hours="$placeholder->openingHours()" />
    <x-storefront.gallery-grid :images="$placeholder->galleryImages()" />
</x-layouts.storefront>