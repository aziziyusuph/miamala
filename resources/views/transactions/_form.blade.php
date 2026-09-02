@php
    $transaction = $transaction ?? new \App\Models\Transaction();
@endphp

<div>
    @if ($errors->any())
        <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #fee2e2; color: #991b1b; border-radius: 6px;">
            <strong>Please fix the following errors:</strong>
            <ul style="margin: 0.5rem 0 0 1.2rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
        <div>
            <label for="customer_name">Customer name</label>
            <input id="customer_name" name="customer_name" type="text" value="{{ old('customer_name', $transaction->customer_name) }}" maxlength="120" required>
        </div>

        <div>
            <label for="phone">Phone</label>
            <input id="phone" name="phone" type="text" value="{{ old('phone', $transaction->phone) }}" maxlength="30" required>
        </div>

        <div>
            <label for="provider">Provider</label>
            <select id="provider" name="provider" required>
                <option value="">Select provider</option>
                @foreach (config('transactions.providers') as $provider)
                    <option value="{{ $provider }}" @selected(old('provider', $transaction->provider) === $provider)>{{ $provider }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="transaction_id">Transaction ID</label>
            <input id="transaction_id" name="transaction_id" type="text" value="{{ old('transaction_id', $transaction->transaction_id) }}" maxlength="100">
        </div>

        <div>
            <label for="category">Category</label>
            <select id="category" name="category" required>
                <option value="">Select category</option>
                @foreach (config('transactions.categories') as $category)
                    <option value="{{ $category }}" @selected(old('category', $transaction->category) === $category)>{{ $category }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="amount">Amount</label>
            <input id="amount" name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount', $transaction->amount) }}" required>
        </div>

        <div>
            <label for="status">Status</label>
            <select id="status" name="status" required>
                @foreach (config('transactions.statuses') as $status)
                    <option value="{{ $status }}" @selected(old('status', $transaction->status) == $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="payment_date">Payment date</label>
            <input id="payment_date" name="payment_date" type="date" value="{{ old('payment_date', $transaction->payment_date?->format('Y-m-d')) }}" required>
        </div>

        <div>
            <label for="order_reference">Order reference</label>
            <input id="order_reference" name="order_reference" type="text" value="{{ old('order_reference', $transaction->order_reference) }}" maxlength="100">
        </div>

        <div>
            <label for="expected_amount">Expected amount</label>
            <input id="expected_amount" name="expected_amount" type="number" step="0.01" min="0.01" value="{{ old('expected_amount', $transaction->expected_amount) }}">
        </div>

        @if ($transaction->exists)
            <div style="display: flex; align-items: end;">
                <label for="reconciled" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0;">
                    <input id="reconciled" name="reconciled" type="checkbox" value="1" @checked(old('reconciled', $transaction->reconciled))>
                    Reconciled
                </label>
            </div>
        @endif
    </div>

    @if ($transaction->exists)
        <div style="margin: 1.5rem 0; padding: 1rem; border: 1px solid #e5e7eb; border-radius: 8px; background: #f9fafb;">
            <h3 style="margin: 0 0 0.75rem; font-size: 1rem;">Reconciliation summary</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem;">
                <div><strong>Received amount:</strong><br>{{ number_format((float) $transaction->amount, 2) }}</div>
                <div><strong>Expected amount:</strong><br>{{ $transaction->expected_amount !== null ? number_format((float) $transaction->expected_amount, 2) : '—' }}</div>
                <div><strong>Difference:</strong><br>
                    @if ($transaction->difference === null)
                        —
                    @else
                        <span style="color: {{ $transaction->difference > 0 ? '#166534' : ($transaction->difference < 0 ? '#991b1b' : '#374151') }}; font-weight: 700;">
                            {{ ($transaction->difference > 0 ? '+' : '') . number_format((float) $transaction->difference, 2) }}
                        </span>
                    @endif
                </div>
                <div><strong>Reconciliation status:</strong><br>{{ str_replace('_', ' ', ucfirst($transaction->reconciliation_status ?? 'unreconciled')) }}</div>
                <div><strong>Reviewed:</strong><br>{{ $transaction->reconciled ? 'Yes' : 'No' }}</div>
            </div>
        </div>
    @endif

    <div style="margin-bottom: 1rem;">
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="4">{{ old('notes', $transaction->notes) }}</textarea>
    </div>
</div>
