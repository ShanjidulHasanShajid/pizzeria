@props(['padding' => true])
<div {{ $attributes->class(['rounded-card border border-line bg-white shadow-card', 'p-5' => $padding]) }}>{{ $slot }}</div>