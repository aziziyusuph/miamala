<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionReconciliationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->business = $user->business;
        $this->actingAs($user);
    }

    public function test_owned_transaction_can_be_reconciled_and_unreconciled(): void
    {
        $transaction = $this->transactionFactory()->create(['reconciled' => false]);

        $this->post(route('transactions.reconcile', $transaction))
            ->assertRedirect('/transactions')
            ->assertSessionHas('success', 'Transaction reconciled successfully.');
        $this->assertTrue((bool) $transaction->fresh()->reconciled);

        $this->post(route('transactions.reconcile', $transaction))
            ->assertRedirect('/transactions')
            ->assertSessionHas('success', 'Transaction marked as unreconciled.');
        $this->assertFalse((bool) $transaction->fresh()->reconciled);
    }

    public function test_reconciliation_toggle_preserves_transaction_financial_and_lifecycle_fields(): void
    {
        $transaction = $this->transactionFactory()->create([
            'amount' => '230000.00',
            'expected_amount' => '250000.00',
            'status' => 'completed',
            'transaction_id' => 'TX-WORKFLOW-001',
            'payment_date' => '2026-09-01 10:30:00',
            'reconciled' => false,
        ]);
        $before = $transaction->fresh();
        $expected = [
            'amount' => $before->amount,
            'expected_amount' => $before->expected_amount,
            'difference' => $before->difference,
            'reconciliation_status' => $before->reconciliation_status,
            'status' => $before->status,
            'transaction_id' => $before->transaction_id,
            'payment_date' => $before->payment_date->toDateTimeString(),
        ];

        $this->post(route('transactions.reconcile', $transaction));
        $after = $transaction->fresh();

        $this->assertSame($expected['amount'], $after->amount);
        $this->assertSame($expected['expected_amount'], $after->expected_amount);
        $this->assertSame($expected['difference'], $after->difference);
        $this->assertSame($expected['reconciliation_status'], $after->reconciliation_status);
        $this->assertSame($expected['status'], $after->status);
        $this->assertSame($expected['transaction_id'], $after->transaction_id);
        $this->assertSame($expected['payment_date'], $after->payment_date->toDateTimeString());
    }

    public function test_unauthenticated_user_cannot_reconcile_transaction(): void
    {
        $transaction = $this->transactionFactory()->create(['reconciled' => false]);
        auth()->logout();

        $this->post(route('transactions.reconcile', $transaction))
            ->assertRedirect('/login');

        $this->assertFalse((bool) $transaction->fresh()->reconciled);
    }

    public function test_cross_business_user_cannot_reconcile_or_unreconcile_transaction(): void
    {
        $otherUser = User::factory()->create();
        $reconciled = Transaction::factory()->for($otherUser->business)->create(['reconciled' => false]);
        $unreconciled = Transaction::factory()->for($otherUser->business)->create(['reconciled' => true]);

        $this->post(route('transactions.reconcile', $reconciled))->assertNotFound();
        $this->post(route('transactions.reconcile', $unreconciled))->assertNotFound();

        $this->assertFalse((bool) $reconciled->fresh()->reconciled);
        $this->assertTrue((bool) $unreconciled->fresh()->reconciled);
    }

    public function test_soft_deleted_transaction_cannot_be_reconciled_or_unreconciled(): void
    {
        $reconciled = $this->transactionFactory()->create(['reconciled' => false]);
        $unreconciled = $this->transactionFactory()->create(['reconciled' => true]);
        $reconciled->delete();
        $unreconciled->delete();

        $this->post(route('transactions.reconcile', $reconciled))->assertNotFound();
        $this->post(route('transactions.reconcile', $unreconciled))->assertNotFound();
    }

    public function test_reconciliation_filter_returns_only_the_requested_state(): void
    {
        $this->transactionFactory()->create(['customer_name' => 'Reconciled Customer', 'reconciled' => true]);
        $this->transactionFactory()->create(['customer_name' => 'Unreconciled Customer', 'reconciled' => false]);

        $this->get('/transactions?reconciled=1')
            ->assertOk()
            ->assertSee('Reconciled Customer')
            ->assertDontSee('Unreconciled Customer');
        $this->get('/transactions?reconciled=0')
            ->assertOk()
            ->assertSee('Unreconciled Customer')
            ->assertDontSee('Reconciled Customer');
    }

    public function test_reconciliation_filter_is_business_scoped_and_combines_with_existing_filters(): void
    {
        $otherUser = User::factory()->create();
        $this->transactionFactory()->create([
            'customer_name' => 'Included Customer',
            'provider' => 'M-Pesa',
            'status' => 'completed',
            'category' => 'School Fees',
            'payment_date' => '2026-09-10',
            'reconciled' => true,
        ]);
        $this->transactionFactory()->create([
            'customer_name' => 'Wrong Reconciliation State',
            'provider' => 'M-Pesa',
            'status' => 'completed',
            'category' => 'School Fees',
            'payment_date' => '2026-09-10',
            'reconciled' => false,
        ]);
        Transaction::factory()->for($otherUser->business)->create([
            'customer_name' => 'Other Business Customer',
            'provider' => 'M-Pesa',
            'status' => 'completed',
            'category' => 'School Fees',
            'payment_date' => '2026-09-10',
            'reconciled' => true,
        ]);

        $this->get('/transactions?search=Customer&provider=M-Pesa&status=completed&category=School+Fees&from=2026-09-01&to=2026-09-30&reconciled=1')
            ->assertOk()
            ->assertSee('Included Customer')
            ->assertDontSee('Wrong Reconciliation State')
            ->assertDontSee('Other Business Customer')
            ->assertSee('reconciled=1', false);
    }

    public function test_csv_export_respects_reconciliation_filter(): void
    {
        $this->transactionFactory()->create(['customer_name' => 'Export Reconciled', 'reconciled' => true]);
        $this->transactionFactory()->create(['customer_name' => 'Do Not Export', 'reconciled' => false]);

        $csv = $this->get('/transactions/export?reconciled=1')->streamedContent();

        $this->assertStringContainsString('Export Reconciled', $csv);
        $this->assertStringNotContainsString('Do Not Export', $csv);
    }

    private function transactionFactory(): Factory
    {
        return Transaction::factory()->for($this->business);
    }
}
