@props(['name', 'title'])
<div x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail === @js($name)) open = true"
    x-on:close-modal.window="open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    role="dialog" aria-modal="true" aria-labelledby="modal-{{ $name }}-title">
    <div class="absolute inset-0 bg-ink/60" x-on:click="open = false" aria-hidden="true"></div>
    <div x-show="open" x-transition x-trap.noscroll="open" {{ $attributes->class('relative w-full max-w-lg rounded-card bg-white p-6 shadow-pop') }}>
        <div class="flex items-start justify-between gap-4">
            <h2 id="modal-{{ $name }}-title" class="font-display text-xl font-bold">{{ $title }}</h2>
            <button type="button" x-on:click="open = false" aria-label="Close" class="text-muted hover:text-ink">
                <x-icon name="x-mark" class="h-6 w-6" />
            </button>
        </div>
        <div class="mt-4">{{ $slot }}</div>
    </div>
</div>