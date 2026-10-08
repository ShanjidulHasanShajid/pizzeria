<x-layouts.storefront title="Choose a new password">
    <x-auth.card title="Choose a new password">
        <form method="POST" action="{{ route('password.store') }}" class="space-y-4" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-form.input name="email" type="email" label="Email" :value="$email" required autocomplete="username" />
            <x-form.input name="password" type="password" label="New password" required autocomplete="new-password"
                help="At least 8 characters, with letters and numbers." />
            <x-form.input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" />
            <x-button type="submit" class="w-full">Save new password</x-button>
        </form>
    </x-auth.card>
</x-layouts.storefront>
