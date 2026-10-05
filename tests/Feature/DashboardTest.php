<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_business_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertViewIs('dashboard')
            ->assertSee('Overview')
            ->assertSee('Payment activity and reconciliation overview for your business.');
    }

    public function test_dashboard_metrics_and_lists_only_include_the_users_business(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Transaction::factory()->for($user->business)->create([
            'customer_name' => 'Exact Match Customer',
            'provider' => 'Bank',
            'amount' => 120,
            'expected_amount' => 120,
            'order_reference' => 'EXACT-1',
            'status' => 'completed',
            'reconciled' => true,
            'payment_date' => now()->subHours(2),
        ]);
        Transaction::factory()->for($user->business)->create([
            'customer_name' => 'Underpaid Customer',
            'provider' => 'M-Pesa',
            'amount' => 75,
            'expected_amount' => 100,
            'order_reference' => 'UNDER-1',
            'status' => 'completed',
            'reconciled' => false,
            'payment_date' => now()->subHour(),
        ]);
        Transaction::factory()->for($user->business)->create([
            'customer_name' => 'Unreconciled Customer',
            'provider' => 'M-Pesa',
            'amount' => 25,
            'expected_amount' => null,
            'order_reference' => null,
            'status' => 'pending',
            'reconciled' => false,
            'payment_date' => now(),
        ]);
        Transaction::factory()->for($otherUser->business)->create([
            'customer_name' => 'Private Other Business Customer',
            'provider' => 'Cash',
            'amount' => 9999,
            'expected_amount' => 9999,
            'order_reference' => 'OTHER-1',
            'status' => 'completed',
            'reconciled' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()
            ->assertViewHas('totalTransactions', 3)
            ->assertViewHas('completedAmount', 195)
            ->assertViewHas('pendingTransactions', 1)
            ->assertViewHas('needsReview', 2)
            ->assertViewHas('reconciledTransactions', 1)
            ->assertViewHas('statusCounts', [
                'pending' => 1,
                'completed' => 2,
                'failed' => 0,
                'refunded' => 0,
            ])
            ->assertViewHas('providerCounts', [
                'Bank' => 1,
                'M-Pesa' => 2,
            ])
            ->assertViewHas('reconciliationCounts', [
                TransactionReconciliationService::STATUS_EXACT_MATCH => 1,
                TransactionReconciliationService::STATUS_UNDERPAID => 1,
                TransactionReconciliationService::STATUS_OVERPAID => 0,
                TransactionReconciliationService::STATUS_UNRECONCILED => 1,
            ])
            ->assertSee('Exact Match Customer')
            ->assertSee('Underpaid Customer')
            ->assertSee('Unreconciled Customer')
            ->assertDontSee('Private Other Business Customer')
            ->assertDontSee('9,999.00');
    }

    public function test_user_without_a_business_cannot_access_any_business_dashboard_data(): void
    {
        $otherBusiness = Business::factory()->create();
        Transaction::factory()->for($otherBusiness)->create([
            'customer_name' => 'Private Other Business Customer',
        ]);
        $userWithoutBusiness = User::factory()->create(['business_id' => null]);

        $this->actingAs($userWithoutBusiness)
            ->get('/dashboard')
            ->assertNotFound()
            ->assertDontSee('Private Other Business Customer');
    }

    public function test_dashboard_displays_an_empty_state_for_a_business_without_transactions(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()
            ->assertSee('No transactions yet')
            ->assertSee('Transactions will appear here once your organization records them.')
            ->assertSee('Add your first transaction')
            ->assertSee('Transaction status')
            ->assertSee('Exact match')
            ->assertSee('No payment channels recorded yet')
            ->assertSee('No recent transactions to show.')
            ->assertViewHas('totalTransactions', 0)
            ->assertViewHas('completedAmount', 0)
            ->assertViewHas('pendingTransactions', 0)
            ->assertViewHas('needsReview', 0);
    }
}
