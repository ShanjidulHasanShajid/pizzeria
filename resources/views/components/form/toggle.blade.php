@props(['name', 'label', 'checked' => false, 'id' => null])
@php
    $id ??= $name;
    $isChecked = session()->hasOldInput() ? (bool) old($name) : $checked;
@endphp
<label for="{{ $id }}" class="inline-flex cursor-pointer items-center gap-3">
    <input type="hidden" name="{{ $name }}" value="0">
    <input id="{{ $id }}" type="checkbox" role="switch" name="{{ $name }}" value="1" @checked($isChecked) class="peer sr-only" {{ $attributes }}>
    <span class="relative h-6 w-11 rounded-full bg-stone-400 transition peer-checked:bg-accent-600 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-600 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition after:content-[''] peer-checked:after:translate-x-5" aria-hidden="true"></span>
    <span class="text-sm font-medium text-ink">{{ $label }}</span>
</label>