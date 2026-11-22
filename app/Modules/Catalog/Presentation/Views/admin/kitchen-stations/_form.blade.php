{{-- Included by create and edit. $station is a KitchenStationRow when editing, null when creating. --}}
<x-admin.form-section title="Station" description="The name the kitchen sees on its tickets.">
    <x-form.input name="name" label="Name" :value="$station?->name" required maxlength="100" />
    @if ($station)
        <div class="sm:col-span-2">
            <x-form.toggle name="is_active" label="Active" :checked="$station->isActive" />
        </div>
    @endif
</x-admin.form-section>
