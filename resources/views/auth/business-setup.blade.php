@extends('layouts.auth')

@section('title', 'Set up your business')
@section('heading', 'Set up your business')
@section('intro', 'Add your business name to finish setting up your account.')

@section('content')
    @if (session('status'))
        <p role="status" class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm leading-6 text-emerald-900">
            {{ session('status') }}
        </p>
    @endif

    <form method="POST" action="{{ route('business.setup.store') }}" class="space-y-5">
        @csrf
        <div>
            <label for="business_name" class="mb-2 block text-sm font-semibold text-slate-800">Business or workspace name</label>
            <input id="business_name" name="business_name" type="text" value="{{ old('business_name') }}" autocomplete="organization" maxlength="255" required autofocus
                class="min-h-12 w-full rounded-lg border border-slate-300 bg-white px-3.5 text-base text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-700 focus:ring-2 focus:ring-emerald-700/20"
                placeholder="Your business name">
            @error('business_name')
                <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-lg bg-emerald-800 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-700 focus:ring-offset-2">Complete business setup</button>
    </form>
@endsection
