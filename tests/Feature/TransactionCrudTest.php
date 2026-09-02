<?php

namespace Tests\Feature;

use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function transactionData(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Asha Mwalimu',
            'phone' => '255712345678',
            'provider' => 'M-Pesa',
            'transaction_id' => 'TX-1001',
            'category' => 'School Fees',
            'amount' => 250000,
            'status' => 'completed',
            'payment_date' => '2026-08-01',
            'order_reference' => 'ORD-1001',
            'expected_amount' => 250000,
            'reconciled' => true,
            'notes' => 'Tuition payment received.',
        ], $overrides);
    }

    public function test_transaction_index_page_loads(): void
    {
        Transaction::factory()->count(3)->create();

        $response = $this->get('/transactions');

        $response->assertOk();
        $response->assertViewIs('transactions.index');
    }

    public function test_transactions_paginate(): void
    {
        Transaction::factory()->count(20)->create();

        $response = $this->get('/transactions?page=1');

        $response->assertOk();
        $response->assertSee('page=2');
        $response->assertSee('Transactions');
    }

    public function test_search_by_customer_name_works(): void
    {
        Transaction::factory()->create(['customer_name' => 'Alice Johnson']);
        Transaction::factory()->create(['customer_name' => 'Bob Smith']);

        $response = $this->get('/transactions?search=Alice');

        $response->assertOk();
        $response->assertSee('Alice Johnson');
        $response->assertDontSee('Bob Smith');
    }

    public function test_search_by_phone_works(): void
    {
        Transaction::factory()->create(['phone' => '255700000001']);
        Transaction::factory()->create(['phone' => '255700000002']);

        $response = $this->get('/transactions?search=255700000001');

        $response->assertOk();
        $response->assertSee('255700000001');
        $response->assertDontSee('255700000002');
    }

    public function test_search_by_transaction_id_works(): void
    {
        Transaction::factory()->create(['transaction_id' => 'TX-SEARCH-001']);
        Transaction::factory()->create(['transaction_id' => 'TX-SEARCH-002']);

        $response = $this->get('/transactions?search=TX-SEARCH-001');

        $response->assertOk();
        $response->assertSee('TX-SEARCH-001');
        $response->assertDontSee('TX-SEARCH-002');
    }

    public function test_search_by_order_reference_works(): void
    {
        Transaction::factory()->create(['order_reference' => 'ORD-REF-100']);
        Transaction::factory()->create(['order_reference' => 'ORD-REF-200']);

        $response = $this->get('/transactions?search=ORD-REF-100');

        $response->assertOk();
        $response->assertSee('ORD-REF-100');
        $response->assertDontSee('ORD-REF-200');
    }

    public function test_provider_filter_works(): void
    {
        Transaction::factory()->create([
            'customer_name' => 'Provider Match Customer',
            'provider' => 'M-Pesa',
        ]);
        Transaction::factory()->create([
            'customer_name' => 'Provider Excluded Customer',
            'provider' => 'Bank',
        ]);

        $response = $this->get('/transactions?provider=M-Pesa');

        $response->assertOk();
        $response->assertSee('Provider Match Customer');
        $response->assertDontSee('Provider Excluded Customer');
    }

    public function test_status_filter_works(): void
    {
        Transaction::factory()->create([
            'customer_name' => 'Pending Row Customer',
            'status' => 'pending',
        ]);
        Transaction::factory()->create([
            'customer_name' => 'Completed Row Customer',
            'status' => 'completed',
        ]);

        $response = $this->get('/transactions?status=pending');

        $response->assertOk();
        $response->assertSee('Pending Row Customer');
        $response->assertDontSee('Completed Row Customer');
    }

    public function test_category_filter_works(): void
    {
        Transaction::factory()->create([
            'customer_name' => 'Category Match Customer',
            'category' => 'School Fees',
        ]);
        Transaction::factory()->create([
            'customer_name' => 'Category Excluded Customer',
            'category' => 'Rent',
        ]);

        $response = $this->get('/transactions?category=School+Fees');

        $response->assertOk();
        $response->assertSee('Category Match Customer');
        $response->assertDontSee('Category Excluded Customer');
    }

    public function test_payment_date_filter_works(): void
    {
        Transaction::factory()->create(['payment_date' => '2026-08-01 10:00:00']);
        Transaction::factory()->create(['payment_date' => '2026-08-20 10:00:00']);

        $response = $this->get('/transactions?from=2026-08-01&to=2026-08-10');

        $response->assertOk();
        $response->assertSee('2026-08-01');
        $response->assertDontSee('2026-08-20');
    }

    public function test_combined_search_and_filters_work_together(): void
    {
        Transaction::factory()->create([
            'customer_name' => 'Zawadi Moyo',
            'provider' => 'M-Pesa',
            'status' => 'completed',
            'category' => 'School Fees',
            'payment_date' => '2026-08-15 09:00:00',
        ]);

        Transaction::factory()->create([
            'customer_name' => 'John Doe',
            'provider' => 'Bank',
            'status' => 'pending',
            'category' => 'Rent',
            'payment_date' => '2026-08-20 09:00:00',
        ]);

        $response = $this->get('/transactions?search=Zawadi&provider=M-Pesa&status=completed&category=School+Fees&from=2026-08-01&to=2026-08-31');

        $response->assertOk();
        $response->assertSee('Zawadi Moyo');
        $response->assertDontSee('John Doe');
    }

    public function test_create_form_loads(): void
    {
        $response = $this->get('/transactions/create');

        $response->assertOk();
        $response->assertViewIs('transactions.create');
    }

    public function test_transaction_can_be_created(): void
    {
        $response = $this->post('/transactions', $this->transactionData());

        $response->assertRedirect('/transactions');
        $this->assertDatabaseHas('transactions', [
            'customer_name' => 'Asha Mwalimu',
            'provider' => 'M-Pesa',
            'transaction_id' => 'TX-1001',
        ]);
    }

    public function test_invalid_transaction_is_rejected(): void
    {
        $response = $this->from('/transactions/create')->post('/transactions', [
            'customer_name' => '',
            'phone' => '255712345678',
            'provider' => 'M-Pesa',
            'transaction_id' => 'TX-INVALID',
            'category' => 'School Fees',
            'amount' => 0,
            'status' => 'completed',
            'payment_date' => '2026-08-01',
        ]);

        $response->assertSessionHasErrors(['customer_name', 'amount']);
        $response->assertRedirect('/transactions/create');
    }

    public function test_duplicate_transaction_id_is_rejected(): void
    {
        Transaction::factory()->create(['transaction_id' => 'TX-DUPLICATE']);

        $response = $this->from('/transactions/create')->post('/transactions', $this->transactionData([
            'transaction_id' => 'TX-DUPLICATE',
        ]));

        $response->assertSessionHasErrors('transaction_id');
        $response->assertRedirect('/transactions/create');
    }

    public function test_edit_form_loads(): void
    {
        $transaction = Transaction::factory()->create();

        $response = $this->get('/transactions/'.$transaction->id.'/edit');

        $response->assertOk();
        $response->assertViewIs('transactions.edit');
    }

    public function test_transaction_detail_page_loads(): void
    {
        $transaction = Transaction::factory()->create([
            'customer_name' => 'Detail Customer',
            'transaction_id' => 'TX-DETAIL-001',
        ]);

        $response = $this->get('/transactions/'.$transaction->id);

        $response->assertOk();
        $response->assertViewIs('transactions.show');
        $response->assertSee('Detail Customer');
        $response->assertSee('TX-DETAIL-001');
    }

    public function test_transaction_can_be_updated(): void
    {
        $transaction = Transaction::factory()->create([
            'customer_name' => 'Old Customer',
            'status' => 'pending',
        ]);

        $response = $this->put('/transactions/'.$transaction->id, $this->transactionData([
            'customer_name' => 'Updated Customer',
            'transaction_id' => 'TX-UPDATED',
            'status' => 'completed',
        ]));

        $response->assertRedirect('/transactions');
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'customer_name' => 'Updated Customer',
            'status' => 'completed',
            'transaction_id' => 'TX-UPDATED',
        ]);
    }

    public function test_transaction_can_be_soft_deleted(): void
    {
        $transaction = Transaction::factory()->create();

        $response = $this->delete('/transactions/'.$transaction->id);

        $response->assertRedirect('/transactions');
        $this->assertSoftDeleted($transaction);
    }

    public function test_deleted_transaction_does_not_appear_in_listing(): void
    {
        $visible = Transaction::factory()->create(['customer_name' => 'Visible Customer']);
        $deleted = Transaction::factory()->create(['customer_name' => 'Deleted Customer']);
        $deleted->delete();

        $response = $this->get('/transactions');

        $response->assertOk();
        $response->assertSee('Visible Customer');
        $response->assertDontSee('Deleted Customer');
    }
}
