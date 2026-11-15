<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SeedDemoCommand extends Command
{
    protected $signature = 'miamala:seed-demo
                            {--force : Confirm that the configured database is the intended demo target}';

    protected $description = 'Create or refresh the isolated Miamala demonstration account and sample transactions';

    private const DEMO_NOTE = 'Simulated demo data. No real payment or customer.';

    public function handle(): int
    {
        $email = trim((string) config('miamala.demo.email'));
        $password = (string) config('miamala.demo.password');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || trim($password) === '') {
            $this->error('Set MIAMALA_DEMO_EMAIL and MIAMALA_DEMO_PASSWORD before initializing demo data.');

            return self::FAILURE;
        }

        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Production demo initialization requires --force after verifying the configured database target.');

            return self::FAILURE;
        }

        $connection = DB::connection();
        $this->line('Target database: '.config('database.default').' / '.$connection->getDatabaseName());

        if (! $this->option('force') && ! $this->confirm('Initialize or refresh the demo account and transactions in this database?')) {
            $this->warn('Demo initialization cancelled.');

            return self::FAILURE;
        }

        try {
            $count = DB::transaction(function () use ($email, $password): int {
                $business = $this->demoBusiness();
                $this->ensureBusinessHasNoOtherUsers($business, $email);
                $this->ensureBusinessContainsOnlyDemoTransactions($business);
                $user = $this->demoUser($business, $email, $password);

                foreach ($this->transactions() as $attributes) {
                    $transaction = Transaction::query()
                        ->withTrashed()
                        ->where('transaction_id', $attributes['transaction_id'])
                        ->first();

                    if ($transaction !== null) {
                        if ($transaction->business_id !== $business->id || $transaction->notes !== self::DEMO_NOTE) {
                            throw new RuntimeException('A reserved demo transaction reference belongs to a different or non-demo record.');
                        }
                        if ($transaction->trashed()) {
                            $transaction->restore();
                        }
                    } else {
                        $transaction = new Transaction;
                    }

                    $transaction->fill($attributes + ['business_id' => $business->id]);
                    $transaction->save();
                }

                if ($user->business_id !== $business->id) {
                    throw new RuntimeException('The configured demo user is not attached to the demo business.');
                }

                return count($this->transactions());
            });
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Demo environment initialized with {$count} simulated transactions.");

        return self::SUCCESS;
    }

    private function demoBusiness(): Business
    {
        $name = trim((string) config('miamala.demo.business_name'));

        if ($name === '') {
            throw new RuntimeException('The demo business name is not configured.');
        }

        $businesses = Business::query()->where('name', $name)->get();

        if ($businesses->count() > 1) {
            throw new RuntimeException('More than one business uses the configured demo business name; refusing to choose one.');
        }

        return $businesses->first() ?? Business::query()->create(['name' => $name]);
    }

    private function ensureBusinessHasNoOtherUsers(Business $business, string $email): void
    {
        if ($business->users()->where('email', '!=', $email)->exists()) {
            throw new RuntimeException('The configured demo business already has another user; refusing to share the demo account.');
        }
    }

    private function ensureBusinessContainsOnlyDemoTransactions(Business $business): void
    {
        $references = collect($this->transactions())->pluck('transaction_id');

        $unexpectedTransactions = $business->transactions()
            ->withTrashed()
            ->where(fn ($query) => $query->whereNotIn('transaction_id', $references)->orWhereNull('transaction_id'))
            ->exists();

        if ($unexpectedTransactions) {
            throw new RuntimeException('The configured demo business contains unrecognized transactions; refusing to modify it.');
        }

        $unmarkedTransactions = $business->transactions()
            ->withTrashed()
            ->whereIn('transaction_id', $references)
            ->where('notes', '!=', self::DEMO_NOTE)
            ->exists();

        if ($unmarkedTransactions) {
            throw new RuntimeException('A reserved demo transaction reference is already used by a non-demo record.');
        }
    }

    private function demoUser(Business $business, string $email, string $password): User
    {
        $user = User::query()->where('email', $email)->first();

        if ($user !== null && $user->business_id !== $business->id) {
            throw new RuntimeException('The configured demo email is already assigned to a different business.');
        }

        $user ??= new User;
        $user->fill([
            'name' => 'Demo User',
            'email' => $email,
            'business_id' => $business->id,
        ]);
        $user->password = Hash::make($password);
        $user->save();

        return $user;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function transactions(): array
    {
        $rows = [
            ['Asha Mushi', 'M-Pesa', 'School Fees', 50000, 50000, 'completed', true],
            ['Daniel Kato', 'Airtel Money', 'Sale', 80000, 100000, 'completed', false],
            ['Neema Joseph', 'Mixx by Yas', 'Invoice', 80000, 75000, 'completed', true],
            ['Baraka Hassan', 'Bank', 'Service', 42000, null, 'pending', false],
            ['Grace Peter', 'Cash', 'Donation', 25000, 25000, 'pending', false],
            ['Rehema Mtei', 'Other', 'Membership', 120000, 120000, 'failed', false],
            ['Juma Salim', 'M-Pesa', 'Rent', 350000, 350000, 'refunded', true],
            ['Zawadi Paulo', 'Airtel Money', 'Sale', 67500, 70000, 'completed', false],
            ['Imani Nyerere', 'Mixx by Yas', 'School Fees', 150000, 150000, 'completed', true],
            ['Peter Mwita', 'Bank', 'Invoice', 225000, 200000, 'completed', false],
            ['Lulu Michael', 'Cash', 'Service', 18500, null, 'pending', false],
            ['Hassan Omari', 'Other', 'Donation', 90000, 90000, 'completed', true],
            ['Upendo Charles', 'M-Pesa', 'Membership', 48000, 50000, 'completed', false],
            ['Amani Said', 'Airtel Money', 'Rent', 400000, 400000, 'completed', true],
            ['Rehema Mtei', 'Mixx by Yas', 'Sale', 73500, 70000, 'completed', false],
            ['Daniel Kato', 'Bank', 'School Fees', 300000, null, 'pending', false],
            ['Zawadi Paulo', 'Cash', 'Invoice', 112000, 112000, 'completed', true],
            ['Juma Salim', 'Other', 'Service', 56000, 60000, 'failed', false],
            ['Asha Mushi', 'Airtel Money', 'Donation', 15000, null, 'pending', false],
            ['Baraka Hassan', 'M-Pesa', 'Sale', 98000, 98000, 'completed', true],
            ['Neema Joseph', 'Bank', 'Membership', 210000, 200000, 'completed', false],
            ['Grace Peter', 'Mixx by Yas', 'Rent', 275000, 275000, 'completed', true],
            ['Imani Nyerere', 'Cash', 'School Fees', 62500, 65000, 'completed', false],
            ['Peter Mwita', 'Other', 'Invoice', 132000, 132000, 'completed', true],
        ];

        return collect($rows)->values()->map(function (array $row, int $index): array {
            [$customer, $provider, $category, $amount, $expected, $status, $reconciled] = $row;
            $sequence = $index + 1;

            return [
                'customer_name' => $customer.' (Demo)',
                'phone' => sprintf('255000%06d', $sequence),
                'provider' => $provider,
                'transaction_id' => sprintf('MIAMALA-DEMO-%04d', $sequence),
                'category' => $category,
                'amount' => $amount,
                'status' => $status,
                'payment_date' => now()->startOfDay()->subDays(($index * 3) % 45)->setTime(9 + ($index % 8), ($index * 11) % 60),
                'order_reference' => sprintf('DEMO-ORD-%04d', $sequence),
                'expected_amount' => $expected,
                'reconciled' => $reconciled,
                'notes' => self::DEMO_NOTE,
            ];
        })->all();
    }
}
