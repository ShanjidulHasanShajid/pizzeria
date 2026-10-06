@props(['name', 'options', 'label' => null, 'help' => null, 'id' => null, 'value' => null, 'required' => false, 'placeholder' => null])
@php
    $id ??= $name;
    $hasError = $errors->has($name);
    $describedBy = trim(($help ? $id.'-help ' : '').($hasError ? $name.'-error' : ''));
    $current = (string) old($name, $value);
@endphp
<div>
    @if ($label)
        <x-form.label :for="$id" :required="$required">{{ $label }}</x-form.label>
    @endif
    <select id="{{ $id }}" name="{{ $name }}"
        @required($required)
        @if ($hasError) aria-invalid="true" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class(['field', 'field-invalid' => $hasError]) }}>
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($current === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @if ($help)
        <x-form.help id="{{ $id }}-help">{{ $help }}</x-form.help>
    @endif
    <x-form.error :name="$name" />
</div>