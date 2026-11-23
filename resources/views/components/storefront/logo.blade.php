{{-- The uploaded logo, or the built-in mark with the shop name. --}}
@props(['siteName', 'logoUrl' => null, 'nameClass' => ''])
<a href="{{ route('home') }}" {{ $attributes->class('flex shrink-0 items-center gap-2') }}>
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-9 w-auto max-w-40 object-contain md:h-10">
    @else
        <img src="{{ asset('images/logo-mark.svg') }}" alt="" class="h-9 w-9 md:h-10 md:w-10">
        <span class="font-display font-bold {{ $nameClass }}">{{ $siteName }}</span>
    @endif
</a>
