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
        @if (session('status'))
            <div class="border-b border-emerald-200 bg-emerald-50 px-5 py-3 text-center text-sm font-medium leading-6 text-emerald-900 sm:px-8" role="status">
                <div class="mx-auto max-w-7xl">{{ session('status') }}</div>
            </div>
        @endif

        @yield('content')
    </body>
</html>
