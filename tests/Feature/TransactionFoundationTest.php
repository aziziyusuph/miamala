<?php

namespace Tests\Feature;

use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class TransactionFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_can_be_created(): void
    {
        $transaction = Transaction::create([
            'customer_name' => 'Asha Mwalimu',
            'phone' => '255712345678',
            'provider' => 'M-Pesa',
            'transaction_id' => 'MPESA-1001',
            'category' => 'School Fees',
            'amount' => 250000,
            'status' => 'completed',
            'payment_date' => now()->subHour(),
            'order_reference' => 'ORD-1001',
            'expected_amount' => 250000,
            'reconciled' => true,
            'notes' => 'Tuition payment received.',
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'customer_name' => 'Asha Mwalimu',
            'provider' => 'M-Pesa',
            'status' => 'completed',
        ]);
    }

    public function test_required_fields_are_required(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Transaction::create([
            'phone' => '255712345678',
            'provider' => 'M-Pesa',
            'category' => 'School Fees',
            'amount' => 25000,
            'status' => 'completed',
            'payment_date' => now(),
        ]);
    }

    public function test_amount_must_be_positive(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Transaction::create([
            'customer_name' => 'John Doe',
            'phone' => '255765432109',
            'provider' => 'Airtel Money',
            'category' => 'Service',
            'amount' => 0,
            'status' => 'pending',
            'payment_date' => now(),
        ]);
    }

    public function test_status_must_be_valid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Transaction::create([
            'customer_name' => 'Jane Doe',
            'phone' => '255754321098',
            'provider' => 'Bank',
            'category' => 'Invoice',
            'amount' => 50000,
            'status' => 'invalid_status',
            'payment_date' => now(),
        ]);
    }

    public function test_provider_and_category_accept_custom_values(): void
    {
        $transaction = Transaction::factory()->create([
            'provider' => 'Custom Provider',
            'category' => 'Custom Category',
        ]);

        $this->assertSame('Custom Provider', $transaction->provider);
        $this->assertSame('Custom Category', $transaction->category);
    }

    public function test_duplicate_transaction_id_is_rejected(): void
    {
        Transaction::create([
            'customer_name' => 'Asha Mwalimu',
            'phone' => '255712345678',
            'provider' => 'M-Pesa',
            'transaction_id' => 'MPESA-UNIQUE',
            'category' => 'School Fees',
            'amount' => 250000,
            'status' => 'completed',
            'payment_date' => now(),
        ]);

        $this->expectException(InvalidArgumentException::class);

        Transaction::create([
            'customer_name' => 'John Doe',
            'phone' => '255765432109',
            'provider' => 'Airtel Money',
            'transaction_id' => 'MPESA-UNIQUE',
            'category' => 'Service',
            'amount' => 25000,
            'status' => 'pending',
            'payment_date' => now(),
        ]);
    }

    public function test_transaction_can_be_soft_deleted(): void
    {
        $transaction = Transaction::create([
            'customer_name' => 'Grace Kivuyo',
            'phone' => '255712000111',
            'provider' => 'Cash',
            'category' => 'Rent',
            'amount' => 750000,
            'status' => 'completed',
            'payment_date' => now(),
        ]);

        $transaction->delete();

        $this->assertSoftDeleted($transaction);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }

    public function test_model_scopes_work_for_common_queries(): void
    {
        Transaction::factory()->create([
            'status' => 'completed',
            'provider' => 'M-Pesa',
            'payment_date' => now()->subDays(2),
            'reconciled' => true,
        ]);

        Transaction::factory()->create([
            'status' => 'pending',
            'provider' => 'Airtel Money',
            'payment_date' => now()->subDays(5),
            'reconciled' => false,
            'order_reference' => 'ORD-REF-1',
        ]);

        Transaction::factory()->create([
            'status' => 'failed',
            'provider' => 'Bank',
            'payment_date' => now()->subDays(10),
            'reconciled' => false,
        ]);

        Transaction::factory()->create([
            'status' => 'completed',
            'provider' => 'M-Pesa',
            'payment_date' => now()->subDays(12),
            'reconciled' => false,
            'order_reference' => 'ORD-REF-2',
        ]);

        $this->assertSame(2, Transaction::completed()->count());
        $this->assertSame(1, Transaction::pending()->count());
        $this->assertSame(1, Transaction::failed()->count());
        $this->assertSame(2, Transaction::provider('M-Pesa')->count());
        $this->assertSame(3, Transaction::unreconciled()->count());
        $this->assertSame(3, Transaction::dateRange(now()->subDays(10)->toDateString(), now()->toDateString())->count());
    }
}
