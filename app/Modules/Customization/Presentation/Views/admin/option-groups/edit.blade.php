{{-- $group: AdminOptionGroupDetail --}}
<x-layouts.admin :title="'Edit '.$group->name">
    <x-admin.page-header :title="'Edit '.$group->name" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Option groups', 'url' => route('admin.option-groups.index')], ['label' => 'Edit']]">
        <x-slot:actions>
            <x-link-button :href="route('admin.option-groups.prices.edit', $group->id)" variant="outline">Price matrix</x-link-button>
            <x-link-button :href="route('admin.option-groups.bulk-attach.create', $group->id)" variant="outline">Attach to a category</x-link-button>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Options first: each row is saved at once by the Livewire component, without the Save button below. --}}
    <section class="border-b border-line pb-8">
        <h2 class="text-base font-semibold">Options</h2>
        <p class="mt-1 text-sm text-muted">Each row has its own Save button. Drag to reorder. Prices per size are set in the price matrix.</p>
        <div class="mt-4">
            <livewire:customization.admin.option-manager :option-group-id="$group->id" />
        </div>
    </section>

    <form method="POST" action="{{ route('admin.option-groups.update', $group->id) }}" novalidate class="mt-8">
        @csrf
        @method('PUT')
        @include('customization::admin.option-groups._form', ['group' => $group])
        <x-admin.save-bar :cancel="route('admin.option-groups.index')" label="Save changes" />
    </form>
</x-layouts.admin>
