@props(['slides'])
<section class="relative overflow-hidden bg-brand-900" aria-roledescription="carousel" aria-label="Featured offers"
    x-data="{
        current: 0,
        total: {{ count($slides) }},
        timer: null,
        next() { this.current = (this.current + 1) % this.total },
        prev() { this.current = (this.current - 1 + this.total) % this.total },
        start() {
            this.stop()
            if (! window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                this.timer = setInterval(() => this.next(), 6000)
            }
        },
        stop() { clearInterval(this.timer) },
    }"
    x-init="start()" x-on:mouseenter="stop()" x-on:mouseleave="start()" x-on:focusin="stop()">

    @foreach ($slides as $i => $slide)
        <div x-show="current === {{ $i }}" @if ($i > 0) x-cloak @endif x-transition.opacity.duration.500ms
            role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ count($slides) }}" class="relative">
            <img src="{{ $slide['image'] }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-30">
            <div class="page-container relative py-16 md:py-28">
                <div class="max-w-xl text-white">
                    <p class="text-sm font-semibold uppercase tracking-widest text-cheese">{{ $slide['eyebrow'] }}</p>
                    <h2 class="mt-2 font-display text-4xl font-bold md:text-5xl">{{ $slide['title'] }}</h2>
                    <p class="mt-4 text-lg text-white/90">{{ $slide['text'] }}</p>
                    <x-link-button :href="$slide['url']" size="lg" class="mt-6">{{ $slide['cta'] }}</x-link-button>
                </div>
            </div>
        </div>
    @endforeach

    <button type="button" x-on:click="prev()" aria-label="Previous slide"
        class="absolute left-2 top-1/2 hidden -translate-y-1/2 rounded-full bg-white/20 p-2 text-white hover:bg-white/40 md:block">
        <x-icon name="chevron-left" class="h-6 w-6" />
    </button>
    <button type="button" x-on:click="next()" aria-label="Next slide"
        class="absolute right-2 top-1/2 hidden -translate-y-1/2 rounded-full bg-white/20 p-2 text-white hover:bg-white/40 md:block">
        <x-icon name="chevron-right" class="h-6 w-6" />
    </button>

    <div class="absolute inset-x-0 bottom-4 flex justify-center gap-2">
        @foreach ($slides as $i => $slide)
            <button type="button" x-on:click="current = {{ $i }}" aria-label="Go to slide {{ $i + 1 }}"
                x-bind:class="current === {{ $i }} ? 'bg-white' : 'bg-white/40'" class="h-2.5 w-2.5 rounded-full"></button>
        @endforeach
    </div>
</section>