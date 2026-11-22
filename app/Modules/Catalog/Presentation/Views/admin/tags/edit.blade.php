<x-layouts.admin :title="'Edit '.$tag->name">
    <x-admin.page-header :title="'Edit '.$tag->name" :breadcrumbs="[['label' => 'Admin', 'url' => route('admin.dashboard')], ['label' => 'Tags', 'url' => route('admin.tags.index')], ['label' => 'Edit']]" />

    <form method="POST" action="{{ route('admin.tags.update', $tag->id) }}" novalidate>
        @csrf
        @method('PUT')
        @include('catalog::admin.tags._form', ['tag' => $tag])
        <x-admin.save-bar :cancel="route('admin.tags.index')" label="Save changes" />
    </form>
</x-layouts.admin>
