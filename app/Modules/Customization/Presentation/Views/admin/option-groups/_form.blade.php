{{-- Included by create and edit. $group is an AdminOptionGroupDetail when editing, null when creating. --}}
<x-admin.form-section title="Option group" description="The name is for you, the title is what customers read.">
    <x-form.input name="name" label="Name" :value="$group?->name" required maxlength="100" help="For example Pizza crusts." />
    <x-form.input name="display_title" label="Title for customers" :value="$group?->displayTitle" required maxlength="100" help="For example Choose your crust." />
    <div class="sm:col-span-2">
        <x-form.textarea name="description" label="Description" :value="$group?->description" rows="2" maxlength="500" help="Optional. Shown under the title." />
    </div>
    @if ($group)
        <div class="sm:col-span-2">
            <x-form.toggle name="is_active" label="Active" :checked="$group->isActive" />
        </div>
    @endif
</x-admin.form-section>
