@extends('layouts.auth')

@section('title', 'Log in')

@section('heading', 'Welcome back')
@section('intro', 'Sign in to manage your business payment records.')

@section('content')
    @if (app(\App\Services\DemoEnvironment::class)->isConfiguredDemoAccount())
        <p role="note" class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-950">
            Demo access is available for evaluation. Use the demo account credentials provided by the site operator.
        </p>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="mb-2 block text-sm font-semibold text-slate-800">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus
                class="min-h-12 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-base text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/20"
                placeholder="you@example.com">
        </div>

        <div>
            <div class="mb-2 flex items-center justify-between gap-3">
                <label for="password" class="block text-sm font-semibold text-slate-800">Password</label>
                <a href="{{ route('password.request') }}" class="text-sm font-semibold text-emerald-800 underline-offset-4 hover:underline focus:outline-none focus:ring-2 focus:ring-emerald-600">Forgot password?</a>
            </div>
            <div class="relative">
                <input id="password" name="password" type="password" autocomplete="current-password" required data-password-input
                    class="min-h-12 w-full rounded-lg border border-slate-300 bg-white px-3.5 pr-14 text-base text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/20"
                    placeholder="Enter your password">
                <button type="button" data-password-toggle="password" aria-label="Show password" class="absolute inset-y-0 right-0 inline-flex w-12 items-center justify-center rounded-r-lg text-slate-500 hover:text-emerald-800 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-emerald-700">
                    <svg data-eye-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg data-eye-off-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="hidden size-5" aria-hidden="true"><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a17.5 17.5 0 0 1-3.1 3.9M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1.4 0 2.7-.3 3.8-.8"/></svg>
                </button>
            </div>
        </div>

        <label for="remember" class="flex w-fit cursor-pointer items-center gap-2.5 text-sm text-slate-700">
            <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember')) class="size-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-700">
            Remember me
        </label>

        <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-lg bg-emerald-800 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Sign in</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">New to Miamala? <a href="{{ route('register') }}" class="font-semibold text-emerald-800 underline-offset-4 hover:underline">Create an account</a></p>
@endsection
