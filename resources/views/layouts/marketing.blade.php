<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Miamala helps organizations record incoming payments and reconcile them against expected amounts.">
        <title>@yield('title', 'Miamala') | Miamala</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-white font-sans text-slate-900 antialiased">
        <nav class="border-b border-slate-200 bg-slate-50" aria-label="Account navigation">
            <div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-3 sm:flex-row sm:items-center sm:justify-end sm:px-8">
                @if (session('status'))
                    <p role="status" class="text-sm font-medium leading-6 text-emerald-900 sm:mr-auto">
                        {{ session('status') }}
                    </p>
                @endif
                <a href="{{ route('register') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-emerald-800 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                    Create account
                </a>
            </div>
        </nav>
        @yield('content')
    </body>
</html>
