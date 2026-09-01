<?php

namespace App\Models;

use App\Services\TransactionReconciliationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUSES = ['pending', 'completed', 'failed', 'refunded'];

    protected $table = 'transactions';

    protected $fillable = [
        'customer_name',
        'phone',
        'provider',
        'transaction_id',
        'category',
        'amount',
        'status',
        'payment_date',
        'order_reference',
        'expected_amount',
        'reconciled',
        'reconciliation_status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'reconciled' => 'boolean',
    ];

    protected $appends = ['difference'];

    protected static function booted(): void
    {
        static::saving(function (self $transaction) {
            $transaction->validateBusinessRules();
            $transaction->reconciliation_status = app(TransactionReconciliationService::class)->calculate($transaction);
        });
    }

    protected function validateBusinessRules(): void
    {
        $this->customer_name = trim((string) ($this->customer_name ?? ''));
        $this->phone = trim((string) ($this->phone ?? ''));
        $this->provider = trim((string) ($this->provider ?? ''));
        $this->category = trim((string) ($this->category ?? ''));
        $this->transaction_id = $this->transaction_id !== null ? trim((string) $this->transaction_id) : null;
        $this->status = strtolower(trim((string) ($this->status ?? 'pending')));

        if ($this->customer_name === '') {
            throw new InvalidArgumentException('Customer name is required.');
        }

        if ($this->phone === '') {
            throw new InvalidArgumentException('Phone number is required.');
        }

        if ($this->provider === '') {
            throw new InvalidArgumentException('Provider is required.');
        }

        if ($this->category === '') {
            throw new InvalidArgumentException('Category is required.');
        }

        if ($this->payment_date === null || $this->payment_date === '') {
            throw new InvalidArgumentException('Payment date is required.');
        }

        if ((float) $this->amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        if (! in_array($this->status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Status must be a valid transaction status.');
        }

        if ($this->expected_amount !== null && (float) $this->expected_amount <= 0) {
            throw new InvalidArgumentException('Expected amount must be greater than zero when provided.');
        }

        if ($this->transaction_id !== null && $this->transaction_id !== '') {
            $query = self::query()->where('transaction_id', $this->transaction_id);

            if ($this->exists) {
                $query->whereKeyNot($this->getKey());
            }

            if ($query->exists()) {
                throw new InvalidArgumentException('A transaction with this transaction ID already exists.');
            }
        }
    }

    public function getDifferenceAttribute(): ?float
    {
        if ($this->expected_amount === null || $this->expected_amount === '') {
            return null;
        }

        return round((float) $this->amount - (float) $this->expected_amount, 2);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeUnreconciled($query)
    {
        return $query->where('reconciled', false);
    }

    public function scopeProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('payment_date', [$from, $to]);
    }
}
