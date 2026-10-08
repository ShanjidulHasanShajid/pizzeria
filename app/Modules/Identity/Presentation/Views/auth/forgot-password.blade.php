<x-layouts.storefront title="Forgot password">
    <x-auth.card title="Forgot your password?" subtitle="Enter your email and we will send you a link to choose a new one.">
        <form method="POST" action="{{ route('password.email') }}" class="space-y-4" novalidate>
            @csrf
            <x-form.input name="email" type="email" label="Email" required autocomplete="email" autofocus />
            <x-button type="submit" class="w-full">Email me a reset link</x-button>
        </form>

        <p class="mt-6 text-center text-sm text-muted">
            <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Back to sign in</a>
        </p>
    </x-auth.card>
</x-layouts.storefront>
