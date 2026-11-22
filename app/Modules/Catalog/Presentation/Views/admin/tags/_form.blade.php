{{--
    Included by create and edit.
    $tag: TagRow when editing, null when creating. $types and $icons: value => label.
--}}
<x-admin.form-section title="Tag" description="Shown as a small badge on product cards and product pages.">
    <x-form.input name="name" label="Name" :value="$tag?->name" required maxlength="50" />
    <x-form.input name="slug" label="Slug" :value="$tag?->slug" maxlength="120"
        :help="$tag ? 'Leave empty to keep the current slug.' : 'Leave empty and it is made from the name.'" />
    <x-form.select name="type" label="Type" :options="$types" :value="$tag?->type->value" placeholder="Choose a type" required />
    <x-form.select name="icon" label="Icon" :options="$icons" :value="$tag?->icon" placeholder="No icon" />
    @if ($tag)
        <div class="sm:col-span-2">
            <x-form.toggle name="is_active" label="Active" :checked="$tag->isActive" />
        </div>
    @endif
</x-admin.form-section>

<x-admin.form-section title="Icons" description="What each icon looks like.">
    <ul class="grid gap-3 sm:col-span-2 sm:grid-cols-2">
        @foreach ($icons as $iconName => $iconLabel)
            <li class="flex items-center gap-2 text-sm">
                <x-icon :name="$iconName" class="h-5 w-5 text-muted" />
                {{ $iconLabel }}
            </li>
        @endforeach
    </ul>
</x-admin.form-section>
