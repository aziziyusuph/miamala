<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;

class DemoEnvironment
{
    public function isDemoUser(?User $user): bool
    {
        $demoEmail = trim((string) config('miamala.demo.email'));

        return $user !== null
            && $demoEmail !== ''
            && hash_equals($demoEmail, $user->email)
            && $user->business_id !== null
            && $this->isDemoBusiness($user->business);
    }

    public function isDemoBusiness(?Business $business): bool
    {
        $businessName = trim((string) config('miamala.demo.business_name'));

        return $business !== null
            && $businessName !== ''
            && hash_equals($businessName, $business->name);
    }

    public function isConfiguredDemoAccount(): bool
    {
        return trim((string) config('miamala.demo.email')) !== ''
            && trim((string) config('miamala.demo.password')) !== '';
    }
}
