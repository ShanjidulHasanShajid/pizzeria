{{--
    Included by create and edit. $category: AdminCategoryDetail when editing, null when creating.
    $parents and $stations: id => name. $imageUrl: path => URL.
--}}
<x-admin.form-section title="Basics" description="The name customers see in the menu, and where the kitchen prepares it.">
    <x-form.input name="name" label="Name" :value="$category?->name" required maxlength="100" />
    <x-form.input name="slug" label="Slug" :value="$category?->slug" maxlength="120"
        :help="$category ? 'Part of the web address. Leave empty to keep it.' : 'Part of the web address. Leave empty and it is made from the name.'" />
    @if ($category && $category->childCount > 0)
        <div>
            <p class="mb-1 block text-sm font-medium text-ink">Parent</p>
            <p class="text-sm text-muted">Top level. It has {{ $category->childCount }} {{ Str::plural('sub-category', $category->childCount) }}, so it cannot become a sub-category itself.</p>
            <input type="hidden" name="parent_id" value="">
        </div>
    @else
        <x-form.select name="parent_id" label="Parent" :options="$parents" :value="$category?->parentId" placeholder="None (top level)"
            help="Only one level of sub-categories, for example Drinks > Milkshakes." />
    @endif
    <x-form.select name="kitchen_station_id" label="Kitchen station" :options="$stations" :value="$category?->kitchenStationId" placeholder="Choose a station" required />
    <div class="sm:col-span-2">
        <x-form.textarea name="description" label="Description" :value="$category?->description" rows="3" maxlength="1000"
            help="Optional. Shown at the top of the category page." />
    </div>
</x-admin.form-section>

<x-admin.form-section title="Image" description="Square works best. JPG, PNG or WebP, at least 400 x 400 pixels, at most 2 MB. It is resized and converted to WebP.">
    <div class="sm:col-span-2">
        <x-admin.image-upload name="image" label="Category image" :current="$imageUrl($category?->imagePath)" />
    </div>
</x-admin.form-section>

<x-admin.form-section title="Visibility" description="An inactive category and its products are hidden from the shop.">
    <div class="space-y-4 sm:col-span-2">
        <x-form.toggle name="is_active" label="Active" :checked="$category?->isActive ?? true" />
        <x-form.toggle name="show_in_mega_menu" label="Show in the menu" :checked="$category?->showInMegaMenu ?? true" />
        <x-form.toggle name="is_featured" label="Featured on the home page" :checked="$category?->isFeatured ?? false" />
    </div>
</x-admin.form-section>
