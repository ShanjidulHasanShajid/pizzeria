{{-- Included by create and edit. $zone is a DeliveryZoneRow when editing, null when creating. $branches: list<Branch> --}}
@php
    $branchOptions = [];

    foreach ($branches as $branch) {
        $branchOptions[$branch->id] = $branch->isActive ? $branch->name : "{$branch->name} (switched off)";
    }
@endphp
@if ($zone && $zone->branch === null)
    <x-alert variant="warning" class="mb-4">The branch of this zone was deleted, so customers cannot choose the zone. Choose another branch.</x-alert>
@endif

<x-admin.form-section title="Delivery zone" description="An area you deliver to. Customers choose it at checkout.">
    <x-form.input name="name" label="Name" :value="$zone?->name" required maxlength="100" help="For example Gulshan 1 and 2." />
    <x-form.select name="location_id" label="Served by" :options="$branchOptions" :value="$zone?->locationId" required placeholder="Choose a branch" />
    <x-form.input name="fee" label="Delivery fee (taka)" :value="$zone ? $zone->fee->toTakaString() : $startingFee" required inputmode="decimal" help="0 is free delivery. Orders already placed keep their fee." />
    <x-form.input name="min_order" label="Minimum order (taka)" :value="$zone?->minimumOrder->toTakaString()" inputmode="decimal" help="For the items, without the fee. Empty or 0 means no minimum." />
    <x-form.toggle name="is_active" label="Switched on" :checked="$zone?->isActive ?? true" />
</x-admin.form-section>
