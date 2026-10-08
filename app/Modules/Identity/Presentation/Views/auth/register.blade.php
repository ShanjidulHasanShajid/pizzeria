<x-layouts.storefront title="Create account">
    <x-auth.card title="Create your account" subtitle="Order faster and track every delivery.">
        <form method="POST" action="{{ route('register') }}" class="space-y-4" novalidate>
            @csrf
            <x-form.input name="name" label="Full name" required autocomplete="name" autofocus />
            <x-form.input name="email" type="email" label="Email" required autocomplete="email" />
            <x-form.input name="phone" type="tel" label="Mobile number" required autocomplete="tel"
                help="We call or message this number about your delivery. Example: 01712345678" />
            <x-form.input name="password" type="password" label="Password" required autocomplete="new-password"
                help="At least 8 characters, with letters and numbers." />
            <x-form.input name="password_confirmation" type="password" label="Confirm password" required autocomplete="new-password" />

            <x-button type="submit" class="w-full">Create account</x-button>
        </form>

        <p class="mt-6 text-center text-sm text-muted">
            Already have an account?
            <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:underline">Sign in</a>
        </p>
    </x-auth.card>
</x-layouts.storefront>
