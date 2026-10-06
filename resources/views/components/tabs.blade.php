@props(['tabs', 'default' => null])
@php
    $default ??= array_key_first($tabs);
@endphp
<div x-data="{ tab: @js($default) }">
    <div role="tablist" class="flex gap-1 overflow-x-auto border-b border-line">
        @foreach ($tabs as $key => $label)
            <button type="button" role="tab"
                x-on:click="tab = @js($key)"
                x-bind:aria-selected="(tab === @js($key)).toString()"
                x-bind:class="tab === @js($key) ? 'border-brand-600 text-brand-700' : 'border-transparent text-muted hover:text-ink'"
                class="whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors">{{ $label }}</button>
        @endforeach
    </div>
    <div class="pt-5">{{ $slot }}</div>
</div>