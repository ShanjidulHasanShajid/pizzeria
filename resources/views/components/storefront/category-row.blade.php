@props(['category'])
<section class="page-container py-8">
    <x-storefront.section-heading :title="$category['name']" :href="route('categories.show', $category['slug'])" />
    <ul class="mt-5 grid gap-4 sm:grid-cols-3">
        @foreach ($category['products'] as $product)
            <li><x-storefront.product-card :product="$product" /></li>
        @endforeach
    </ul>
</section>