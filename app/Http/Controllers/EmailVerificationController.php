<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureBusinessSetupAccess;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(): View
    {
        return view('auth.verify-email', [
            'email' => session('email', old('email')),
        ]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $emailInput = $request->input('email');

        if (is_string($emailInput)) {
            $request->merge(['email' => Str::lower(trim($emailInput))]);
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);
        $email = $validated['email'];
        $users = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->limit(2)
            ->get();

        if ($users->count() === 1) {
            $user = $users->sole();

            if (! $user->hasVerifiedEmail() || $user->business_id === null) {
                $user->sendEmailVerificationNotification();
            }
        }

        return redirect()->route('verification.notice')
            ->with('status', 'If an eligible account exists, a verification or setup link will be sent shortly.')
            ->with('email', $validated['email']);
    }

    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::query()->findOrFail($id);

        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user->business_id !== null) {
            return redirect()->route('login')
                ->with('status', 'Your email is verified. You can now sign in.');
        }

        $request->session()->put(EnsureBusinessSetupAccess::SESSION_KEY, $user->getKey());

        return redirect()->route('business.setup')
            ->with('status', 'Your email is verified. Complete your business setup to continue.');
    }
}
