{{--
    $layout: HomeLayout. $categories: category id => name.
    $products: for each saved row, product id => name.
--}}
<x-layouts.admin title="Home page">
    <x-admin.page-header title="Home page" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Home page']]" />

    <nav class="mb-8 flex flex-wrap gap-3" aria-label="Home page content">
        <x-link-button :href="route('admin.hero-slides.index')" variant="outline">Hero slides</x-link-button>
        <x-link-button :href="route('admin.trust-badges.index')" variant="outline">Trust badges</x-link-button>
        <x-link-button :href="route('admin.banners.index')" variant="outline">Banners</x-link-button>
        <x-link-button :href="route('admin.gallery.index')" variant="outline">Gallery</x-link-button>
    </nav>

    <form method="POST" action="{{ route('admin.home.update') }}" x-data novalidate>
        @csrf
        @method('PUT')

        <x-admin.form-section title="Showcase tabs" description="Up to six categories as tabs, in this order.">
            @for ($i = 0; $i < $maxTabs; $i++)
                @php($chosen = old("tabs.{$i}", $layout->tabCategoryIds[$i] ?? ''))
                <div>
                    <label for="tab-{{ $i }}" class="mb-1 block text-sm font-medium text-ink">Tab {{ $i + 1 }}</label>
                    <select id="tab-{{ $i }}" name="tabs[{{ $i }}]" class="field">
                        <option value="">None</option>
                        @foreach ($categories as $id => $name)
                            <option value="{{ $id }}" @selected((string) $chosen === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endfor
            <div class="sm:col-span-2">
                <x-form.error name="tabs" />
            </div>
        </x-admin.form-section>

        @for ($i = 0; $i < $maxRows; $i++)
            @php($row = $layout->rows[$i] ?? null)
            <x-admin.form-section title="Category row {{ $i + 1 }}" description="Three products of one category. Save a new category first; then its products can be picked. Without a pick the first products are shown.">
                <div class="sm:col-span-2">
                    @php($chosen = old("rows.{$i}.category_id", $row?->categoryId ?? ''))
                    <label for="row-{{ $i }}" class="mb-1 block text-sm font-medium text-ink">Category</label>
                    {{-- Another category: its products are not the picked ones any more. --}}
                    <select id="row-{{ $i }}" name="rows[{{ $i }}][category_id]" class="field"
                        x-on:change="$root.querySelectorAll('.pick-{{ $i }}').forEach(select => select.value = '')">
                        <option value="">None</option>
                        @foreach ($categories as $id => $name)
                            <option value="{{ $id }}" @selected((string) $chosen === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($row)
                    @for ($p = 0; $p < $maxProducts; $p++)
                        @php($picked = old("rows.{$i}.product_ids.{$p}", $row->productIds[$p] ?? ''))
                        <div>
                            <label for="row-{{ $i }}-product-{{ $p }}" class="mb-1 block text-sm font-medium text-ink">Product {{ $p + 1 }}</label>
                            <select id="row-{{ $i }}-product-{{ $p }}" name="rows[{{ $i }}][product_ids][{{ $p }}]" class="field pick-{{ $i }}">
                                <option value="">Automatic</option>
                                @foreach ($products[$i] ?? [] as $id => $name)
                                    <option value="{{ $id }}" @selected((string) $picked === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endfor
                @endif
                <div class="sm:col-span-2">
                    <x-form.error :name="'rows.'.$i" />
                </div>
            </x-admin.form-section>
        @endfor

        <x-admin.save-bar label="Save layout" />
    </form>
</x-layouts.admin>
