@props(['name', 'label', 'value' => '1', 'checked' => false, 'help' => null, 'id' => null])
@php
    $id ??= $name;
    // After a failed validation, trust what the user had ticked; otherwise use the default.
    $isChecked = session()->hasOldInput() ? (string) old($name) === (string) $value : $checked;
@endphp
<div>
    <label for="{{ $id }}" class="inline-flex items-center gap-2 text-sm text-ink">
        <input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="{{ $value }}" @checked($isChecked)
            {{ $attributes->class('h-4 w-4 rounded border-stone-400 text-brand-600 focus:ring-brand-600') }}>
        <span>{{ $label }}</span>
    </label>
    @if ($help)
        <x-form.help>{{ $help }}</x-form.help>
    @endif
    <x-form.error :name="$name" />
</div>