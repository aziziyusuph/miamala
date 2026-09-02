<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Services\TransactionReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected function transactionData(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Asha Mwalimu',
            'phone' => '255712345678',
            'provider' => 'M-Pesa',
            'transaction_id' => 'TX-RECON-1001',
            'category' => 'School Fees',
            'amount' => 250000,
            'status' => 'completed',
            'payment_date' => '2026-08-01 10:00:00',
            'order_reference' => 'ORD-1001',
            'expected_amount' => 250000,
            'reconciled' => false,
            'notes' => 'Tuition payment received.',
        ], $overrides);
    }

    public function test_exact_match_is_classified_correctly(): void
    {
        $transaction = Transaction::create($this->transactionData());

        $this->assertSame('exact_match', $transaction->reconciliation_status);
        $this->assertSame(0.0, (float) $transaction->difference);
    }

    public function test_underpayment_is_classified_correctly(): void
    {
        $transaction = Transaction::create($this->transactionData([
            'amount' => 230000,
            'expected_amount' => 250000,
        ]));

        $this->assertSame('underpaid', $transaction->reconciliation_status);
        $this->assertSame(-20000.0, (float) $transaction->difference);
    }

    public function test_overpayment_is_classified_correctly(): void
    {
        $transaction = Transaction::create($this->transactionData([
            'amount' => 260000,
            'expected_amount' => 250000,
        ]));

        $this->assertSame('overpaid', $transaction->reconciliation_status);
        $this->assertSame(10000.0, (float) $transaction->difference);
    }

    public function test_missing_expected_amount_is_unreconciled(): void
    {
        $transaction = Transaction::create($this->transactionData([
            'expected_amount' => null,
        ]));

        $this->assertSame('unreconciled', $transaction->reconciliation_status);
        $this->assertNull($transaction->difference);
    }

    public function test_missing_order_reference_is_unreconciled(): void
    {
        $transaction = Transaction::create($this->transactionData([
            'order_reference' => null,
        ]));

        $this->assertSame('unreconciled', $transaction->reconciliation_status);
    }

    public function test_manual_reconciliation_state_is_independent_from_financial_status(): void
    {
        $transaction = Transaction::create($this->transactionData([
            'amount' => 230000,
            'expected_amount' => 250000,
            'reconciled' => true,
        ]));

        $this->assertTrue((bool) $transaction->reconciled);
        $this->assertSame('underpaid', $transaction->reconciliation_status);
    }

    public function test_decimal_values_with_tolerance_are_treated_as_exact_match(): void
    {
        $transaction = Transaction::create($this->transactionData([
            'amount' => 250000.01,
            'expected_amount' => 250000,
        ]));

        $this->assertSame('exact_match', $transaction->reconciliation_status);
        $this->assertSame(0.01, round((float) $transaction->difference, 2));
    }

    public function test_cent_values_are_compared_without_float_arithmetic(): void
    {
        $transaction = Transaction::create($this->transactionData([
            'amount' => '100.10',
            'expected_amount' => '100.09',
        ]));

        $this->assertSame('exact_match', $transaction->reconciliation_status);
        $this->assertSame(0.01, (float) $transaction->difference);
    }

    public function test_amount_validation_uses_decimal_minor_units(): void
    {
        $transaction = Transaction::create($this->transactionData([
            'amount' => '100.10',
            'expected_amount' => '100.09',
        ]));

        $service = app(TransactionReconciliationService::class);

        $this->assertSame(10010, $service->toMinorUnits($transaction->amount));
        $this->assertSame(10009, $service->toMinorUnits($transaction->expected_amount));
    }
}
