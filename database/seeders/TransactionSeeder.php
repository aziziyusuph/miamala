<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Transaction;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(Business $business): void
    {
        $sampleTransactions = [
            [
                'customer_name' => 'Aisha Mchome',
                'phone' => '255712345678',
                'provider' => 'M-Pesa',
                'transaction_id' => 'MP-1001',
                'category' => 'School Fees',
                'amount' => 250000,
                'status' => 'completed',
                'payment_date' => now()->subDay(),
                'order_reference' => 'ORD-1001',
                'expected_amount' => 250000,
                'reconciled' => true,
                'notes' => 'Tuition payment for Q1.',
            ],
            [
                'customer_name' => 'Bakari Juma',
                'phone' => '255765432109',
                'provider' => 'Airtel Money',
                'transaction_id' => 'AIR-2045',
                'category' => 'Service',
                'amount' => 185000,
                'status' => 'pending',
                'payment_date' => now()->subHours(4),
                'order_reference' => 'ORD-2045',
                'expected_amount' => 185000,
                'reconciled' => false,
                'notes' => 'Awaiting confirmation from customer.',
            ],
            [
                'customer_name' => 'Grace Kivuyo',
                'phone' => '255754321098',
                'provider' => 'Mixx by Yas',
                'transaction_id' => 'MIX-8742',
                'category' => 'Rent',
                'amount' => 750000,
                'status' => 'completed',
                'payment_date' => now()->subDays(3),
                'order_reference' => 'ORD-8742',
                'expected_amount' => 750000,
                'reconciled' => true,
                'notes' => 'Monthly rent settlement.',
            ],
            [
                'customer_name' => 'Joseph Macha',
                'phone' => '255743210987',
                'provider' => 'Bank',
                'transaction_id' => 'BANK-3321',
                'category' => 'Invoice',
                'amount' => 90500,
                'status' => 'failed',
                'payment_date' => now()->subDays(2),
                'order_reference' => 'INV-3321',
                'expected_amount' => 90500,
                'reconciled' => false,
                'notes' => 'Bank rejected transaction due to failed validation.',
            ],
            [
                'customer_name' => 'Salma Rashid',
                'phone' => '255712222333',
                'provider' => 'Cash',
                'transaction_id' => 'CASH-909',
                'category' => 'Donation',
                'amount' => 120000,
                'status' => 'refunded',
                'payment_date' => now()->subDays(6),
                'order_reference' => 'DON-909',
                'expected_amount' => 120000,
                'reconciled' => true,
                'notes' => 'Refund processed after donor cancellation.',
            ],
            [
                'customer_name' => 'Musa Nyoni',
                'phone' => '255788990011',
                'provider' => 'Other',
                'transaction_id' => 'OTH-1982',
                'category' => 'Membership',
                'amount' => 300000,
                'status' => 'pending',
                'payment_date' => now()->subHours(12),
                'order_reference' => 'MEM-1982',
                'expected_amount' => 300000,
                'reconciled' => false,
                'notes' => 'Membership fee still awaiting final approval.',
            ],
        ];

        foreach ($sampleTransactions as $transaction) {
            Transaction::query()->firstOrCreate(
                ['transaction_id' => $transaction['transaction_id']],
                array_merge($transaction, ['business_id' => $business->id])
            );
        }

        Transaction::factory()->count(25)->create([
            'business_id' => $business->id,
            'provider' => fn () => fake()->randomElement(config('transactions.providers')),
            'status' => fn () => fake()->randomElement(config('transactions.statuses')),
            'category' => fn () => fake()->randomElement(config('transactions.categories')),
            'reconciled' => fn () => fake()->boolean(60),
        ]);
    }
}
