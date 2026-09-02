<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Policies\TransactionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TransactionPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_policy_is_discovered_automatically(): void
    {
        $this->assertInstanceOf(TransactionPolicy::class, Gate::getPolicyFor(Transaction::class));
    }

    public function test_same_business_user_is_authorized_for_transaction_actions(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user->business)->create();
        $policy = new TransactionPolicy;

        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->view($user, $transaction));
        $this->assertTrue($policy->create($user));
        $this->assertTrue($policy->update($user, $transaction));
        $this->assertTrue($policy->delete($user, $transaction));
    }

    public function test_cross_business_user_is_denied_transaction_actions(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->for($otherUser->business)->create();
        $policy = new TransactionPolicy;

        $this->assertTrue($policy->viewAny($user));
        $this->assertFalse($policy->view($user, $transaction));
        $this->assertFalse($policy->update($user, $transaction));
        $this->assertFalse($policy->delete($user, $transaction));
    }

    public function test_user_without_business_is_denied_all_transaction_actions(): void
    {
        $user = User::factory()->create(['business_id' => null]);
        $transaction = Transaction::factory()->create();
        $policy = new TransactionPolicy;

        $this->assertFalse($policy->viewAny($user));
        $this->assertFalse($policy->view($user, $transaction));
        $this->assertFalse($policy->create($user));
        $this->assertFalse($policy->update($user, $transaction));
        $this->assertFalse($policy->delete($user, $transaction));
    }

    public function test_transaction_controller_exercises_view_any_policy(): void
    {
        $user = User::factory()->create();

        Gate::spy();

        $this->actingAs($user)->get('/transactions')->assertOk();

        Gate::shouldHaveReceived('authorize')
            ->with('viewAny', Transaction::class)
            ->once();
    }
}
