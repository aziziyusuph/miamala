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
        @yield('content')
    </body>
</html>
