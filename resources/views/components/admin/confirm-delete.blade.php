@props(['name', 'action' => '#', 'title' => 'Move to trash?', 'message' => 'It will move to the Trash. You can restore it later.'])
<x-modal :name="$name" :title="$title">
    <p class="text-sm text-muted">{{ $message }}</p>
    <form method="POST" action="{{ $action }}" class="mt-6 flex justify-end gap-3">
        @csrf
        @method('DELETE')
        <x-button variant="outline" x-on:click="$dispatch('close-modal')">Cancel</x-button>
        <x-button type="submit" variant="danger">Move to trash</x-button>
    </form>
</x-modal>