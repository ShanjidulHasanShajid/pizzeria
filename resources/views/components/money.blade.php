@props(['amount', 'compareAt' => null, 'from' => false])
<span {{ $attributes->class('inline-flex items-baseline gap-2 tabular-nums') }}>
    @if ($from)
        <span class="text-xs font-normal text-muted">From</span>
    @endif
    <span class="font-semibold text-ink">@money($amount)</span>
    @if ($compareAt)
        <span class="sr-only">Was</span>
        <s class="text-sm text-muted">@money($compareAt)</s>
    @endif
</span>