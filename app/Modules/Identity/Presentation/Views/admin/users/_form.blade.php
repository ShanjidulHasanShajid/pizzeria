{{-- Included by create.blade.php and edit.blade.php. Variables: $user (null when creating), $createRoles (null when editing). --}}
<x-admin.form-section title="Details" description="Name and contact details.">
    <x-form.input name="name" label="Full name" :value="$user?->name" required />
    <x-form.input name="email" type="email" label="Email" :value="$user?->email" required />
    <x-form.input name="phone" type="tel" label="Mobile number" :value="$user?->phone" help="Optional for staff. Example: 01712345678" />
    @if ($createRoles)
        <x-form.select name="role" label="Role" :options="$createRoles" placeholder="Choose a role" required />
    @endif
</x-admin.form-section>

<x-admin.form-section title="Password" :description="$user ? 'Leave empty to keep the current password.' : 'At least 8 characters, with letters and numbers.'">
    <x-form.input name="password" type="password" :label="$user ? 'New password' : 'Password'" :required="! $user" autocomplete="new-password" />
    <x-form.input name="password_confirmation" type="password" label="Confirm password" :required="! $user" autocomplete="new-password" />
</x-admin.form-section>
