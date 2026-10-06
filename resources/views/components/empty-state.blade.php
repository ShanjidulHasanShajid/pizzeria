@props(['title', 'message' => null, 'icon' => 'photo'])
<div {{ $attributes->class('rounded-card border border-dashed border-stone-300 bg-white px-6 py-12 text-center') }}>
    <x-icon :name="$icon" class="mx-auto h-10 w-10 text-stone-400" />
    <h2 class="mt-3 text-base font-semibold text-ink">{{ $title }}</h2>
    @if ($message)
        <p class="mx-auto mt-1 max-w-md text-sm text-muted">{{ $message }}</p>
    @endif
    @isset($action)
        <div class="mt-5">{{ $action }}</div>
    @endisset
</div>