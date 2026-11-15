<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureBusinessSetupAccess;
use App\Http\Requests\BusinessSetupRequest;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BusinessSetupController extends Controller
{
    public function create(): View
    {
        return view('auth.business-setup');
    }

    public function store(BusinessSetupRequest $request): RedirectResponse
    {
        $userId = $request->session()->get(EnsureBusinessSetupAccess::SESSION_KEY);
        $businessName = $request->validated('business_name');

        $business = DB::transaction(function () use ($userId, $businessName): ?Business {
            $user = User::query()->lockForUpdate()->find($userId);

            if ($user === null || ! $user->hasVerifiedEmail()) {
                return null;
            }

            if ($user->business_id !== null) {
                return $user->business;
            }

            $business = Business::query()->create(['name' => $businessName]);
            $user->business()->associate($business);
            $user->save();

            return $business;
        });

        if ($business === null) {
            $request->session()->forget(EnsureBusinessSetupAccess::SESSION_KEY);

            return redirect()->route('onboarding.resume');
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'Business setup is complete. Sign in with your registration email and password.');
    }
}
