@props(['columns', 'selectable' => false, 'sort' => null, 'direction' => 'asc'])
<div class="space-y-3"
    x-data="{
        count: 0,
        update() { this.count = this.$root.querySelectorAll('[data-row-checkbox]:checked').length },
        toggleAll(on) { this.$root.querySelectorAll('[data-row-checkbox]').forEach((box) => { box.checked = on }); this.update() },
    }">
    @if ($selectable)
        <div x-show="count > 0" x-cloak role="status" class="flex flex-wrap items-center gap-3 rounded-lg bg-ink px-4 py-2 text-sm text-white">
            <span x-text="count + ' selected'"></span>
            @isset($bulk)
                {{ $bulk }}
            @endisset
        </div>
    @endif

    <div class="overflow-x-auto rounded-card border border-line bg-white shadow-card">
        <table class="min-w-full divide-y divide-line text-sm">
            <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-muted">
                <tr>
                    @if ($selectable)
                        <th scope="col" class="w-10 px-4 py-3">
                            <input type="checkbox" aria-label="Select all rows" class="h-4 w-4 rounded border-stone-400 text-brand-600"
                                x-on:change="toggleAll($event.target.checked)">
                        </th>
                    @endif
                    @foreach ($columns as $column)
                        @php
                            $key = $column['sort'] ?? null;
                            $isSorted = $key !== null && $key === $sort;
                            $nextDirection = $isSorted && $direction === 'asc' ? 'desc' : 'asc';
                        @endphp
                        <th scope="col" class="px-4 py-3" @if ($isSorted) aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}" @endif>
                            @if ($key !== null)
                                <a href="{{ request()->fullUrlWithQuery(['sort' => $key, 'direction' => $nextDirection]) }}" class="inline-flex items-center gap-1 hover:text-ink">
                                    {{ $column['label'] }}
                                    @if ($isSorted)
                                        <span aria-hidden="true">{{ $direction === 'asc' ? '▲' : '▼' }}</span>
                                    @endif
                                </a>
                            @else
                                {{ $column['label'] }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>