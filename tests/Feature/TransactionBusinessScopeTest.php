<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionBusinessScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_cannot_access_transaction_routes(): void
    {
        $transaction = Transaction::factory()->create();
        $data = [
            '_token' => 'test-token',
            'customer_name' => 'Unauthenticated Customer',
            'phone' => '255712345678',
            'provider' => 'M-Pesa',
            'category' => 'Sale',
            'amount' => 100,
            'status' => 'completed',
            'payment_date' => '2026-09-02',
        ];

        $this->get('/transactions')->assertRedirect('/login');
        $this->get('/transactions/create')->assertRedirect('/login');
        $this->get('/transactions/'.$transaction->id)->assertRedirect('/login');
        $this->get('/transactions/'.$transaction->id.'/edit')->assertRedirect('/login');
        $this->withSession(['_token' => 'test-token'])->post('/transactions', $data)->assertRedirect('/login');
        $this->withSession(['_token' => 'test-token'])->put('/transactions/'.$transaction->id, $data)->assertRedirect('/login');
        $this->withSession(['_token' => 'test-token'])->delete('/transactions/'.$transaction->id, ['_token' => 'test-token'])->assertRedirect('/login');
    }

    public function test_user_sees_only_transactions_from_their_business(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $transactionA = Transaction::factory()->for($userA->business)->create(['customer_name' => 'Business A Customer']);
        $transactionB = Transaction::factory()->for($userB->business)->create(['customer_name' => 'Business B Customer']);

        $response = $this->actingAs($userA)->get('/transactions');

        $response->assertOk();
        $response->assertSee($transactionA->customer_name);
        $response->assertDontSee($transactionB->customer_name);
    }

    public function test_search_and_filters_remain_business_scoped(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        Transaction::factory()->for($userA->business)->create([
            'customer_name' => 'Matching Customer',
            'provider' => 'M-Pesa',
            'status' => 'completed',
            'category' => 'Sale',
        ]);
        Transaction::factory()->for($userB->business)->create([
            'customer_name' => 'Hidden Customer',
            'provider' => 'M-Pesa',
            'status' => 'completed',
            'category' => 'Sale',
        ]);

        $response = $this->actingAs($userA)->get('/transactions?search=Customer&provider=M-Pesa&status=completed&category=Sale');

        $response->assertOk();
        $response->assertSee('Matching Customer');
        $response->assertDontSee('Hidden Customer');
    }

    public function test_pagination_remains_business_scoped(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        Transaction::factory()->count(16)->for($userA->business)->create();
        Transaction::factory()->for($userB->business)->create(['customer_name' => 'Hidden Page Customer']);

        $response = $this->actingAs($userA)->get('/transactions?page=2');

        $response->assertOk();
        $response->assertDontSee('Hidden Page Customer');
    }

    public function test_another_business_transaction_cannot_be_viewed_or_edited(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $transactionB = Transaction::factory()->for($userB->business)->create();

        $this->actingAs($userA)->get('/transactions/'.$transactionB->id)->assertNotFound();
        $this->actingAs($userA)->get('/transactions/'.$transactionB->id.'/edit')->assertNotFound();
    }

    public function test_another_business_transaction_cannot_be_updated_or_deleted(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $transactionB = Transaction::factory()->for($userB->business)->create([
            'customer_name' => 'Protected Customer',
        ]);

        $data = [
            'customer_name' => 'Changed Customer',
            'phone' => '255712345678',
            'provider' => 'M-Pesa',
            'category' => 'Sale',
            'amount' => 100,
            'status' => 'completed',
            'payment_date' => '2026-09-02',
        ];

        $this->actingAs($userA)->put('/transactions/'.$transactionB->id, $data)->assertNotFound();
        $this->actingAs($userA)->delete('/transactions/'.$transactionB->id)->assertNotFound();

        $this->assertDatabaseHas('transactions', [
            'id' => $transactionB->id,
            'customer_name' => 'Protected Customer',
            'deleted_at' => null,
        ]);
    }

    public function test_transaction_creation_uses_authenticated_business(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $response = $this->actingAs($userA)->post('/transactions', [
            'business_id' => $userB->business_id,
            'customer_name' => 'Owned Customer',
            'phone' => '255712345678',
            'provider' => 'M-Pesa',
            'category' => 'Sale',
            'amount' => 100,
            'status' => 'completed',
            'payment_date' => '2026-09-02',
        ]);

        $response->assertRedirect('/transactions');
        $this->assertDatabaseHas('transactions', [
            'customer_name' => 'Owned Customer',
            'business_id' => $userA->business_id,
        ]);
        $this->assertDatabaseMissing('transactions', [
            'customer_name' => 'Owned Customer',
            'business_id' => $userB->business_id,
        ]);
    }

    public function test_user_without_business_is_denied_transaction_access(): void
    {
        $user = User::factory()->create(['business_id' => null]);

        $this->actingAs($user)->get('/transactions')->assertNotFound();
    }

    public function test_user_without_business_cannot_create_a_transaction(): void
    {
        $user = User::factory()->create(['business_id' => null]);

        $response = $this->actingAs($user)->post('/transactions', [
            'customer_name' => 'Unowned Customer',
            'phone' => '255712345678',
            'provider' => 'M-Pesa',
            'category' => 'Sale',
            'amount' => 100,
            'status' => 'completed',
            'payment_date' => '2026-09-02',
        ]);

        $response->assertNotFound();
        $this->assertDatabaseMissing('transactions', ['customer_name' => 'Unowned Customer']);
    }
}
