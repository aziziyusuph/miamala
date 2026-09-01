<?php

namespace App\Services;

use App\Models\Transaction;

class TransactionReconciliationService
{
    public const STATUS_UNRECONCILED = 'unreconciled';

    public const STATUS_EXACT_MATCH = 'exact_match';

    public const STATUS_UNDERPAID = 'underpaid';

    public const STATUS_OVERPAID = 'overpaid';

    public function calculate(Transaction $transaction): string
    {
        $expectedAmount = $transaction->expected_amount;
        $orderReference = trim((string) ($transaction->order_reference ?? ''));

        if ($expectedAmount === null || $expectedAmount === '' || $orderReference === '') {
            return self::STATUS_UNRECONCILED;
        }

        $difference = round((float) $transaction->amount - (float) $expectedAmount, 2);

        if (abs($difference) <= 0.01) {
            return self::STATUS_EXACT_MATCH;
        }

        if ($difference < 0) {
            return self::STATUS_UNDERPAID;
        }

        return self::STATUS_OVERPAID;
    }

    public function differenceFor(Transaction $transaction): ?float
    {
        if ($transaction->expected_amount === null || $transaction->expected_amount === '') {
            return null;
        }

        return round((float) $transaction->amount - (float) $transaction->expected_amount, 2);
    }
}
