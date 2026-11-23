{{-- $category: CategoryPage. The rest: see _listing. --}}
@php
    $crumbs = [['label' => 'Home', 'url' => route('home')], ['label' => 'Menu', 'url' => route('menu.index')]];

    if ($category->parentSlug !== null) {
        $crumbs[] = ['label' => $category->parentName, 'url' => route('categories.show', $category->parentSlug)];
    }

    $crumbs[] = ['label' => $category->name];
@endphp
<x-layouts.storefront :title="$category->name" :description="$category->description ?: $category->name.' from our menu.'">
    <div class="page-container py-8">
        <x-breadcrumbs :items="$crumbs" />
        <h1 class="mt-4 font-display text-3xl font-bold md:text-4xl">{{ $category->name }}</h1>
        @if ($category->description)
            <p class="mt-2 max-w-2xl text-muted">{{ $category->description }}</p>
        @endif

        @if ($category->children !== [])
            <nav class="mt-5 flex gap-2 overflow-x-auto pb-2" aria-label="Sub-categories">
                @foreach ($category->children as $child)
                    <a href="{{ route('categories.sub', [$category->slug, $child->slug]) }}" class="whitespace-nowrap rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold hover:border-brand-600 hover:text-brand-700">{{ $child->name }}</a>
                @endforeach
            </nav>
        @endif

        @include('catalog::storefront._listing')
    </div>
</x-layouts.storefront>
