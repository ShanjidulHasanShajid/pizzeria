@props(['name', 'label' => null, 'help' => null, 'id' => null, 'value' => null, 'required' => false, 'rows' => 4])
@php
    $id ??= $name;
    $hasError = $errors->has($name);
    $describedBy = trim(($help ? $id.'-help ' : '').($hasError ? $name.'-error' : ''));
@endphp
<div>
    @if ($label)
        <x-form.label :for="$id" :required="$required">{{ $label }}</x-form.label>
    @endif
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}"
        @required($required)
        @if ($hasError) aria-invalid="true" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class(['field', 'field-invalid' => $hasError]) }}>{{ old($name, $value) }}</textarea>
    @if ($help)
        <x-form.help id="{{ $id }}-help">{{ $help }}</x-form.help>
    @endif
    <x-form.error :name="$name" />
</div>