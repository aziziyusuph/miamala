<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Business;
use App\Models\User;
use Closure;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    private const LOGIN_DECAY_SECONDS = 60;

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
        $rateLimitKey = $this->loginThrottleKey($request);

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_LOGIN_ATTEMPTS)) {
            event(new Lockout($request));

            return back()
                ->withErrors(['email' => 'These credentials do not match our records. Please try again shortly.'])
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
            RateLimiter::hit($rateLimitKey, self::LOGIN_DECAY_SECONDS);

            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->onlyInput('email');
        }

        RateLimiter::clear($rateLimitKey);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function forgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        Password::sendResetLink([
            'email' => $this->caseInsensitiveEmailLookup(Str::lower($request->input('email'))),
        ]);

        return back()->with('status', 'If an account exists for that email, a password reset link will be sent shortly.');
    }

    public function resetPasswordForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', old('email')),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', PasswordRule::min(8), 'confirmed'],
        ]);

        $status = Password::reset(
            [
                'email' => $this->caseInsensitiveEmailLookup(Str::lower($validated['email'])),
                'password' => $validated['password'],
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $validated['token'],
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Your password has been reset. You can now sign in with your new password.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing')->with('status', 'You have been securely signed out.');
    }

    private function loginThrottleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('email', '')).'|'.$request->ip());
    }

    private function caseInsensitiveEmailLookup(string $email): Closure
    {
        return static function (Builder $query) use ($email): void {
            $emailColumn = $query->getModel()->qualifyColumn('email');
            $matches = (clone $query)
                ->whereRaw("LOWER({$emailColumn}) = ?", [$email])
                ->limit(2)
                ->get();

            if ($matches->count() === 1) {
                $query->whereKey($matches->first()->getKey());
            } else {
                $query->whereRaw('1 = 0');
            }
        };
    }
}
