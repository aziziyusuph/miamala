<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@hasSection('title')@yield('title') | @endif{{ config('app.name', 'Miamala') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <a href="{{ route('dashboard') }}" class="flex w-fit items-center gap-3 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-700 text-lg font-bold text-white" aria-hidden="true">M</span>
                    <span class="text-xl font-semibold tracking-tight text-slate-950">Miamala</span>
                </a>

                <nav class="flex flex-col gap-1 sm:flex-row sm:items-center" aria-label="Primary navigation">
                    <a
                        href="{{ route('dashboard') }}"
                        @class([
                            'rounded-lg px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2',
                            'bg-emerald-50 text-emerald-800' => request()->routeIs('dashboard'),
                            'text-slate-600 hover:bg-slate-100 hover:text-slate-950' => ! request()->routeIs('dashboard'),
                        ])
                        @if (request()->routeIs('dashboard')) aria-current="page" @endif
                    >Dashboard</a>
                    <a
                        href="{{ route('transactions.index') }}"
                        @class([
                            'rounded-lg px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2',
                            'bg-emerald-50 text-emerald-800' => request()->routeIs('transactions.*'),
                            'text-slate-600 hover:bg-slate-100 hover:text-slate-950' => ! request()->routeIs('transactions.*'),
                        ])
                        @if (request()->routeIs('transactions.*')) aria-current="page" @endif
                    >Transactions</a>
                    <form method="POST" action="{{ route('logout') }}" class="sm:ml-2">
                        @csrf
                        <button type="submit" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-left text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 sm:w-auto sm:border-0 sm:text-center">
                            Sign out
                        </button>
                    </form>
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if (app(\App\Services\DemoEnvironment::class)->isDemoUser(auth()->user()))
                <div role="status" class="mb-6 rounded-xl border border-amber-300 bg-amber-50 px-4 py-4 text-sm text-amber-950 sm:px-5">
                    <p class="font-semibold">Demo Environment — Read-only</p>
                    <p class="mt-1 leading-6">You're viewing simulated Miamala transactions. No real money or payment accounts are involved or connected.</p>
                    <p class="mt-1 leading-6">This demo account is read-only. You can explore records and export them, but changes are disabled.</p>
                </div>
            @endif

            @hasSection('page-title')
                <div class="mb-6">
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">@yield('page-title')</h1>
                </div>
            @endif

            @if (session('success') || session('status'))
                <div role="status" class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('success') ?? session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('alerts')
            @yield('content')
        </main>
    </body>
</html>
