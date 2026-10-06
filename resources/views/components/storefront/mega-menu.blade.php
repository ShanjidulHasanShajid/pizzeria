@props(['groups'])
<div class="rounded-b-card border border-t-0 border-line bg-white p-6 shadow-pop">
    <div class="grid grid-cols-2 gap-x-8 gap-y-6 lg:grid-cols-3 xl:grid-cols-4">
        @foreach ($groups as $group)
            <div>
                <a href="{{ route('categories.show', $group['slug']) }}" class="font-display text-lg font-bold text-brand-700 hover:underline">{{ $group['name'] }}</a>
                @if ($group['children'] !== [])
                    <ul class="mt-2 space-y-1">
                        @foreach ($group['children'] as $child)
                            <li><a href="{{ route('categories.show', $child['slug']) }}" class="text-sm text-muted hover:text-brand-700 hover:underline">{{ $child['name'] }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
</div>