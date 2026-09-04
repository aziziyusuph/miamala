<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Validation\ValidationException;

class TransactionLifecycleService
{
    /**
     * @var array<string, array<int, string>>
     */
    private const ALLOWED_TRANSITIONS = [
        'pending' => ['completed', 'failed'],
        'failed' => ['pending'],
        'completed' => ['refunded'],
        'refunded' => [],
    ];

    public function transition(Transaction $transaction, string $targetStatus): Transaction
    {
        if ($transaction->isDirty('business_id')) {
            throw ValidationException::withMessages([
                'business_id' => 'A transaction cannot be moved to another business.',
            ]);
        }

        $currentStatus = strtolower(trim((string) $transaction->status));
        $targetStatus = strtolower(trim($targetStatus));

        if (! in_array($targetStatus, config('transactions.statuses'), true)) {
            throw ValidationException::withMessages([
                'status' => 'Status must be a valid transaction status.',
            ]);
        }

        if ($currentStatus === $targetStatus) {
            $transaction->save();

            return $transaction;
        }

        if (! in_array($targetStatus, self::ALLOWED_TRANSITIONS[$currentStatus] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "A transaction cannot transition from {$currentStatus} to {$targetStatus}.",
            ]);
        }

        $transaction->status = $targetStatus;
        $transaction->save();

        return $transaction;
    }
}
