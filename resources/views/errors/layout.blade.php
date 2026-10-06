<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | {{ config('app.name') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite('resources/css/app.css')
</head>
<body class="flex min-h-screen items-center justify-center bg-cream px-4 font-sans text-ink">
    <main class="max-w-md text-center">
        <img src="/images/logo-mark.svg" alt="" class="mx-auto h-16 w-16">
        <p class="mt-6 font-display text-6xl font-bold text-brand-600">@yield('code')</p>
        <h1 class="mt-2 font-display text-2xl font-bold">@yield('title')</h1>
        <p class="mt-3 text-muted">@yield('message')</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="/" class="btn btn-primary btn-lg">Back to home</a>
            <a href="/menu" class="btn btn-outline btn-lg">View the menu</a>
        </div>
    </main>
</body>
</html>