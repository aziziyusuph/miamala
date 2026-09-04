<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TransactionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_allowed_transitions_are_applied(): void
    {
        $service = app(TransactionLifecycleService::class);

        foreach ([
            ['pending', 'completed'],
            ['pending', 'failed'],
            ['failed', 'pending'],
            ['completed', 'refunded'],
        ] as [$currentStatus, $targetStatus]) {
            $transaction = Transaction::factory()->create(['status' => $currentStatus]);

            $service->transition($transaction, $targetStatus);

            $this->assertSame($targetStatus, $transaction->fresh()->status);
        }
    }

    #[DataProvider('forbiddenTransitions')]
    public function test_forbidden_transitions_are_rejected(string $currentStatus, string $targetStatus): void
    {
        $transaction = Transaction::factory()->create(['status' => $currentStatus]);

        $this->expectException(ValidationException::class);

        app(TransactionLifecycleService::class)->transition($transaction, $targetStatus);
    }

    public static function forbiddenTransitions(): array
    {
        return [
            'pending to refunded' => ['pending', 'refunded'],
            'completed to pending' => ['completed', 'pending'],
            'completed to failed' => ['completed', 'failed'],
            'failed to completed' => ['failed', 'completed'],
            'failed to refunded' => ['failed', 'refunded'],
            'refunded to pending' => ['refunded', 'pending'],
            'refunded to completed' => ['refunded', 'completed'],
            'refunded to failed' => ['refunded', 'failed'],
        ];
    }

    #[DataProvider('forbiddenTransitions')]
    public function test_forbidden_http_transitions_are_rejected(string $currentStatus, string $targetStatus): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user->business)->create(['status' => $currentStatus]);

        $response = $this->actingAs($user)->from('/transactions/'.$transaction->id.'/edit')->put(
            '/transactions/'.$transaction->id,
            $this->transactionData($transaction, $targetStatus),
        );

        $response->assertRedirect('/transactions/'.$transaction->id.'/edit');
        $response->assertSessionHasErrors('status');
        $this->assertSame($currentStatus, $transaction->fresh()->status);
    }

    #[DataProvider('allowedHttpTransitions')]
    public function test_allowed_transitions_work_through_http(string $currentStatus, string $targetStatus): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user->business)->create(['status' => $currentStatus]);

        $this->actingAs($user)->put(
            '/transactions/'.$transaction->id,
            $this->transactionData($transaction, $targetStatus),
        )->assertRedirect('/transactions');

        $this->assertSame($targetStatus, $transaction->fresh()->status);
    }

    public static function allowedHttpTransitions(): array
    {
        return [
            'pending to failed' => ['pending', 'failed'],
            'failed to pending' => ['failed', 'pending'],
            'completed to refunded' => ['completed', 'refunded'],
        ];
    }

    public function test_same_status_update_remains_allowed_through_http(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user->business)->create(['status' => 'completed']);

        $this->actingAs($user)->put(
            '/transactions/'.$transaction->id,
            $this->transactionData($transaction, 'completed'),
        )->assertRedirect('/transactions');

        $this->assertSame('completed', $transaction->fresh()->status);
    }

    public function test_cross_business_user_cannot_transition_transaction(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->for($otherUser->business)->create(['status' => 'pending']);

        $this->actingAs($user)->put(
            '/transactions/'.$transaction->id,
            $this->transactionData($transaction, 'completed'),
        )->assertNotFound();

        $this->assertSame('pending', $transaction->fresh()->status);
    }

    public function test_unauthenticated_user_cannot_transition_transaction(): void
    {
        $transaction = Transaction::factory()->create(['status' => 'pending']);

        $this->put(
            '/transactions/'.$transaction->id,
            $this->transactionData($transaction, 'completed'),
        )->assertRedirect('/login');

        $this->assertSame('pending', $transaction->fresh()->status);
    }

    public function test_lifecycle_transition_cannot_change_business_id(): void
    {
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();
        $transaction = Transaction::factory()->for($business)->create(['status' => 'pending']);
        $transaction->business_id = $otherBusiness->id;

        $this->expectException(ValidationException::class);

        app(TransactionLifecycleService::class)->transition($transaction, 'completed');
    }

    public function test_http_transition_ignores_business_id_payload(): void
    {
        $user = User::factory()->create();
        $otherBusiness = Business::factory()->create();
        $transaction = Transaction::factory()->for($user->business)->create(['status' => 'pending']);

        $this->actingAs($user)->put(
            '/transactions/'.$transaction->id,
            $this->transactionData($transaction, 'completed') + ['business_id' => $otherBusiness->id],
        )->assertRedirect('/transactions');

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'business_id' => $user->business_id,
            'status' => 'completed',
        ]);
    }

    private function transactionData(Transaction $transaction, string $status): array
    {
        return [
            'customer_name' => $transaction->customer_name,
            'phone' => $transaction->phone,
            'provider' => $transaction->provider,
            'transaction_id' => $transaction->transaction_id,
            'category' => $transaction->category,
            'amount' => $transaction->amount,
            'status' => $status,
            'payment_date' => $transaction->payment_date->toDateString(),
            'order_reference' => $transaction->order_reference,
            'expected_amount' => $transaction->expected_amount,
            'reconciled' => $transaction->reconciled,
            'notes' => $transaction->notes,
        ];
    }
}
