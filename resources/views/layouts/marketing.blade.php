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

        @if (request()->routeIs('landing'))
            <template id="homepage-register-link">
                <a href="{{ route('register') }}" class="inline-flex min-h-10 items-center justify-center whitespace-nowrap rounded-lg bg-emerald-800 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 sm:px-4">
                    Create account
                </a>
            </template>
            <script>
                (() => {
                    const headerRow = document.querySelector('header > div > div');
                    const brand = headerRow?.querySelector('[aria-label="Miamala home"]');
                    const signIn = headerRow?.querySelector('a[href="{{ route('login') }}"]');
                    const template = document.getElementById('homepage-register-link');

                    if (!headerRow || !brand || !signIn || !template) return;

                    const actions = document.createElement('div');
                    actions.className = 'flex shrink-0 items-center gap-2 sm:gap-3';
                    signIn.parentNode.insertBefore(actions, signIn);
                    actions.append(signIn, template.content.firstElementChild.cloneNode(true));
                })();
            </script>
        @endif
    </body>
</html>
