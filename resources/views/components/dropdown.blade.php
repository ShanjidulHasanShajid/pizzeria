@props(['align' => 'right', 'width' => 'w-48'])
<div class="relative" x-data="{ open: false }" x-on:keydown.escape="open = false" x-on:click.outside="open = false">
    <div x-on:click="open = ! open">{{ $trigger }}</div>
    <div x-show="open" x-cloak x-transition
        class="absolute z-40 mt-2 {{ $width }} {{ $align === 'left' ? 'left-0' : 'right-0' }} rounded-card border border-line bg-white py-1 shadow-pop"
        role="menu">
        {{ $slot }}
    </div>
</div>