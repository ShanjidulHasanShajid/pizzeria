@props(['combos'])
<section class="my-8 bg-brand-50 py-10">
    <div class="page-container">
        <x-storefront.section-heading title="Meal deals" :href="route('meal-deals.index')" />
        <ul class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($combos as $combo)
                <li><x-storefront.product-card :product="$combo" /></li>
            @endforeach
        </ul>
    </div>
</section>