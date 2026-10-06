@inject('placeholder', 'App\Modules\Shared\Presentation\Support\PlaceholderData')
<footer class="mt-12 bg-ink text-white/80">
    <div class="page-container grid gap-10 py-12 md:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <img src="{{ asset('images/logo-mark.svg') }}" alt="" class="h-10 w-10">
                <span class="font-display text-2xl font-bold text-white">{{ config('app.name') }}</span>
            </a>
            <p class="mt-4 max-w-sm text-sm">Hand-stretched pizza, fresh pasta and warm desserts, made to order in Dhaka and delivered hot to your door.</p>

            <h2 class="mt-6 text-sm font-semibold uppercase tracking-wider text-white">Our branches</h2>
            <ul class="mt-2 space-y-3 text-sm">
                @foreach ($placeholder->branches() as $branch)
                    <li>
                        <p class="font-semibold text-white">{{ $branch['name'] }}</p>
                        <p>{{ $branch['address'] }}</p>
                        <p><a href="tel:{{ preg_replace('/\D/', '', $branch['phone']) }}" class="hover:text-cheese">{{ $branch['phone'] }}</a></p>
                    </li>
                @endforeach
            </ul>

            <h2 class="mt-6 text-sm font-semibold uppercase tracking-wider text-white">Opening hours</h2>
            <dl class="mt-2 space-y-1 text-sm">
                @foreach ($placeholder->openingHours() as $days => $hours)
                    <div class="flex gap-2"><dt class="font-medium text-white">{{ $days }}:</dt><dd>{{ $hours }}</dd></div>
                @endforeach
            </dl>
        </div>

        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wider text-white">Customer service</h2>
            <ul class="mt-3 space-y-2 text-sm">
                <li><a href="{{ route('faq') }}" class="hover:text-cheese">FAQ</a></li>
                <li><a href="{{ route('orders.track') }}" class="hover:text-cheese">Track order</a></li>
                <li><a href="{{ route('contact') }}" class="hover:text-cheese">Contact us</a></li>
                <li><a href="{{ route('legal.refund') }}" class="hover:text-cheese">Refund policy</a></li>
            </ul>
        </div>

        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wider text-white">Quick links</h2>
            <ul class="mt-3 space-y-2 text-sm">
                <li><a href="{{ route('menu.index') }}" class="hover:text-cheese">Menu</a></li>
                <li><a href="{{ route('builder.show') }}" class="hover:text-cheese">Build your pizza</a></li>
                <li><a href="{{ route('meal-deals.index') }}" class="hover:text-cheese">Meal deals</a></li>
                <li><a href="{{ route('catering') }}" class="hover:text-cheese">Catering</a></li>
                <li><a href="{{ route('locations.index') }}" class="hover:text-cheese">Locations</a></li>
            </ul>
        </div>

        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wider text-white">About us</h2>
            <ul class="mt-3 space-y-2 text-sm">
                <li><a href="{{ route('about') }}" class="hover:text-cheese">Our story</a></li>
                <li><a href="{{ route('legal.terms') }}" class="hover:text-cheese">Terms</a></li>
                <li><a href="{{ route('legal.privacy') }}" class="hover:text-cheese">Privacy</a></li>
            </ul>
            <h2 class="mt-6 text-sm font-semibold uppercase tracking-wider text-white">Follow us</h2>
            <ul class="mt-3 flex gap-4 text-sm">
                @foreach ($placeholder->socialLinks() as $link)
                    <li><a href="{{ $link['url'] }}" class="hover:text-cheese">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10">
        <p class="page-container py-4 text-xs text-white/60">&copy; {{ now()->year }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</footer>