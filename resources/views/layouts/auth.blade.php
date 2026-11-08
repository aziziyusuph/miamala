<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Secure access to Miamala payment records and reconciliation.">
        <title>@yield('title', 'Account') | Miamala</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
        <div class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(420px,0.9fr)]">
            <aside class="relative hidden overflow-hidden bg-slate-950 px-10 py-12 text-white lg:flex lg:flex-col lg:justify-between xl:px-16">
                <div class="pointer-events-none absolute -right-32 top-1/4 size-[28rem] rounded-full bg-emerald-400/10 blur-3xl" aria-hidden="true"></div>
                <a href="{{ route('landing') }}" class="relative flex w-fit items-center gap-3 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:ring-offset-2 focus:ring-offset-slate-950">
                    <span class="flex size-11 items-center justify-center rounded-xl bg-emerald-400 text-lg font-bold text-slate-950" aria-hidden="true">M</span>
                    <span class="text-xl font-semibold tracking-tight">Miamala</span>
                </a>
                <div class="relative max-w-xl py-12">
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-300">Payment records, clearly managed</p>
                    <h2 class="mt-5 text-4xl font-semibold leading-tight tracking-tight xl:text-5xl">A clearer view of your business payments.</h2>
                    <p class="mt-6 max-w-lg text-lg leading-8 text-slate-300">Keep transactions, expected amounts, and reconciliation status together in one straightforward workflow.</p>
                </div>
                <p class="relative text-sm text-slate-400">Open-source tools for organizations across Africa.</p>
            </aside>

            <main class="flex min-h-screen flex-col px-5 py-6 sm:px-8 lg:px-12">
                <div class="mx-auto flex w-full max-w-md flex-1 flex-col">
                    <a href="{{ route('landing') }}" class="flex w-fit items-center gap-2.5 rounded-md text-slate-950 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2 lg:hidden">
                        <span class="flex size-9 items-center justify-center rounded-lg bg-emerald-800 font-bold text-white" aria-hidden="true">M</span>
                        <span class="text-lg font-semibold tracking-tight">Miamala</span>
                    </a>
                    <section class="my-auto py-10 sm:py-14" aria-labelledby="auth-heading">
                        <a href="{{ route('landing') }}" class="mb-8 hidden w-fit items-center gap-2.5 rounded-md text-slate-950 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2 lg:flex">
                            <span class="flex size-9 items-center justify-center rounded-lg bg-emerald-800 font-bold text-white" aria-hidden="true">M</span>
                            <span class="text-lg font-semibold tracking-tight">Miamala</span>
                        </a>
                        <p class="text-sm font-bold uppercase tracking-wider text-emerald-800">Your account</p>
                        <h1 id="auth-heading" class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">@yield('heading', 'Welcome')</h1>
                        <p class="mt-2 text-sm leading-6 text-slate-600">@yield('intro')</p>

                        @if (session('status'))
                            <div role="status" class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm leading-6 text-emerald-900">{{ session('status') }}</div>
                        @endif
                        @if ($errors->any())
                            <div role="alert" class="mt-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
                                <ul class="list-inside list-disc space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="mt-7">@yield('content')</div>
                    </section>
                    <footer class="border-t border-slate-200 py-5 text-center text-xs text-slate-500">© {{ date('Y') }} Miamala · <a href="{{ route('landing') }}" class="font-medium text-slate-700 underline-offset-4 hover:underline">Back to homepage</a></footer>
                </div>
            </main>
        </div>
    </body>
</html>
