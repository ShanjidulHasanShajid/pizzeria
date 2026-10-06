@props(['name'])
@if ($errors->has($name))
    <p id="{{ $name }}-error" class="mt-1 text-sm font-medium text-red-700" role="alert">{{ $errors->first($name) }}</p>
@endif