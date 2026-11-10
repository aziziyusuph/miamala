<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function createRegistration(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data): User {
            $business = Business::create([
                'name' => ($data['business_name'] ?? null) ?: $data['name'].' Workspace',
            ]);

            return $business->users()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $email = Str::lower(trim($credentials['email']));
        $rateLimitKey = 'login-failures:'.hash('sha256', $email);

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->onlyInput('email');
        }

        $submittedEmail = $request->submittedEmail() ?? $credentials['email'];
        $user = User::query()->where('email', $submittedEmail)->first();

        if ($user === null) {
            $matchingUsers = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->limit(2)
                ->get();

            $user = $matchingUsers->firstWhere('email', $submittedEmail)
                ?? ($matchingUsers->count() === 1 ? $matchingUsers->first() : null);
        }

        $authenticated = $user !== null && Auth::attempt([
            'email' => $user->email,
            'password' => $credentials['password'],
        ], $request->boolean('remember'));

        if (! $authenticated) {
            RateLimiter::hit($rateLimitKey, 60);

            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->onlyInput('email');
        }

        RateLimiter::clear($rateLimitKey);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
