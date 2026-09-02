<?php

namespace Database\Seeders;

use App\Models\Business;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(BusinessOwnershipSeeder::class);

        $business = Business::query()->where('name', BusinessOwnershipSeeder::DEVELOPMENT_BUSINESS_NAME)->firstOrFail();

        $this->call(TransactionSeeder::class, ['business' => $business]);
    }
}
