<div class="bg-ink text-sm text-white/90">
    <div class="page-container flex items-center justify-between gap-4 py-2">
        <p class="hidden sm:block">Delivering across Gulshan, Banani, Dhanmondi and more</p>
        <ul class="flex flex-1 items-center justify-end gap-4 sm:flex-none">
            <li class="hidden md:block"><a href="{{ route('orders.track') }}" class="hover:text-cheese">Track order</a></li>
            <li class="hidden md:block"><a href="{{ route('faq') }}" class="hover:text-cheese">Help</a></li>
            <li class="hidden md:block">
                @auth
                    @can('access-admin')
                        <a href="{{ route('admin.dashboard') }}" class="hover:text-cheese">Admin</a>
                        <span aria-hidden="true">/</span>
                    @endcan
                    <a href="{{ route('account.dashboard') }}" class="hover:text-cheese">My account</a>
                    <span aria-hidden="true">/</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="hover:text-cheese">Sign out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:text-cheese">Sign in</a>
                    <span aria-hidden="true">/</span>
                    <a href="{{ route('register') }}" class="hover:text-cheese">Register</a>
                @endauth
            </li>
            <li>
                <a href="tel:+8801700000000" class="inline-flex items-center gap-1.5 font-semibold text-cheese">
                    <x-icon name="phone" class="h-4 w-4" /> 01700-000000
                </a>
            </li>
        </ul>
    </div>
</div>