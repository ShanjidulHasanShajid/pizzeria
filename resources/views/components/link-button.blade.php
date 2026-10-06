@props(['href', 'variant' => 'primary', 'size' => 'md'])
<a href="{{ $href }}" {{ $attributes->class(['btn', "btn-{$variant}", "btn-{$size}"]) }}>{{ $slot }}</a>