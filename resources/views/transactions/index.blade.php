<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Transactions</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 2rem; background: #f7f7f7; color: #222; }
            h1 { margin-bottom: 1rem; }
            .toolbar { background: #fff; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
            .toolbar form { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.75rem; }
            .field { display: flex; flex-direction: column; gap: 0.35rem; }
            .field input, .field select, .field button, .field a { padding: 0.55rem 0.75rem; font-size: 0.95rem; }
            .actions { display: flex; gap: 0.5rem; }
            .button { border: 1px solid #ccc; background: #fff; border-radius: 4px; text-decoration: none; color: #222; }
            .primary { background: #0f172a; color: #fff; border-color: #0f172a; }
            .card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden; }
            table { width: 100%; border-collapse: collapse; }
            th, td { padding: 0.75rem 0.9rem; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: top; }
            th { background: #f3f4f6; }
            .status-pill { display: inline-block; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600; }
            .status-pending { background: #fef3c7; color: #92400e; }
            .status-completed { background: #dcfce7; color: #166534; }
            .status-failed { background: #fee2e2; color: #991b1b; }
            .status-refunded { background: #ede9fe; color: #5b21b6; }
            .reconciliation-pill { display: inline-block; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; }
            .reconciliation-unreconciled { background: #fff7ed; color: #9a5b00; }
            .reconciliation-exact_match { background: #dcfce7; color: #166534; }
            .reconciliation-underpaid { background: #fef3c7; color: #92400e; }
            .reconciliation-overpaid { background: #fee2e2; color: #991b1b; }
            .empty { padding: 1rem; color: #4b5563; }
            .alert { padding: 0.8rem 1rem; margin-bottom: 1rem; border-radius: 6px; background: #dcfce7; color: #166534; }
            .pagination { display: flex; justify-content: center; margin-top: 1rem; }
            .pagination nav { display: flex; gap: 0.5rem; }
            .pagination a, .pagination span { padding: 0.45rem 0.7rem; border-radius: 4px; background: #fff; border: 1px solid #ddd; text-decoration: none; color: #222; }
            .pagination .active { background: #0f172a; color: #fff; border-color: #0f172a; }
        </style>
    </head>
    <body>
        <h1>Transactions</h1>

        @if (session('success'))
            <div class="alert">{{ session('success') }}</div>
        @endif

        <div class="toolbar">
            <form method="GET" action="{{ route('transactions.index') }}">
                <div class="field">
                    <label for="search">Search</label>
                    <input id="search" name="search" type="text" value="{{ request('search') }}" placeholder="Customer, phone, ID, order...">
                </div>

                <div class="field">
                    <label for="provider">Provider</label>
                    <select id="provider" name="provider">
                        <option value="">All providers</option>
                        @foreach (config('transactions.providers') as $provider)
                            <option value="{{ $provider }}" @selected(request('provider') === $provider)>{{ $provider }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All statuses</option>
                        @foreach (config('transactions.statuses') as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All categories</option>
                        @foreach (config('transactions.categories') as $category)
                            <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="from">From</label>
                    <input id="from" name="from" type="date" value="{{ request('from') }}">
                </div>

                <div class="field">
                    <label for="to">To</label>
                    <input id="to" name="to" type="date" value="{{ request('to') }}">
                </div>

                <div class="field">
                    <label>&nbsp;</label>
                    <div class="actions">
                        <button type="submit" class="button primary">Apply</button>
                        <a href="{{ route('transactions.index') }}" class="button">Reset</a>
                        <a href="{{ route('transactions.create') }}" class="button primary">New transaction</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card">
            @if ($transactions->isEmpty())
                <div class="empty">No transactions found.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Provider</th>
                            <th>Transaction ID</th>
                            <th>Category</th>
                            <th>Received</th>
                            <th>Expected</th>
                            <th>Difference</th>
                            <th>Status</th>
                            <th>Payment date</th>
                            <th>Order reference</th>
                            <th>Reconciliation</th>
                            <th>Reviewed</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->customer_name }}</td>
                                <td>{{ $transaction->phone }}</td>
                                <td>{{ $transaction->provider }}</td>
                                <td>{{ $transaction->transaction_id ?? '—' }}</td>
                                <td>{{ $transaction->category }}</td>
                                <td>{{ number_format((float) $transaction->amount, 2) }}</td>
                                <td>{{ $transaction->expected_amount !== null ? number_format((float) $transaction->expected_amount, 2) : '—' }}</td>
                                <td>
                                    @if ($transaction->difference === null)
                                        —
                                    @else
                                        <span style="color: {{ $transaction->difference > 0 ? '#166534' : ($transaction->difference < 0 ? '#991b1b' : '#374151') }}; font-weight: 700;">
                                            {{ ($transaction->difference > 0 ? '+' : '') . number_format((float) $transaction->difference, 2) }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="status-pill status-{{ $transaction->status }}">{{ ucfirst($transaction->status) }}</span>
                                </td>
                                <td>{{ $transaction->payment_date?->format('Y-m-d') ?? '—' }}</td>
                                <td>{{ $transaction->order_reference ?? '—' }}</td>
                                <td>
                                    <span class="reconciliation-pill reconciliation-{{ $transaction->reconciliation_status }}">
                                        {{ str_replace('_', ' ', ucfirst($transaction->reconciliation_status ?? 'unreconciled')) }}
                                    </span>
                                </td>
                                <td>{{ $transaction->reconciled ? 'Yes' : 'No' }}</td>
                                <td>
                                    <div class="actions">
                                        <a href="{{ route('transactions.edit', $transaction) }}" class="button">Edit</a>
                                        <form method="POST" action="{{ route('transactions.destroy', $transaction) }}" onsubmit="return confirm('Are you sure you want to delete this transaction?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="button">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="pagination">
            {{ $transactions->links() }}
        </div>
    </body>
</html>
