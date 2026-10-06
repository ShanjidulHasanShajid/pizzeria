@if (session('success') || session('error') || session('status') || $errors->any())
    <div class="space-y-3">
        @if (session('success'))
            <x-alert type="success" dismissible>{{ session('success') }}</x-alert>
        @endif
        @if (session('status'))
            <x-alert type="info" dismissible>{{ session('status') }}</x-alert>
        @endif
        @if (session('error'))
            <x-alert type="error" dismissible>{{ session('error') }}</x-alert>
        @endif
        @if ($errors->any())
            <x-alert type="error">Please fix the highlighted fields and try again.</x-alert>
        @endif
    </div>
@endif