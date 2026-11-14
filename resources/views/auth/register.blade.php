@extends('layouts.auth')

@section('title', 'Create account')
@section('heading', 'Create your account')
@section('intro', 'Set up your Miamala account and business workspace.')

@section('content')
    <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
        @csrf
        <div>
            <label for="name" class="mb-2 block text-sm font-semibold text-slate-800">Full name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" maxlength="255" required autofocus
                class="min-h-12 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-base text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/20"
                placeholder="Your full name">
        </div>
        <div>
            <label for="business_name" class="mb-2 block text-sm font-semibold text-slate-800">Business or workspace name <span class="font-normal text-slate-500">(optional)</span></label>
            <input id="business_name" name="business_name" type="text" value="{{ old('business_name') }}" autocomplete="organization" maxlength="255"
                class="min-h-12 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-base text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/20"
                placeholder="Your business name">
        </div>
        <div>
            <label for="email" class="mb-2 block text-sm font-semibold text-slate-800">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" required
                class="min-h-12 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-base text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/20"
                placeholder="you@example.com">
        </div>
        @foreach ([['password', 'Password', 'new-password'], ['password_confirmation', 'Confirm password', 'new-password']] as [$field, $label, $autocomplete])
            <div>
                <label for="{{ $field }}" class="mb-2 block text-sm font-semibold text-slate-800">{{ $label }}</label>
                <div class="relative">
                    <input id="{{ $field }}" name="{{ $field }}" type="password" autocomplete="{{ $autocomplete }}" minlength="8" required data-password-input
                        class="min-h-12 w-full rounded-lg border border-slate-300 bg-white px-3.5 pr-14 text-base text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/20"
                        placeholder="At least 8 characters">
                    <button type="button" data-password-toggle="{{ $field }}" aria-label="Show {{ strtolower($label) }}" class="absolute inset-y-0 right-0 inline-flex w-12 items-center justify-center rounded-r-lg text-slate-500 hover:text-emerald-800 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-emerald-700">
                        <svg data-eye-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg data-eye-off-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="hidden size-5" aria-hidden="true"><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a17.5 17.5 0 0 1-3.1 3.9M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1.4 0 2.7-.3 3.8-.8"/></svg>
                    </button>
                </div>
            </div>
        @endforeach
        <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-lg bg-emerald-800 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Create account</button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-600">Already have an account? <a href="{{ route('login') }}" class="font-semibold text-emerald-800 underline-offset-4 hover:underline">Sign in</a></p>
@endsection
