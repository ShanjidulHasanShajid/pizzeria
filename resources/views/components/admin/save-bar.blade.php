@props(['cancel' => null, 'label' => 'Save'])
<div class="sticky bottom-0 -mx-4 mt-8 flex items-center justify-end gap-3 border-t border-line bg-white/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    @if ($cancel)
        <a href="{{ $cancel }}" class="text-sm font-medium text-muted hover:text-ink">Cancel</a>
    @endif
    <x-button type="submit">{{ $label }}</x-button>
</div>