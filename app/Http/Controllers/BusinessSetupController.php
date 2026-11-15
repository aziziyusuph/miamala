<?php

namespace App\Http\Controllers;

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
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $userId = $user->getKey();
        $businessName = $request->validated('business_name');

        $business = DB::transaction(function () use ($userId, $businessName): ?Business {
            $user = User::query()->lockForUpdate()->find($userId);

            if ($user === null) {
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
            return redirect()->route('login');
        }

        $user->refresh();

        return redirect()->route('dashboard')
            ->with('status', 'Business setup is complete.');
    }
}
