@props(['type' => 'info', 'dismissible' => false])
@php
    $styles = [
        'success' => 'border-accent-300 bg-accent-50 text-accent-900',
        'error' => 'border-red-300 bg-red-50 text-red-900',
        'warning' => 'border-amber-300 bg-amber-50 text-amber-900',
        'info' => 'border-sky-300 bg-sky-50 text-sky-900',
    ];
    $icons = [
        'success' => 'check-circle',
        'error' => 'exclamation-triangle',
        'warning' => 'exclamation-triangle',
        'info' => 'information-circle',
    ];
@endphp
<div x-data="{ show: true }" x-show="show" role="{{ $type === 'error' ? 'alert' : 'status' }}"
    {{ $attributes->class(['flex items-start gap-3 rounded-lg border p-4 text-sm', $styles[$type] ?? $styles['info']]) }}>
    <x-icon :name="$icons[$type] ?? 'information-circle'" class="mt-0.5 h-5 w-5 shrink-0" />
    <div class="flex-1">{{ $slot }}</div>
    @if ($dismissible)
        <button type="button" x-on:click="show = false" aria-label="Dismiss" class="shrink-0 opacity-70 hover:opacity-100">
            <x-icon name="x-mark" class="h-4 w-4" />
        </button>
    @endif
</div>