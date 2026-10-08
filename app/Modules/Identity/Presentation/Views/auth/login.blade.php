<x-layouts.storefront title="Sign in">
    <x-auth.card title="Sign in" subtitle="Welcome back. Sign in to see your orders and check out faster.">
        <form method="POST" action="{{ route('login') }}" class="space-y-4" novalidate>
            @csrf
            <x-form.input name="email" type="email" label="Email" required autocomplete="username" autofocus />
            <x-form.input name="password" type="password" label="Password" required autocomplete="current-password" />

            <div class="flex items-center justify-between gap-4">
                <x-form.checkbox name="remember" label="Remember me" />
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-700 hover:underline">Forgot your password?</a>
            </div>

            <x-button type="submit" class="w-full">Sign in</x-button>
        </form>

        <p class="mt-6 text-center text-sm text-muted">
            New here?
            <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:underline">Create an account</a>
        </p>
    </x-auth.card>
</x-layouts.storefront>
