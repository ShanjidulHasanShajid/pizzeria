{{-- The filter form of every listing page. Works without JavaScript. --}}
@use('App\Modules\Catalog\Domain\Enums\ProductSort')
<form method="GET" action="{{ $pageUrl }}" class="mt-6 rounded-card border border-line bg-white p-4" aria-label="Filter the menu" x-data>
    @if ($filter->search !== '')
        <input type="hidden" name="q" value="{{ $filter->search }}">
    @endif
    <div class="flex flex-wrap items-end gap-4">
        <div class="min-w-48">
            <label for="filter-sort" class="mb-1 block text-sm font-medium text-ink">Sort by</label>
            <select id="filter-sort" name="sort" class="field" x-on:change="$el.form.requestSubmit()">
                @foreach (ProductSort::cases() as $sort)
                    <option value="{{ $sort->value }}" @selected($filter->sort === $sort)>{{ $sort->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-32">
            <label for="filter-min" class="mb-1 block text-sm font-medium text-ink">From ৳</label>
            <input id="filter-min" name="min" type="number" min="0" max="999999" inputmode="numeric" class="field" value="{{ $filter->minPrice === null ? '' : intdiv($filter->minPrice->poisha(), 100) }}">
        </div>
        <div class="w-32">
            <label for="filter-max" class="mb-1 block text-sm font-medium text-ink">To ৳</label>
            <input id="filter-max" name="max" type="number" min="0" max="999999" inputmode="numeric" class="field" value="{{ $filter->maxPrice === null ? '' : intdiv($filter->maxPrice->poisha(), 100) }}">
        </div>
        <label class="inline-flex min-h-11 items-center gap-2 text-sm text-ink">
            <input type="checkbox" name="available" value="1" @checked($filter->onlyAvailable) class="h-4 w-4 rounded border-stone-400 text-brand-600 focus:ring-brand-600">
            Available now
        </label>
        <div class="flex items-center gap-3">
            <x-button type="submit">Apply</x-button>
            @if ($filter->isActive())
                <a href="{{ $pageUrl }}" class="text-sm font-medium text-muted hover:text-ink">Clear</a>
            @endif
        </div>
    </div>

    @if ($filterTags !== [])
        <fieldset class="mt-4">
            <legend class="sr-only">Tags</legend>
            <div class="flex flex-wrap gap-x-5 gap-y-2">
                @foreach ($filterTags as $option)
                    @continue($option->slug === $filter->pageTag)
                    <label class="inline-flex items-center gap-2 text-sm text-ink">
                        <input type="checkbox" name="tags[]" value="{{ $option->slug }}" @checked(in_array($option->slug, $filter->tagSlugs, true)) class="h-4 w-4 rounded border-stone-400 text-brand-600 focus:ring-brand-600">
                        {{ $option->name }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endif
</form>
