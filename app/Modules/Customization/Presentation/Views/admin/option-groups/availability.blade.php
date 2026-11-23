{{-- $groups: list<AdminOptionGroupDetail>, in sort order --}}
<x-layouts.admin title="Sold out today">
    <x-admin.page-header title="Sold out today" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Option groups', 'url' => route('admin.option-groups.index')], ['label' => 'Sold out today']]" />

    <p class="mb-4 max-w-2xl text-sm text-muted">One click marks an option sold out, or available again, on every product that offers it.</p>

    @if ($groups === [])
        <x-empty-state title="No option groups yet" message="Add an option group first." />
    @else
        <div class="max-w-3xl space-y-6" x-data="{ search: '' }">
            <div>
                <label for="option-search" class="mb-1 block text-sm font-medium text-ink">Find an option</label>
                <input id="option-search" type="search" class="field" placeholder="For example olives" x-model="search">
            </div>

            @foreach ($groups as $group)
                @if ($group->options !== [])
                    <section>
                        <h2 class="text-base font-semibold">{{ $group->name }}</h2>
                        <ul class="mt-2 divide-y divide-line rounded-card border border-line bg-white">
                            @foreach ($group->options as $option)
                                <li class="flex flex-wrap items-center gap-3 px-4 py-2" x-show="@js(mb_strtolower($option->name)).includes(search.trim().toLowerCase())">
                                    <span class="flex-1">{{ $option->name }}</span>
                                    @unless ($option->isAvailable)
                                        <x-badge variant="soldout">Sold out</x-badge>
                                    @endunless
                                    <form method="POST" action="{{ route('admin.option-groups.options.toggle', [$group->id, $option->id]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <x-button type="submit" size="sm" variant="outline" :aria-label="($option->isAvailable ? 'Mark sold out: ' : 'Make available: ').$option->name">{{ $option->isAvailable ? 'Sold out' : 'Available' }}</x-button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            @endforeach
        </div>
    @endif
</x-layouts.admin>
