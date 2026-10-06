@props(['slides'])
@php
    $count = count($slides);
    // Track layout: [clone of last] [real slides...] [clone of first]. The clones let the loop wrap without rewinding.
    $track = [['slide' => $slides[$count - 1], 'real' => null]];
    foreach ($slides as $i => $slide) {
        $track[] = ['slide' => $slide, 'real' => $i];
    }
    $track[] = ['slide' => $slides[0], 'real' => null];
@endphp
<section class="relative overflow-hidden bg-brand-900" aria-roledescription="carousel" aria-label="Featured offers"
    x-data="{
        total: {{ $count }},
        pos: 1,
        animate: true,
        locked: false,
        timer: null,
        touchX: 0,
        get current() { return (this.pos - 1 + this.total) % this.total },
        move(to) {
            if (this.total < 2 || this.locked) return
            this.locked = true
            this.pos = to
            setTimeout(() => this.settle(), 520)
        },
        next() { this.move(this.pos + 1) },
        prev() { this.move(this.pos - 1) },
        go(i) { if (i !== this.current) this.move(i + 1) },
        settle() {
            if (this.pos > this.total) this.jump(1)
            else if (this.pos < 1) this.jump(this.total)
            else this.locked = false
        },
        jump(to) {
            this.animate = false
            this.pos = to
            this.$nextTick(() => {
                void this.$refs.track.offsetWidth
                this.animate = true
                this.locked = false
            })
        },
        swipeEnd(e) {
            const dx = e.changedTouches[0].clientX - this.touchX
            if (Math.abs(dx) > 50) dx < 0 ? this.next() : this.prev()
        },
        start() {
            this.stop()
            if (this.total > 1 && ! window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                this.timer = setInterval(() => this.next(), 6000)
            }
        },
        stop() { clearInterval(this.timer) },
    }"
    x-init="start()" x-on:mouseenter="stop()" x-on:mouseleave="start()" x-on:focusin="stop()"
    x-on:touchstart.passive="touchX = $event.touches[0].clientX" x-on:touchend.passive="swipeEnd($event)">

    <div x-ref="track" class="flex" style="transform: translateX(-100%)"
        x-bind:class="animate ? 'transition-transform duration-500 ease-in-out motion-reduce:transition-none' : ''"
        x-bind:style="{ transform: 'translateX(-' + (pos * 100) + '%)' }">
        @foreach ($track as $item)
            @php($slide = $item['slide'])
            <div class="relative w-full shrink-0" role="group" aria-roledescription="slide"
                @if ($item['real'] === null)
                    aria-hidden="true" inert
                @else
                    aria-label="{{ $item['real'] + 1 }} of {{ $count }}"
                    x-bind:inert="current !== {{ $item['real'] }}" x-bind:aria-hidden="current !== {{ $item['real'] }}"
                @endif>
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
    </div>

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
            <button type="button" x-on:click="go({{ $i }})" aria-label="Go to slide {{ $i + 1 }}"
                x-bind:class="current === {{ $i }} ? 'bg-white' : 'bg-white/40'" class="h-2.5 w-2.5 rounded-full"></button>
        @endforeach
    </div>
</section>
