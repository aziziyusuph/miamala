<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Transaction {{ $transaction->transaction_id ?? $transaction->id }}</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 2rem; background: #f7f7f7; color: #222; }
            .container { max-width: 980px; margin: 0 auto; }
            .card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 1.5rem; }
            h1 { margin-top: 0; }
            dl { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
            dt { font-weight: 600; color: #4b5563; }
            dd { margin: 0.25rem 0 0; }
            .actions { display: flex; gap: 0.75rem; margin-top: 1.5rem; }
            .button { padding: 0.65rem 0.8rem; border: 1px solid #cbd5e1; border-radius: 6px; text-decoration: none; color: #222; background: #fff; }
            .primary { background: #0f172a; color: #fff; border-color: #0f172a; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Transaction details</h1>
            <div class="card">
                <dl>
                    <div><dt>Customer</dt><dd>{{ $transaction->customer_name }}</dd></div>
                    <div><dt>Phone</dt><dd>{{ $transaction->phone }}</dd></div>
                    <div><dt>Provider</dt><dd>{{ $transaction->provider }}</dd></div>
                    <div><dt>Transaction ID</dt><dd>{{ $transaction->transaction_id ?? '—' }}</dd></div>
                    <div><dt>Category</dt><dd>{{ $transaction->category }}</dd></div>
                    <div><dt>Received amount</dt><dd>{{ number_format((float) $transaction->amount, 2) }}</dd></div>
                    <div><dt>Expected amount</dt><dd>{{ $transaction->expected_amount !== null ? number_format((float) $transaction->expected_amount, 2) : '—' }}</dd></div>
                    <div><dt>Difference</dt><dd>{{ $transaction->difference !== null ? number_format((float) $transaction->difference, 2) : '—' }}</dd></div>
                    <div><dt>Status</dt><dd>{{ ucfirst($transaction->status) }}</dd></div>
                    <div><dt>Payment date</dt><dd>{{ $transaction->payment_date?->format('Y-m-d') ?? '—' }}</dd></div>
                    <div><dt>Order reference</dt><dd>{{ $transaction->order_reference ?? '—' }}</dd></div>
                    <div><dt>Reconciliation</dt><dd>{{ str_replace('_', ' ', ucfirst($transaction->reconciliation_status ?? 'unreconciled')) }}</dd></div>
                    <div><dt>Reviewed</dt><dd>{{ $transaction->reconciled ? 'Yes' : 'No' }}</dd></div>
                    <div><dt>Notes</dt><dd>{{ $transaction->notes ?? '—' }}</dd></div>
                </dl>
                <div class="actions">
                    <a href="{{ route('transactions.edit', $transaction) }}" class="button primary">Edit transaction</a>
                    <a href="{{ route('transactions.index') }}" class="button">Back to list</a>
                </div>
            </div>
        </div>
    </body>
</html>
