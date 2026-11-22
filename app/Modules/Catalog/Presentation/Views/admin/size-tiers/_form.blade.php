{{-- Included by create and edit. $sizeTier is a SizeTierRow when editing, null when creating. --}}
<x-admin.form-section title="Size" description="For example Personal, Medium, Large.">
    <x-form.input name="name" label="Name" :value="$sizeTier?->name" required maxlength="50" />
    <x-form.input name="slug" label="Slug" :value="$sizeTier?->slug" maxlength="120"
        :help="$sizeTier ? 'Leave empty to keep the current slug.' : 'Leave empty and it is made from the name.'" />
    @if ($sizeTier)
        <div class="sm:col-span-2">
            <x-form.toggle name="is_active" label="Active" :checked="$sizeTier->isActive" />
        </div>
    @endif
</x-admin.form-section>
