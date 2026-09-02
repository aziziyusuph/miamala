<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class BusinessOwnershipSeeder extends Seeder
{
    public const DEVELOPMENT_BUSINESS_NAME = 'Miamala Development Business';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Development ownership provisioning is only available in local or testing environments.');
        }

        $business = Business::query()->firstOrCreate([
            'name' => self::DEVELOPMENT_BUSINESS_NAME,
        ]);

        User::query()->firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => 'password',
        ]);

        User::query()->whereNull('business_id')->update([
            'business_id' => $business->id,
        ]);

        Transaction::query()->whereNull('business_id')->update([
            'business_id' => $business->id,
        ]);

        if (User::query()->whereNull('business_id')->exists() || Transaction::query()->whereNull('business_id')->exists()) {
            throw new RuntimeException('Ownership provisioning left orphaned users or transactions.');
        }
    }
}
