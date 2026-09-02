<?php

namespace App\Services;

use App\Models\Transaction;

class TransactionReconciliationService
{
    private const MINOR_UNITS_PER_MAJOR_UNIT = 100;

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

        $difference = $this->toMinorUnits($transaction->amount) - $this->toMinorUnits($expectedAmount);

        if (abs($difference) <= 1) {
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

        $difference = $this->toMinorUnits($transaction->amount) - $this->toMinorUnits($transaction->expected_amount);
        $sign = $difference < 0 ? '-' : '';
        $absoluteDifference = abs($difference);
        $formattedDifference = sprintf(
            '%s%d.%02d',
            $sign,
            intdiv($absoluteDifference, self::MINOR_UNITS_PER_MAJOR_UNIT),
            $absoluteDifference % self::MINOR_UNITS_PER_MAJOR_UNIT,
        );

        return (float) $formattedDifference;
    }

    public function toMinorUnits(mixed $amount): int
    {
        $value = trim((string) $amount);
        $isNegative = str_starts_with($value, '-');
        $value = ltrim($value, '+-');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $fraction = str_pad($fraction, 3, '0');
        $minorUnits = ((int) $whole * self::MINOR_UNITS_PER_MAJOR_UNIT) + (int) substr($fraction, 0, 2);

        if ((int) $fraction[2] >= 5) {
            $minorUnits++;
        }

        return $isNegative ? -$minorUnits : $minorUnits;
    }
}
