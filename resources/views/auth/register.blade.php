@extends('layouts.marketing')

@section('title', 'Create account')

@section('content')
    <main class="mx-auto max-w-xl px-5 py-12 sm:px-8 sm:py-16">
        <a href="{{ route('landing') }}" class="text-sm font-semibold text-emerald-800 hover:text-emerald-950">Miamala</a>
        <h1 class="mt-6 text-3xl font-semibold text-slate-950">Create your account</h1>
        <p class="mt-2 text-sm text-slate-600">Your business workspace will be ready when you sign in.</p>

        @if ($errors->any())
            <div class="mt-6 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="mt-8 space-y-5">
            @csrf
            <div>
                <label for="name" class="mb-1.5 block text-sm font-medium text-slate-800">Your name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required maxlength="255" autofocus class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/20">
            </div>
            <div>
                <label for="business_name" class="mb-1.5 block text-sm font-medium text-slate-800">Business or workspace name <span class="font-normal text-slate-500">(optional)</span></label>
                <input id="business_name" name="business_name" type="text" value="{{ old('business_name') }}" autocomplete="organization" maxlength="255" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/20">
            </div>
            <div>
                <label for="email" class="mb-1.5 block text-sm font-medium text-slate-800">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required maxlength="255" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/20">
            </div>
            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-slate-800">Password</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/20">
            </div>
            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-800">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="8" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-700/20">
            </div>
            <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-md bg-emerald-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Create account</button>
        </form>

        <p class="mt-6 text-sm text-slate-600">Already have an account? <a href="{{ route('login') }}" class="font-semibold text-emerald-800 hover:text-emerald-950">Sign in</a></p>
    </main>
@endsection