@props(['title', 'products', 'href' => null])
<section class="page-container py-8">
    <x-storefront.section-heading :title="$title" :href="$href" />
    <ul class="-mx-4 mt-5 flex snap-x gap-4 overflow-x-auto px-4 pb-3 sm:mx-0 sm:px-0">
        @foreach ($products as $product)
            <li class="w-64 shrink-0 snap-start">
                <x-storefront.product-card :product="$product" />
            </li>
        @endforeach
    </ul>
</section>