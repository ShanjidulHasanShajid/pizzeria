@props(['label' => 'Reorderable list'])
<ul data-sortable aria-label="{{ $label }}" {{ $attributes->class('divide-y divide-line rounded-card border border-line bg-white') }}>
    {{ $slot }}
</ul>