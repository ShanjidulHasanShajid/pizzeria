@props(['variant' => 'primary', 'size' => 'md', 'type' => 'button', 'loading' => false])
<button type="{{ $type }}" @disabled($loading) @if ($loading) aria-busy="true" @endif {{ $attributes->class(['btn', "btn-{$variant}", "btn-{$size}"]) }}>
    @if ($loading)
        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/>
            <path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" class="opacity-75"/>
        </svg>
    @endif
    {{ $slot }}
</button>