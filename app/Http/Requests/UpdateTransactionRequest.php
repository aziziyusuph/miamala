<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $transaction = $this->route('transaction');

        return [
            'customer_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'provider' => ['required', 'string', 'max:50'],
            'transaction_id' => ['nullable', 'string', 'max:100', Rule::unique('transactions', 'transaction_id')->ignore($transaction?->id)],
            'category' => ['required', 'string', 'max:80'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'status' => ['required', 'string', 'in:pending,completed,failed,refunded'],
            'payment_date' => ['required', 'date'],
            'order_reference' => ['nullable', 'string', 'max:100'],
            'expected_amount' => ['nullable', 'numeric', 'gt:0'],
            'reconciled' => ['nullable', 'boolean'],
            'reconciliation_status' => ['nullable', 'string', 'in:unreconciled,exact_match,underpaid,overpaid'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
