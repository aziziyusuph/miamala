@extends('layouts.auth')

@section('title', 'Forgot password')
@section('heading', 'Reset your password')
@section('intro', 'Enter your account email and we’ll send instructions if an account is registered.')

@section('content')
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="mb-2 block text-sm font-semibold text-slate-800">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" required autofocus
                class="min-h-12 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-base text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/20"
                placeholder="you@example.com">
        </div>
        <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-lg bg-emerald-800 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Send reset link</button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-600"><a href="{{ route('login') }}" class="font-semibold text-emerald-800 underline-offset-4 hover:underline">Back to sign in</a></p>
@endsection
