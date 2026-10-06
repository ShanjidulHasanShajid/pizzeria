@props(['tabs'])
<section class="page-container py-8">
    <x-tabs :tabs="collect($tabs)->map(fn ($tab) => $tab['label'])->all()">
        @foreach ($tabs as $key => $tab)
            <div x-show="tab === @js($key)" @if (! $loop->first) x-cloak @endif role="tabpanel">
                <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($tab['products'] as $product)
                        <li><x-storefront.product-card :product="$product" /></li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </x-tabs>
</section>