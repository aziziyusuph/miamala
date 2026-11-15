<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessSetupAccess
{
    public const SESSION_KEY = 'business_setup_user_id';

    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->session()->get(self::SESSION_KEY);

        if (! is_numeric($userId)) {
            return redirect()->route('onboarding.resume');
        }

        $user = User::query()->find($userId);

        if ($user === null || ! $user->hasVerifiedEmail()) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('verification.notice');
        }

        if ($user->business_id !== null) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('login');
        }

        return $next($request);
    }
}
