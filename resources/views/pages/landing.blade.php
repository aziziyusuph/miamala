@extends('layouts.marketing')

@section('title', 'Payment records and reconciliation')

@section('content')
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <div class="flex items-center justify-between gap-4 py-5">
                <a href="{{ route('landing') }}" class="flex shrink-0 items-center gap-3 rounded-md focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2" aria-label="Miamala home">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-800 text-lg font-bold text-white" aria-hidden="true">M</span>
                    <span class="text-xl font-semibold tracking-tight text-slate-950">Miamala</span>
                </a>
                <a href="{{ route('login') }}" class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">Sign in</a>
            </div>
            <nav class="flex flex-wrap gap-x-6 gap-y-2 border-t border-slate-100 py-3 text-sm font-medium text-slate-600" aria-label="Main navigation">
                <a href="#how-it-works" class="rounded-sm py-1 hover:text-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600">How it works</a>
                <a href="#channels" class="rounded-sm py-1 hover:text-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600">Channels</a>
                <a href="#open-source" class="rounded-sm py-1 hover:text-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600">Open source</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="relative overflow-hidden bg-slate-950 text-white">
            <div class="pointer-events-none absolute -right-24 -top-36 size-96 rounded-full bg-emerald-500/10 blur-3xl" aria-hidden="true"></div>
            <div class="mx-auto grid max-w-7xl gap-12 px-5 py-16 sm:px-8 sm:py-24 lg:grid-cols-2 lg:items-center lg:gap-16 lg:py-28">
                <div class="relative">
                    <p class="mb-5 inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-emerald-200">
                        <span class="size-1.5 rounded-full bg-emerald-300" aria-hidden="true"></span>
                        Open-source project for organizations
                    </p>
                    <h1 class="max-w-2xl text-4xl font-semibold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                        Payment records and reconciliation, in one clear workflow.
                    </h1>
                    <p class="mt-6 max-w-xl text-lg leading-8 text-slate-300">
                        Miamala helps organizations record incoming payments across mobile money, bank transfers, and manual entries, then compare them with expected amounts for reconciliation.
                    </p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('login') }}" class="inline-flex min-h-12 items-center justify-center rounded-lg bg-emerald-400 px-6 py-3 text-sm font-bold text-slate-950 shadow-sm hover:bg-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:ring-offset-2 focus:ring-offset-slate-950">
                            Explore Demo
                            <span class="ml-2" aria-hidden="true">→</span>
                        </a>
                        <a href="https://github.com/aziziyusuph/miamala" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-12 items-center justify-center rounded-lg border border-slate-600 px-6 py-3 text-sm font-semibold text-white hover:border-slate-400 hover:bg-white/5 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-slate-950">
                            View Source
                            <span class="ml-2" aria-hidden="true">↗</span>
                        </a>
                    </div>
                    <p class="mt-5 text-sm text-slate-400">Built for clear records and confident review—not payment processing.</p>
                </div>

                <div class="relative rounded-2xl border border-white/10 bg-white/[0.04] p-5 shadow-2xl shadow-black/20 sm:p-7">
                    <div class="flex items-center justify-between border-b border-white/10 pb-5">
                        <div>
                            <p class="text-sm font-semibold text-white">A clearer payment workflow</p>
                            <p class="mt-1 text-xs text-slate-400">From record to reconciliation</p>
                        </div>
                        <span class="rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1 text-xs font-medium text-emerald-200">Organization records</span>
                    </div>
                    <div class="space-y-3 py-5">
                        <div class="flex items-center gap-4 rounded-xl border border-white/10 bg-slate-900/70 p-4">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-emerald-400/10 text-sm font-bold text-emerald-300">01</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-white">Record an incoming payment</p>
                                <p class="mt-1 text-xs text-slate-400">Capture the details your team needs</p>
                            </div>
                            <span class="text-emerald-300" aria-hidden="true">✓</span>
                        </div>
                        <div class="flex items-center gap-4 rounded-xl border border-white/10 bg-slate-900/70 p-4">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-sky-400/10 text-sm font-bold text-sky-300">02</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-white">Compare with expected</p>
                                <p class="mt-1 text-xs text-slate-400">See the difference at a glance</p>
                            </div>
                            <span class="text-sky-300" aria-hidden="true">↔</span>
                        </div>
                        <div class="flex items-center gap-4 rounded-xl border border-white/10 bg-slate-900/70 p-4">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-violet-400/10 text-sm font-bold text-violet-300">03</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-white">Review and reconcile</p>
                                <p class="mt-1 text-xs text-slate-400">Keep the follow-up state visible</p>
                            </div>
                            <span class="text-violet-300" aria-hidden="true">✓</span>
                        </div>
                    </div>
                    <p class="border-t border-white/10 pt-4 text-xs leading-5 text-slate-400">
                        Records are entered and managed by your organization. No payment rails are connected.
                    </p>
                </div>
            </div>
        </section>

        <section class="border-b border-amber-200 bg-amber-50" aria-label="Demo notice">
            <div class="mx-auto flex max-w-7xl gap-3 px-5 py-4 text-sm leading-6 text-amber-950 sm:px-8">
                <span class="mt-0.5 font-bold text-amber-700" aria-hidden="true">i</span>
                <p><strong class="font-semibold">Demo environment</strong> — transaction records shown in the public demo are simulated. No real money is processed.</p>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 sm:py-28">
            <div class="grid gap-10 lg:grid-cols-[0.85fr_1.15fr] lg:items-center lg:gap-20">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wider text-emerald-800">The challenge</p>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Payment records can come from many places. Clarity should not.</h2>
                </div>
                <div>
                    <p class="text-lg leading-8 text-slate-600">
                        Organizations may receive or record payments through multiple channels, while expected amounts and payment records still need to be compared and reviewed. Miamala gives teams one place to record those details and follow each reconciliation.
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-2 text-sm font-semibold">
                        <span class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-800 shadow-sm">Record payment</span>
                        <span class="text-emerald-700" aria-hidden="true">→</span>
                        <span class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-800 shadow-sm">Compare amount</span>
                        <span class="text-emerald-700" aria-hidden="true">→</span>
                        <span class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-800 shadow-sm">Review difference</span>
                        <span class="text-emerald-700" aria-hidden="true">→</span>
                        <span class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-emerald-900 shadow-sm">Reconcile</span>
                    </div>
                </div>
            </div>
        </section>

        <section id="how-it-works" class="scroll-mt-8 bg-slate-50 py-20 sm:py-28">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <div class="max-w-2xl">
                    <p class="text-sm font-bold uppercase tracking-wider text-emerald-800">How it works</p>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">A straightforward workflow, from entry to review.</h2>
                    <p class="mt-4 text-lg leading-7 text-slate-600">Keep payment details, expected amounts, and follow-up status together in your organization’s records.</p>
                </div>
                <ol class="mt-12 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    <li class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <span class="flex size-11 items-center justify-center rounded-xl bg-emerald-100 text-sm font-bold text-emerald-900">01</span>
                        <h3 class="mt-5 text-lg font-semibold text-slate-950">Record</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Enter payment details such as customer, channel, amount, date, and reference.</p>
                    </li>
                    <li class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <span class="flex size-11 items-center justify-center rounded-xl bg-sky-100 text-sm font-bold text-sky-900">02</span>
                        <h3 class="mt-5 text-lg font-semibold text-slate-950">Compare</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Add the expected amount and see how it compares with the amount received.</p>
                    </li>
                    <li class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <span class="flex size-11 items-center justify-center rounded-xl bg-amber-100 text-sm font-bold text-amber-900">03</span>
                        <h3 class="mt-5 text-lg font-semibold text-slate-950">Review</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Check the calculated difference and identify exact matches, underpayments, or overpayments.</p>
                    </li>
                    <li class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <span class="flex size-11 items-center justify-center rounded-xl bg-violet-100 text-sm font-bold text-violet-900">04</span>
                        <h3 class="mt-5 text-lg font-semibold text-slate-950">Reconcile</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Mark a record reconciled or unreconciled so its review state stays clear.</p>
                    </li>
                </ol>
            </div>
        </section>

        <section id="channels" class="scroll-mt-8 border-b border-slate-200 py-20 sm:py-24">
            <div class="mx-auto grid max-w-7xl gap-10 px-5 sm:px-8 lg:grid-cols-2 lg:items-center">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wider text-emerald-800">Payment channels</p>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">Record transactions from the payment channels your organization uses.</h2>
                    <p class="mt-4 text-lg leading-7 text-slate-600">These are channel values your team can select when recording a transaction—not live provider integrations or connected payment rails.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Channels supported by the transaction model</p>
                    <ul class="mt-5 flex flex-wrap gap-3">
                        @foreach (['M-Pesa', 'Airtel Money', 'Mixx by Yas', 'Bank', 'Cash', 'Other'] as $channel)
                            <li class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-800">{{ $channel }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>

        <section class="overflow-hidden bg-emerald-950 py-20 text-white sm:py-28">
            <div class="mx-auto grid max-w-7xl gap-12 px-5 sm:px-8 lg:grid-cols-2 lg:items-center lg:gap-20">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wider text-emerald-300">Reconciliation, made visible</p>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">See what lines up—and what needs another look.</h2>
                    <p class="mt-5 text-lg leading-8 text-emerald-100/80">Miamala calculates the difference between received and expected amounts, classifies the match, and keeps the reviewed state separate so teams can manage follow-up.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-white/10 bg-white/[0.06] p-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-emerald-200">Amount received</p>
                        <p class="mt-2 text-lg font-semibold">Recorded payment</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/[0.06] p-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-emerald-200">Expected amount</p>
                        <p class="mt-2 text-lg font-semibold">Organization expectation</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/[0.06] p-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-emerald-200">Calculated difference</p>
                        <p class="mt-2 text-lg font-semibold">Received minus expected</p>
                    </div>
                    <div class="rounded-xl border border-emerald-300/20 bg-emerald-300/10 p-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-emerald-200">Review state</p>
                        <p class="mt-2 text-lg font-semibold">Reconciled or unreconciled</p>
                    </div>
                    <p class="text-sm leading-6 text-emerald-100/70 sm:col-span-2">Reconciliation status distinguishes unreconciled, exact match, underpaid, and overpaid records.</p>
                </div>
            </div>
        </section>

        <section id="open-source" class="scroll-mt-8 bg-slate-50 py-20 sm:py-28">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <div class="grid gap-12 rounded-3xl border border-slate-200 bg-white p-7 shadow-sm sm:p-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center lg:gap-16 lg:p-14">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wider text-emerald-800">Open source, built in the open</p>
                        <h2 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">A practical project for better payment records.</h2>
                        <p class="mt-4 text-lg leading-8 text-slate-600">Miamala is an Africa-focused open-source project for organizations that need a clear way to record incoming payments and review reconciliation. Explore the code, follow development, or contribute through the public repository.</p>
                        <a href="https://github.com/aziziyusuph/miamala" target="_blank" rel="noopener noreferrer" class="mt-7 inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">
                            View source on GitHub
                            <span class="ml-2" aria-hidden="true">↗</span>
                        </a>
                    </div>
                    <div class="border-t border-slate-200 pt-7 lg:border-l lg:border-t-0 lg:pl-10 lg:pt-0">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">The current stack</p>
                        <ul class="mt-4 flex flex-wrap gap-2">
                            @foreach (['Laravel', 'PHP', 'PostgreSQL', 'Blade', 'Tailwind CSS', 'Vite'] as $technology)
                                <li class="rounded-md bg-slate-100 px-3 py-2 text-sm font-medium text-slate-700">{{ $technology }}</li>
                            @endforeach
                        </ul>
                        <p class="mt-4 text-sm leading-6 text-slate-500">PostgreSQL is supported by the project’s database configuration; SQLite is also available for local development.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
            <div class="grid gap-8 sm:grid-cols-2 sm:items-start">
                <div>
                    <a href="{{ route('landing') }}" class="text-lg font-semibold tracking-tight text-slate-950">Miamala</a>
                    <p class="mt-2 max-w-md text-sm leading-6 text-slate-600">Record incoming payments and compare them with expected amounts for reconciliation.</p>
                </div>
                <nav class="flex flex-wrap gap-x-6 gap-y-3 text-sm font-medium" aria-label="Footer navigation">
                    <a href="https://github.com/aziziyusuph/miamala" target="_blank" rel="noopener noreferrer" class="text-slate-600 hover:text-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600">GitHub repository <span aria-hidden="true">↗</span></a>
                    <a href="{{ route('login') }}" class="text-slate-600 hover:text-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600">Sign in</a>
                </nav>
            </div>
            <div class="mt-8 flex flex-col gap-2 border-t border-slate-200 pt-5 text-xs leading-5 text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                <p>Demo environment: transaction records shown in the public demo are simulated. No real money is processed.</p>
                <p>© {{ date('Y') }} Miamala</p>
            </div>
        </div>
    </footer>
@endsection
