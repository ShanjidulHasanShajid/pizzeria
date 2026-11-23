{{-- $settings: SiteSettings. $locations: list<LocationView>. --}}
@props(['settings', 'locations', 'logoUrl' => null])
@use('App\Modules\Content\Presentation\Support\ShopFormat')
@php
    $social = array_filter(['Facebook' => $settings->facebookUrl, 'Instagram' => $settings->instagramUrl, 'YouTube' => $settings->youtubeUrl]);
@endphp
<footer class="mt-12 bg-ink text-white/80">
    <div class="page-container grid gap-10 py-12 md:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <x-storefront.logo :site-name="$settings->siteName" :logo-url="$logoUrl" name-class="text-2xl text-white" />
            @if ($settings->aboutText !== '')
                <p class="mt-4 max-w-sm text-sm">{{ $settings->aboutText }}</p>
            @endif

            @if ($settings->addressLine !== '' || $settings->phone !== '' || $settings->email !== '')
                <ul class="mt-4 space-y-1 text-sm">
                    @if ($settings->addressLine !== '')
                        <li>{{ $settings->addressLine }}</li>
                    @endif
                    @if ($settings->phone !== '')
                        <li><a href="{{ ShopFormat::phoneHref($settings->phone) }}" class="hover:text-cheese">{{ $settings->phone }}</a></li>
                    @endif
                    @if ($settings->email !== '')
                        <li><a href="mailto:{{ $settings->email }}" class="hover:text-cheese">{{ $settings->email }}</a></li>
                    @endif
                </ul>
            @endif

            @if ($locations !== [])
                <h2 class="mt-6 text-sm font-semibold uppercase tracking-wider text-white">Our branches</h2>
                <ul class="mt-2 space-y-4 text-sm">
                    @foreach ($locations as $location)
                        <li>
                            <p class="font-semibold text-white">{{ $location->name }}</p>
                            <p>{{ $location->address }}{{ $location->area === '' ? '' : ', '.$location->area }}</p>
                            <p><a href="{{ ShopFormat::phoneHref($location->phone) }}" class="hover:text-cheese">{{ $location->phone }}</a></p>
                            <dl class="mt-1 space-y-0.5">
                                @foreach (ShopFormat::openingHours($location->hours) as $line)
                                    <div class="flex gap-2"><dt class="font-medium text-white">{{ $line['days'] }}:</dt><dd>{{ $line['hours'] }}</dd></div>
                                @endforeach
                            </dl>
                        </li>
                    @endforeach
                </ul>
            @endif
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
            @if ($social !== [])
                <h2 class="mt-6 text-sm font-semibold uppercase tracking-wider text-white">Follow us</h2>
                <ul class="mt-3 flex flex-wrap gap-4 text-sm">
                    @foreach ($social as $label => $url)
                        <li><a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="hover:text-cheese">{{ $label }}</a></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
    <div class="border-t border-white/10">
        <p class="page-container py-4 text-xs text-white/60">&copy; {{ now()->year }} {{ $settings->siteName }}. All rights reserved.</p>
    </div>
</footer>
