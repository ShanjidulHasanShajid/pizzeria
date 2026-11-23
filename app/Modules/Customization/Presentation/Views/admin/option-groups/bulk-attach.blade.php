{{-- $group: AdminOptionGroupDetail. $categories: category id => name. --}}
<x-layouts.admin :title="'Attach '.$group->name">
    <x-admin.page-header :title="'Attach '.$group->name.' to a category'" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Option groups', 'url' => route('admin.option-groups.index')], ['label' => $group->name, 'url' => route('admin.option-groups.edit', $group->id)], ['label' => 'Attach']]" />

    <form method="POST" action="{{ route('admin.option-groups.bulk-attach.store', $group->id) }}" novalidate>
        @csrf
        @error('option_group_id')
            <x-alert type="error" class="mb-4">{{ $message }}</x-alert>
        @enderror

        <x-admin.form-section title="Products" description="Every product of the category and its sub-categories gets the group. Products that have it already keep their own rules. Combos are left out.">
            <div class="sm:col-span-2">
                <x-form.select name="category_id" label="Category" :options="$categories" placeholder="Choose a category" required />
            </div>
        </x-admin.form-section>

        <x-admin.form-section title="Rules" description="How many options a customer may choose. You can change the rules of single products later.">
            <div class="sm:col-span-2">
                <x-form.toggle name="is_required" label="Required: the customer must choose" />
            </div>
            <x-form.input name="minimum" type="number" label="Minimum" value="0" min="0" max="30" required help="A required group needs at least 1." />
            <x-form.input name="maximum" type="number" label="Maximum" value="1" min="1" max="30" required />
            <x-form.input name="free_count" type="number" label="Free picks" value="0" min="0" max="30" required help="That many of the cheapest paid options cost nothing." />
            <div class="sm:col-span-2">
                <x-form.error name="rules" />
            </div>
        </x-admin.form-section>

        <x-admin.save-bar :cancel="route('admin.option-groups.edit', $group->id)" label="Attach to the category" />
    </form>
</x-layouts.admin>
