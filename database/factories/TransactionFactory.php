<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $providers = ['M-Pesa', 'Airtel Money', 'Mixx by Yas', 'Bank', 'Cash', 'Other'];
        $statuses = ['pending', 'completed', 'failed', 'refunded'];
        $categories = ['Sale', 'School Fees', 'Rent', 'Donation', 'Invoice', 'Membership', 'Service', 'Other'];

        $amount = $this->faker->randomFloat(2, 500, 5000000);
        $status = $this->faker->randomElement($statuses);
        $provider = $this->faker->randomElement($providers);
        $reconciled = in_array($status, ['completed', 'refunded'], true) && $this->faker->boolean(70);

        return [
            'customer_name' => $this->faker->name(),
            'phone' => $this->faker->numerify('2557########'),
            'provider' => $provider,
            'transaction_id' => $this->faker->unique()->bothify('TX-###-????'),
            'category' => $this->faker->randomElement($categories),
            'amount' => $amount,
            'status' => $status,
            'payment_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'order_reference' => $this->faker->optional()->bothify('ORD-#######'),
            'expected_amount' => $this->faker->optional()->randomFloat(2, 500, 5000000),
            'reconciled' => $reconciled,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
