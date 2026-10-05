<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DemoEnvironment;
use App\Services\TransactionReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DemoEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    private const DEMO_EMAIL = 'miamala-demo@example.test';

    private string $demoPassword;

    protected function setUp(): void
    {
        parent::setUp();

        $this->demoPassword = Str::random(40);
        config([
            'miamala.demo.email' => self::DEMO_EMAIL,
            'miamala.demo.password' => $this->demoPassword,
            'miamala.demo.business_name' => 'Miamala Demo Business',
        ]);
    }

    public function test_demo_configuration_is_available_without_committed_credentials(): void
    {
        $this->assertSame(self::DEMO_EMAIL, config('miamala.demo.email'));
        $this->assertSame($this->demoPassword, config('miamala.demo.password'));
        $this->assertSame('Miamala Demo Business', config('miamala.demo.business_name'));
    }

    public function test_configured_demo_user_and_business_are_recognized_as_demo(): void
    {
        $business = Business::factory()->create(['name' => 'Miamala Demo Business']);
        $user = User::factory()->create([
            'business_id' => $business->id,
            'email' => self::DEMO_EMAIL,
        ]);

        $this->assertTrue(app(DemoEnvironment::class)->isDemoUser($user));
    }

    public function test_other_user_on_demo_business_is_not_recognized_as_demo(): void
    {
        $business = Business::factory()->create(['name' => 'Miamala Demo Business']);
        $user = User::factory()->create(['business_id' => $business->id]);

        $this->assertFalse(app(DemoEnvironment::class)->isDemoUser($user));
    }

    public function test_configured_demo_email_on_another_business_is_not_recognized_as_demo(): void
    {
        $business = Business::factory()->create(['name' => 'A Different Business']);
        $user = User::factory()->create([
            'business_id' => $business->id,
            'email' => self::DEMO_EMAIL,
        ]);

        $this->assertFalse(app(DemoEnvironment::class)->isDemoUser($user));
    }

    public function test_different_user_and_business_are_not_recognized_as_demo(): void
    {
        $business = Business::factory()->create(['name' => 'A Different Business']);
        $user = User::factory()->create(['business_id' => $business->id]);

        $this->assertFalse(app(DemoEnvironment::class)->isDemoUser($user));
    }

    public function test_user_is_not_recognized_as_demo_without_configured_demo_email(): void
    {
        config(['miamala.demo.email' => '']);
        $business = Business::factory()->create(['name' => 'Miamala Demo Business']);
        $user = User::factory()->create([
            'business_id' => $business->id,
            'email' => self::DEMO_EMAIL,
        ]);

        $this->assertFalse(app(DemoEnvironment::class)->isDemoUser($user));
    }

    public function test_user_without_a_business_is_not_recognized_as_demo(): void
    {
        $user = User::factory()->create([
            'business_id' => null,
            'email' => self::DEMO_EMAIL,
        ]);

        $this->assertFalse(app(DemoEnvironment::class)->isDemoUser($user));
    }

    public function test_demo_command_creates_one_business_user_and_deterministic_dataset(): void
    {
        $this->assertSame(0, Artisan::call('miamala:seed-demo', ['--force' => true]));

        $business = Business::query()->where('name', 'Miamala Demo Business')->sole();
        $user = User::query()->where('email', self::DEMO_EMAIL)->sole();

        $this->assertSame('Demo User', $user->name);
        $this->assertSame($business->id, $user->business_id);
        $this->assertSame(24, $business->transactions()->count());
        $this->assertSame(1, Business::query()->where('name', 'Miamala Demo Business')->count());
        $this->assertSame(1, User::query()->where('email', self::DEMO_EMAIL)->count());
        $this->assertTrue(Hash::check($this->demoPassword, $user->password));
        $this->assertSame(24, $business->transactions()->where('notes', 'Simulated demo data. No real payment or customer.')->count());
    }

    public function test_running_demo_command_twice_does_not_duplicate_records(): void
    {
        $this->assertSame(0, Artisan::call('miamala:seed-demo', ['--force' => true]));
        $transactionIds = Transaction::query()->orderBy('id')->pluck('id')->all();

        $this->assertSame(0, Artisan::call('miamala:seed-demo', ['--force' => true]));

        $this->assertSame(1, Business::query()->where('name', 'Miamala Demo Business')->count());
        $this->assertSame(1, User::query()->where('email', self::DEMO_EMAIL)->count());
        $this->assertSame(24, Transaction::query()->count());
        $this->assertSame($transactionIds, Transaction::query()->orderBy('id')->pluck('id')->all());
    }

    public function test_demo_account_authenticates_and_can_view_dashboard_and_transactions(): void
    {
        $this->seedDemo();

        $this->get('/')
            ->assertOk()
            ->assertSee('Explore Demo')
            ->assertSee('href="'.route('login').'"', false);

        $this->get('/login')
            ->assertOk()
            ->assertSee('Demo access is available for evaluation.')
            ->assertDontSee($this->demoPassword);

        $login = $this->post('/login', [
            'email' => self::DEMO_EMAIL,
            'password' => $this->demoPassword,
        ]);

        $login->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Demo Environment')
            ->assertSee('simulated Miamala transactions')
            ->assertSee('M-Pesa');

        $this->get('/transactions')
            ->assertOk()
            ->assertSee('Demo Environment')
            ->assertSee('Asha Mushi (Demo)')
            ->assertDontSee('New transaction')
            ->assertDontSee('Mark as unreconciled');

        $demoTransaction = User::query()->where('email', self::DEMO_EMAIL)->sole()
            ->business->transactions()->where('transaction_id', 'MIAMALA-DEMO-0001')->firstOrFail();

        $this->get('/transactions/'.$demoTransaction->id)
            ->assertOk()
            ->assertSee('Asha Mushi (Demo)')
            ->assertSee('Exact match')
            ->assertDontSee('Edit transaction');

        $this->get('/transactions?search=Asha&provider=M-Pesa&status=completed')
            ->assertOk()
            ->assertSee('Asha Mushi (Demo)')
            ->assertDontSee('Daniel Kato (Demo)');

        $this->get('/transactions?page=2')
            ->assertOk()
            ->assertSee('MIAMALA-DEMO-');
    }

    public function test_demo_transactions_are_isolated_from_other_businesses_in_both_directions(): void
    {
        $this->seedDemo();
        $demoUser = User::query()->where('email', self::DEMO_EMAIL)->sole();
        $demoTransaction = $demoUser->business->transactions()->firstOrFail();

        $otherUser = User::factory()->create();
        $otherTransaction = Transaction::factory()->for($otherUser->business)->create([
            'customer_name' => 'Separate Business Customer',
        ]);

        $this->actingAs($demoUser)
            ->get('/transactions')
            ->assertOk()
            ->assertSee('Asha Mushi (Demo)')
            ->assertDontSee('Separate Business Customer');

        $this->actingAs($otherUser)
            ->get('/transactions')
            ->assertOk()
            ->assertSee('Separate Business Customer')
            ->assertDontSee('Asha Mushi (Demo)');

        $this->actingAs($otherUser)
            ->get('/transactions/'.$demoTransaction->id)
            ->assertNotFound();
    }

    public function test_demo_user_cannot_create_transactions(): void
    {
        $this->seedDemo();
        $demoUser = User::query()->where('email', self::DEMO_EMAIL)->sole();
        $transactionCount = $demoUser->business->transactions()->count();

        $this->actingAs($demoUser);

        $this->get('/transactions/create')->assertForbidden();

        $this->post('/transactions', [
            'customer_name' => 'New Demo Customer',
            'phone' => '255000000099',
            'provider' => 'Cash',
            'category' => 'Sale',
            'amount' => 100,
            'status' => 'completed',
            'payment_date' => now()->toDateString(),
        ])->assertForbidden();

        $this->assertSame($transactionCount, $demoUser->business->transactions()->count());
        $this->assertDatabaseMissing('transactions', ['customer_name' => 'New Demo Customer']);
    }

    public function test_demo_user_cannot_edit_or_update_transactions(): void
    {
        $this->seedDemo();
        $demoUser = User::query()->where('email', self::DEMO_EMAIL)->sole();
        $transaction = $demoUser->business->transactions()->where('transaction_id', 'MIAMALA-DEMO-0001')->firstOrFail();
        $original = $transaction->only(['customer_name', 'amount', 'status', 'reconciled', 'reconciliation_status']);

        $this->actingAs($demoUser)
            ->get('/transactions/'.$transaction->id.'/edit')
            ->assertForbidden();

        $this->put('/transactions/'.$transaction->id, [
            'customer_name' => 'Changed Demo Customer',
            'phone' => $transaction->phone,
            'provider' => $transaction->provider,
            'category' => $transaction->category,
            'amount' => 999999,
            'status' => 'refunded',
            'payment_date' => now()->toDateString(),
            'expected_amount' => 1,
            'order_reference' => 'CHANGED-REF',
        ])->assertForbidden();

        $this->assertSame($original, $transaction->fresh()->only(array_keys($original)));
    }

    public function test_demo_user_cannot_delete_transactions(): void
    {
        $this->seedDemo();
        $demoUser = User::query()->where('email', self::DEMO_EMAIL)->sole();
        $transaction = $demoUser->business->transactions()->where('transaction_id', 'MIAMALA-DEMO-0001')->firstOrFail();

        $this->actingAs($demoUser)
            ->delete('/transactions/'.$transaction->id)
            ->assertForbidden();

        $this->assertNotNull($transaction->fresh());
        $this->assertNull($transaction->fresh()->deleted_at);
    }

    public function test_demo_user_cannot_change_reconciliation_state(): void
    {
        $this->seedDemo();
        $demoUser = User::query()->where('email', self::DEMO_EMAIL)->sole();
        $transaction = $demoUser->business->transactions()->where('transaction_id', 'MIAMALA-DEMO-0002')->firstOrFail();
        $originalReconciled = $transaction->reconciled;
        $originalStatus = $transaction->reconciliation_status;

        $this->actingAs($demoUser)
            ->post('/transactions/'.$transaction->id.'/reconcile')
            ->assertForbidden();

        $transaction->refresh();
        $this->assertSame($originalReconciled, $transaction->reconciled);
        $this->assertSame($originalStatus, $transaction->reconciliation_status);
    }

    public function test_demo_user_can_export_transactions(): void
    {
        $this->seedDemo();
        $demoUser = User::query()->where('email', self::DEMO_EMAIL)->sole();

        $this->actingAs($demoUser);
        $csv = $this->get('/transactions/export');
        $csv->assertOk();

        $this->assertStringContainsString(
            'MIAMALA-DEMO-',
            $csv->streamedContent(),
        );
    }

    public function test_normal_business_users_retain_transaction_mutation_permissions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/transactions', [
            'customer_name' => 'Normal Business Customer',
            'phone' => '255000000099',
            'provider' => 'Cash',
            'category' => 'Sale',
            'amount' => 100,
            'status' => 'completed',
            'payment_date' => now()->toDateString(),
        ])->assertRedirect('/transactions');

        $transaction = $user->business->transactions()->where('customer_name', 'Normal Business Customer')->firstOrFail();

        $this->put('/transactions/'.$transaction->id, [
            'customer_name' => 'Updated Normal Customer',
            'phone' => '255000000099',
            'provider' => 'Cash',
            'category' => 'Sale',
            'amount' => 125,
            'status' => 'completed',
            'payment_date' => now()->toDateString(),
        ])->assertRedirect('/transactions');

        $transaction->refresh();
        $this->assertSame('Updated Normal Customer', $transaction->customer_name);
        $this->assertSame('125.00', $transaction->amount);

        $this->post('/transactions/'.$transaction->id.'/reconcile')
            ->assertRedirect('/transactions');
        $this->assertTrue($transaction->fresh()->reconciled);

        $this->delete('/transactions/'.$transaction->id)
            ->assertRedirect('/transactions');
        $this->assertSoftDeleted($transaction);
    }

    public function test_demo_data_contains_all_supported_reconciliation_scenarios_and_statuses(): void
    {
        $this->seedDemo();

        $business = Business::query()->where('name', 'Miamala Demo Business')->sole();
        $transactions = $business->transactions();

        foreach (config('transactions.statuses') as $status) {
            $this->assertGreaterThan(0, (clone $transactions)->where('status', $status)->count());
        }

        foreach ([
            TransactionReconciliationService::STATUS_EXACT_MATCH,
            TransactionReconciliationService::STATUS_UNDERPAID,
            TransactionReconciliationService::STATUS_OVERPAID,
            TransactionReconciliationService::STATUS_UNRECONCILED,
        ] as $status) {
            $this->assertGreaterThan(0, (clone $transactions)->where('reconciliation_status', $status)->count());
        }

        foreach (config('transactions.providers') as $provider) {
            $this->assertGreaterThan(0, (clone $transactions)->where('provider', $provider)->count());
        }

        $this->assertSame('exact_match', app(TransactionReconciliationService::class)->calculate(
            $business->transactions()->where('transaction_id', 'MIAMALA-DEMO-0001')->firstOrFail(),
        ));
        $this->assertSame('underpaid', $business->transactions()->where('transaction_id', 'MIAMALA-DEMO-0002')->firstOrFail()->reconciliation_status);
        $this->assertSame('overpaid', $business->transactions()->where('transaction_id', 'MIAMALA-DEMO-0003')->firstOrFail()->reconciliation_status);
        $this->assertSame('unreconciled', $business->transactions()->where('transaction_id', 'MIAMALA-DEMO-0004')->firstOrFail()->reconciliation_status);
    }

    public function test_demo_banner_is_not_shown_to_a_normal_business_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Demo Environment')
            ->assertDontSee('simulated Miamala transactions');
    }

    public function test_command_requires_configured_email_and_password(): void
    {
        config([
            'miamala.demo.email' => '',
            'miamala.demo.password' => '',
        ]);

        $this->assertSame(1, Artisan::call('miamala:seed-demo', ['--force' => true]));
        $this->assertSame(0, Business::query()->count());
        $this->assertSame(0, User::query()->count());
    }

    public function test_demo_command_refuses_to_reuse_a_business_with_another_user(): void
    {
        $business = Business::factory()->create(['name' => 'Miamala Demo Business']);
        $existingUser = User::factory()->create(['business_id' => $business->id]);

        $this->assertSame(1, Artisan::call('miamala:seed-demo', ['--force' => true]));

        $this->assertSame(1, Business::query()->count());
        $this->assertSame(1, User::query()->count());
        $this->assertSame($existingUser->id, $business->users()->sole()->id);
        $this->assertSame(0, $business->transactions()->count());
    }

    public function test_production_demo_initialization_requires_explicit_force(): void
    {
        $this->app['env'] = 'production';

        $this->assertSame(1, Artisan::call('miamala:seed-demo'));

        $this->assertSame(0, Business::query()->count());
        $this->assertSame(0, User::query()->count());
        $this->assertSame(0, Transaction::query()->count());
    }

    private function seedDemo(): void
    {
        $this->assertSame(0, Artisan::call('miamala:seed-demo', ['--force' => true]));
    }
}
