{{--
    The view of the ProductOptions Livewire component. A Livewire view needs ONE root element.
    $attached: list<AttachedGroupRow> in display order. $choices: group id => name, not attached yet.
--}}
<div class="space-y-6">
    @if ($status !== '')
        <p class="text-sm text-accent-700" role="status">{{ $status }}</p>
    @endif
    @error('ids')
        <x-alert type="error">{{ $message }}</x-alert>
    @enderror

    {{-- The list is always there (also when empty), so admin.js can make it draggable on page load. --}}
    <ul data-sortable aria-label="Attached option groups" class="space-y-3" x-data x-on:sorted="$wire.reorder($event.detail.ids)">
        @foreach ($attached as $group)
            @php($row = "rows.{$group->attachmentId}")
            @php($id = "attached-{$group->attachmentId}")
            <li data-id="{{ $group->attachmentId }}" wire:key="{{ $id }}" class="rounded-card border border-line bg-white p-3">
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" data-handle aria-label="Drag {{ $group->groupName }} to reorder" class="cursor-grab text-muted hover:text-ink">
                        <x-icon name="bars-3" class="h-5 w-5" />
                    </button>
                    <a href="{{ route('admin.option-groups.edit', $group->optionGroupId) }}" class="font-medium underline">{{ $group->groupName }}</a>
                    <span class="text-sm text-muted">{{ $group->optionCount }} {{ Str::plural('option', $group->optionCount) }}</span>
                    @unless ($group->isGroupActive)
                        <x-badge variant="soldout">Inactive</x-badge>
                    @endunless
                </div>

                <div class="mt-3 flex flex-wrap items-end gap-3">
                    <label class="inline-flex items-center gap-2 pb-2 text-sm">
                        <input type="checkbox" class="h-4 w-4 rounded border-stone-400 text-brand-600" wire:model="{{ $row }}.isRequired"> Required
                    </label>
                    @foreach (['minimum' => 'Minimum', 'maximum' => 'Maximum', 'freeCount' => 'Free picks'] as $field => $label)
                        <div>
                            <label class="mb-1 block text-xs font-medium text-muted" for="{{ $id }}-{{ $field }}">{{ $label }}</label>
                            <input id="{{ $id }}-{{ $field }}" type="number" min="0" max="30" class="field w-24" wire:model="{{ $row }}.{{ $field }}">
                        </div>
                    @endforeach
                    <x-button size="sm" wire:click="saveRules({{ $group->attachmentId }})">Save rules</x-button>
                    <x-button size="sm" variant="danger" wire:click="detach({{ $group->attachmentId }})"
                        wire:confirm="Detach this group from the product? The group itself stays.">Detach</x-button>
                </div>

                @foreach (['minimum', 'maximum', 'freeCount', 'rules'] as $field)
                    <x-form.error :name="$row.'.'.$field" />
                @endforeach
            </li>
        @endforeach
    </ul>

    @if ($attached === [])
        <p class="text-sm text-muted">No option groups yet. Customers order this product as it is.</p>
    @endif

    {{-- Not a form element: this component stands on the product page next to the product form. --}}
    <div class="rounded-card border border-dashed border-stone-300 bg-white p-4">
        <h3 class="text-sm font-semibold">Attach an option group</h3>
        @if ($choices === [])
            <p class="mt-2 text-sm text-muted">Every option group is attached already.</p>
        @else
            <div class="mt-3 flex flex-wrap items-end gap-3">
                <div class="w-56">
                    <label class="mb-1 block text-xs font-medium text-muted" for="new-attached-group">Group</label>
                    <select id="new-attached-group" class="field" wire:model="new.optionGroupId">
                        <option value="">Choose a group</option>
                        @foreach ($choices as $groupId => $name)
                            <option value="{{ $groupId }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="inline-flex items-center gap-2 pb-2 text-sm">
                    <input type="checkbox" class="h-4 w-4 rounded border-stone-400 text-brand-600" wire:model="new.isRequired"> Required
                </label>
                @foreach (['minimum' => 'Minimum', 'maximum' => 'Maximum', 'freeCount' => 'Free picks'] as $field => $label)
                    <div>
                        <label class="mb-1 block text-xs font-medium text-muted" for="new-attached-{{ $field }}">{{ $label }}</label>
                        <input id="new-attached-{{ $field }}" type="number" min="0" max="30" class="field w-24" wire:model="new.{{ $field }}">
                    </div>
                @endforeach
                <x-button size="sm" wire:click="attach" wire:loading.attr="disabled" wire:target="attach">Attach</x-button>
            </div>
            @foreach (['optionGroupId', 'minimum', 'maximum', 'freeCount', 'rules'] as $field)
                <x-form.error :name="'new.'.$field" />
            @endforeach
        @endif
    </div>
</div>
