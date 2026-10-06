@props(['name', 'label', 'value', 'checked' => false, 'id' => null])
@php
    $id ??= $name.'-'.$value;
    $isChecked = session()->hasOldInput() ? (string) old($name) === (string) $value : $checked;
@endphp
<label for="{{ $id }}" class="inline-flex items-center gap-2 text-sm text-ink">
    <input id="{{ $id }}" type="radio" name="{{ $name }}" value="{{ $value }}" @checked($isChecked)
        {{ $attributes->class('h-4 w-4 border-stone-400 text-brand-600 focus:ring-brand-600') }}>
    <span>{{ $label }}</span>
</label>