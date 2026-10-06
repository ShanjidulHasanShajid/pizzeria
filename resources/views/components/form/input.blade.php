@props(['name', 'label' => null, 'help' => null, 'type' => 'text', 'id' => null, 'value' => null, 'required' => false])
@php
    $id ??= $name;
    $hasError = $errors->has($name);
    $describedBy = trim(($help ? $id.'-help ' : '').($hasError ? $name.'-error' : ''));
@endphp
<div>
    @if ($label)
        <x-form.label :for="$id" :required="$required">{{ $label }}</x-form.label>
    @endif
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}"
        @required($required)
        @if ($hasError) aria-invalid="true" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class(['field', 'field-invalid' => $hasError]) }}>
    @if ($help)
        <x-form.help id="{{ $id }}-help">{{ $help }}</x-form.help>
    @endif
    <x-form.error :name="$name" />
</div>