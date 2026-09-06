<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionExportTest extends TestCase
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

    public function test_authenticated_user_can_download_csv_with_expected_headers_and_data(): void
    {
        $transaction = $this->transactionFactory()->create([
            'transaction_id' => 'TX-EXPORT-001',
            'customer_name' => 'Asha Mwalimu',
            'phone' => '255712345678',
            'provider' => 'M-Pesa',
            'category' => 'School Fees',
            'amount' => '250000.00',
            'status' => 'completed',
            'payment_date' => '2026-09-01 10:30:00',
            'order_reference' => 'ORD-1001',
            'expected_amount' => '250000.00',
            'reconciled' => true,
            'notes' => 'Tuition payment received.',
        ]);

        $response = $this->get('/transactions/export');
        $csv = $response->streamedContent();

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('content-disposition', 'attachment; filename=miamala-transactions-'.now()->format('Y-m-d').'.csv');
        $rows = array_map('str_getcsv', array_filter(explode("\n", $csv), fn (string $row): bool => $row !== ''));

        $this->assertSame([
            'Transaction ID',
            'Customer',
            'Phone',
            'Provider',
            'Category',
            'Amount',
            'Status',
            'Payment Date',
            'Order Reference',
            'Expected Amount',
            'Reconciliation Status',
            'Reconciled',
            'Notes',
        ], $rows[0]);
        $this->assertSame([
            'TX-EXPORT-001',
            'Asha Mwalimu',
            '255712345678',
            'M-Pesa',
            'School Fees',
            '250000.00',
            'completed',
            '2026-09-01 10:30:00',
            'ORD-1001',
            '250000.00',
            'exact_match',
            'Yes',
            'Tuition payment received.',
        ], $rows[1]);
        $this->assertNotNull($transaction->fresh());
    }

    public function test_unauthenticated_user_cannot_access_export(): void
    {
        auth()->logout();

        $this->get('/transactions/export')->assertRedirect('/login');
    }

    public function test_export_respects_search_and_each_supported_filter(): void
    {
        $this->transactionFactory()->create([
            'customer_name' => 'Search Match',
            'phone' => '255700000001',
            'transaction_id' => 'TX-MATCH',
            'order_reference' => 'ORD-MATCH',
            'provider' => 'M-Pesa',
            'category' => 'School Fees',
            'status' => 'completed',
            'payment_date' => '2026-09-10 10:00:00',
        ]);
        $this->transactionFactory()->create([
            'customer_name' => 'Excluded Provider',
            'provider' => 'Bank',
            'category' => 'School Fees',
            'status' => 'completed',
            'payment_date' => '2026-09-10 10:00:00',
        ]);
        $this->transactionFactory()->create([
            'customer_name' => 'Excluded Status',
            'provider' => 'M-Pesa',
            'category' => 'School Fees',
            'status' => 'pending',
            'payment_date' => '2026-09-10 10:00:00',
        ]);
        $this->transactionFactory()->create([
            'customer_name' => 'Excluded Category',
            'provider' => 'M-Pesa',
            'category' => 'Rent',
            'status' => 'completed',
            'payment_date' => '2026-09-10 10:00:00',
        ]);
        $this->transactionFactory()->create([
            'customer_name' => 'Excluded Date',
            'provider' => 'M-Pesa',
            'category' => 'School Fees',
            'status' => 'completed',
            'payment_date' => '2026-08-10 10:00:00',
        ]);

        $response = $this->get('/transactions/export?search=TX-MATCH&provider=M-Pesa&status=completed&category=School+Fees&from=2026-09-01&to=2026-09-30');
        $csv = $response->streamedContent();

        $this->assertStringContainsString('TX-MATCH', $csv);
        $this->assertStringNotContainsString('Excluded Provider', $csv);
        $this->assertStringNotContainsString('Excluded Status', $csv);
        $this->assertStringNotContainsString('Excluded Category', $csv);
        $this->assertStringNotContainsString('Excluded Date', $csv);
    }

    public function test_export_contains_all_matching_transactions_beyond_pagination_limit(): void
    {
        $this->transactionFactory()->count(16)->create([
            'customer_name' => 'Exported Customer',
            'provider' => 'M-Pesa',
            'status' => 'completed',
        ]);

        $csv = $this->get('/transactions/export?provider=M-Pesa')->streamedContent();

        $this->assertSame(17, substr_count($csv, "\n"));
    }

    public function test_export_is_business_scoped_and_excludes_soft_deleted_transactions(): void
    {
        $otherUser = User::factory()->create();
        $this->transactionFactory()->create(['customer_name' => 'Visible Customer']);
        $deleted = $this->transactionFactory()->create(['customer_name' => 'Deleted Customer']);
        $deleted->delete();
        Transaction::factory()->for($otherUser->business)->create(['customer_name' => 'Other Business Customer']);

        $csv = $this->get('/transactions/export?search=Customer')->streamedContent();

        $this->assertStringContainsString('Visible Customer', $csv);
        $this->assertStringNotContainsString('Deleted Customer', $csv);
        $this->assertStringNotContainsString('Other Business Customer', $csv);
    }

    public function test_index_export_link_preserves_active_filters_without_pagination(): void
    {
        $response = $this->get('/transactions?search=Asha&provider=M-Pesa&status=completed&category=School+Fees&from=2026-09-01&to=2026-09-30&page=2');

        $response->assertOk();
        $response->assertSee('href="http://localhost:8000/transactions/export?', false);
        $response->assertSee('search=Asha', false);
        $response->assertSee('provider=M-Pesa', false);
        $response->assertSee('status=completed', false);
        $response->assertSee('category=School%20Fees', false);
        $response->assertSee('from=2026-09-01', false);
        $response->assertSee('to=2026-09-30', false);
        $this->assertStringNotContainsString('transactions/export?search=Asha&provider=M-Pesa&status=completed&category=School+Fees&from=2026-09-01&to=2026-09-30&page=2', $response->getContent());
    }

    public function test_formula_like_values_are_prefixed_for_spreadsheet_safety(): void
    {
        $this->transactionFactory()->create([
            'customer_name' => '=HYPERLINK("http://malicious.test")',
            'phone' => '+255700000000',
            'provider' => '@Provider',
            'category' => '-Category',
            'order_reference' => '=FORMULA',
            'notes' => '+Unsafe note',
        ]);

        $csv = $this->get('/transactions/export')->streamedContent();
        $rows = array_map('str_getcsv', array_filter(explode("\n", $csv), fn (string $row): bool => $row !== ''));
        $exported = $rows[1];

        $this->assertStringStartsWith("'=HYPERLINK(\"http://malicious.test\")", $exported[1]);
        $this->assertSame("'+255700000000", $exported[2]);
        $this->assertSame("'@Provider", $exported[3]);
        $this->assertSame("'-Category", $exported[4]);
        $this->assertSame("'=FORMULA", $exported[8]);
        $this->assertSame("'+Unsafe note", $exported[12]);
    }

    private function transactionFactory(): Factory
    {
        return Transaction::factory()->for($this->business);
    }
}
