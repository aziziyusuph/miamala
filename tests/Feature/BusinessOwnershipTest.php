<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\BusinessOwnershipSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_relationships_are_available(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);
        $transaction = Transaction::factory()->create(['business_id' => $business->id]);

        $this->assertTrue($business->users->contains($user));
        $this->assertTrue($business->transactions->contains($transaction));
        $this->assertTrue($user->business->is($business));
        $this->assertTrue($transaction->business->is($business));
    }

    public function test_factories_create_transactions_with_business_ownership(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create();

        $this->assertNotNull($user->business_id);
        $this->assertDatabaseHas('businesses', ['id' => $user->business_id]);
        $this->assertNotNull($transaction->business_id);
        $this->assertDatabaseHas('businesses', ['id' => $transaction->business_id]);
    }

    public function test_development_provisioning_assigns_existing_orphans(): void
    {
        $user = User::factory()->create(['business_id' => null]);
        $transaction = Transaction::create([
            'customer_name' => 'Development Customer',
            'phone' => '255712345678',
            'provider' => 'M-Pesa',
            'category' => 'Sale',
            'amount' => 100,
            'status' => 'completed',
            'payment_date' => now(),
        ]);

        $this->seed(BusinessOwnershipSeeder::class);

        $business = Business::query()->where('name', BusinessOwnershipSeeder::DEVELOPMENT_BUSINESS_NAME)->firstOrFail();

        $this->assertSame($business->id, $user->fresh()->business_id);
        $this->assertSame($business->id, $transaction->fresh()->business_id);
        $this->assertSame($business->id, User::query()->where('email', 'test@example.com')->value('business_id'));
        $this->assertDatabaseMissing('users', ['business_id' => null]);
        $this->assertDatabaseMissing('transactions', ['business_id' => null]);
    }
}
