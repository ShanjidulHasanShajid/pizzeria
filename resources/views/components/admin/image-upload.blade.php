@props(['name', 'label', 'current' => null, 'help' => null])
<div x-data="{
        preview: @js($current),
        removed: false,
        choose(event) {
            const file = event.target.files[0]
            if (file) { this.preview = URL.createObjectURL(file); this.removed = false }
        },
        remove() { this.preview = null; this.removed = true; this.$refs.file.value = '' },
    }">
    <x-form.label :for="$name">{{ $label }}</x-form.label>
    <div class="flex items-center gap-4">
        <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-line bg-cream">
            <img x-show="preview" x-bind:src="preview" alt="Preview of the selected image" class="h-full w-full object-cover">
            <x-icon x-show="! preview" name="photo" class="h-8 w-8 text-stone-400" />
        </div>
        <div class="space-y-2">
            <input id="{{ $name }}" x-ref="file" type="file" name="{{ $name }}" accept="image/jpeg,image/png,image/webp" x-on:change="choose($event)"
                class="block w-full text-sm file:mr-3 file:rounded-btn file:border-0 file:bg-brand-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-700">
            <button type="button" x-show="preview" x-on:click="remove()" class="text-sm font-medium text-red-700 hover:underline">Remove image</button>
            <input type="hidden" name="remove_{{ $name }}" x-bind:value="removed ? 1 : 0">
        </div>
    </div>
    @if ($help)
        <x-form.help>{{ $help }}</x-form.help>
    @endif
    <x-form.error :name="$name" />
</div>