{{-- $tag: TagBadge. The rest: see _listing. --}}
<x-layouts.storefront :title="$tag->name.' dishes'.($pageNumber > 1 ? ', page '.$pageNumber : '')" :description="'Everything on our menu that is tagged '.$tag->name.'.'" :canonical="$canonical">
    <div class="page-container py-8">
        <x-breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Menu', 'url' => route('menu.index')], ['label' => $tag->name]]" />
        <h1 class="mt-4 flex items-center gap-3 font-display text-3xl font-bold md:text-4xl">
            @if ($tag->icon)
                <x-icon :name="$tag->icon" class="h-8 w-8 text-brand-700" />
            @endif
            {{ $tag->name }}
        </h1>

        @include('catalog::storefront._listing')
    </div>
</x-layouts.storefront>
