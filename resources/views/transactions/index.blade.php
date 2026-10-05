@extends('layouts.app')

@section('title', 'Transactions')
@section('page-title', 'Transactions')

@section('content')
    <section class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" aria-label="Transaction filters">
        <form method="GET" action="{{ route('transactions.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="flex flex-col gap-1.5">
                <label for="search" class="text-sm font-medium text-slate-700">Search</label>
                <input id="search" name="search" type="text" value="{{ request('search') }}" placeholder="Customer, phone, ID, order..." class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="provider" class="text-sm font-medium text-slate-700">Provider</label>
                <select id="provider" name="provider" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                    <option value="">All providers</option>
                    @foreach (config('transactions.providers') as $provider)
                        <option value="{{ $provider }}" @selected(request('provider') === $provider)>{{ $provider }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="status" class="text-sm font-medium text-slate-700">Status</label>
                <select id="status" name="status" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                    <option value="">All statuses</option>
                    @foreach (config('transactions.statuses') as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="category" class="text-sm font-medium text-slate-700">Category</label>
                <select id="category" name="category" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                    <option value="">All categories</option>
                    @foreach (config('transactions.categories') as $category)
                        <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="from" class="text-sm font-medium text-slate-700">From</label>
                <input id="from" name="from" type="date" value="{{ request('from') }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="to" class="text-sm font-medium text-slate-700">To</label>
                <input id="to" name="to" type="date" value="{{ request('to') }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="reconciled" class="text-sm font-medium text-slate-700">Reconciliation</label>
                <select id="reconciled" name="reconciled" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                    <option value="">All reconciliation states</option>
                    <option value="1" @selected(request('reconciled') === '1')>Reconciled</option>
                    <option value="0" @selected(request('reconciled') === '0')>Unreconciled</option>
                </select>
            </div>

            <div class="flex flex-wrap items-end gap-2 sm:col-span-2 lg:col-span-4">
                <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">Apply</button>
                <a href="{{ route('transactions.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">Reset</a>
                <a href="{{ route('transactions.export', request()->except('page')) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">Export CSV</a>
                @can('create', \App\Models\Transaction::class)
                    <a href="{{ route('transactions.create') }}" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-700 focus:ring-offset-2">New transaction</a>
                @endcan
            </div>
        </form>
    </section>

    <section class="mb-4 flex flex-col gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between" aria-label="Transaction summary">
        <p class="text-sm text-slate-600"><strong class="font-semibold text-slate-900">Filtered transactions:</strong> {{ $transactionCount }}</p>
        <p class="text-sm text-slate-600"><strong class="font-semibold text-slate-900">Total amount:</strong> {{ number_format((float) $totalAmount, 2) }}</p>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" aria-label="Transactions">
        @if ($transactions->isEmpty())
            <div class="px-4 py-10 text-center text-sm text-slate-600">No transactions found.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-sm">
                    <thead class="bg-slate-100 text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <tr>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Customer</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Phone</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Provider</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Transaction ID</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Category</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Received</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Expected</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Difference</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Status</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Payment date</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Order reference</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Reconciliation</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Reviewed</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @foreach ($transactions as $transaction)
                            <tr class="align-top hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900">{{ $transaction->customer_name }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $transaction->phone }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $transaction->provider }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $transaction->transaction_id ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $transaction->category }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ number_format((float) $transaction->amount, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $transaction->expected_amount !== null ? number_format((float) $transaction->expected_amount, 2) : '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if ($transaction->difference === null)
                                        —
                                    @else
                                        <span @class([
                                            'font-semibold',
                                            'text-emerald-700' => $transaction->difference > 0,
                                            'text-rose-700' => $transaction->difference < 0,
                                            'text-slate-700' => ! ($transaction->difference > 0) && ! ($transaction->difference < 0),
                                        ])>
                                            {{ ($transaction->difference > 0 ? '+' : '') . number_format((float) $transaction->difference, 2) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                        'bg-amber-100 text-amber-800' => $transaction->status === 'pending',
                                        'bg-emerald-100 text-emerald-800' => $transaction->status === 'completed',
                                        'bg-rose-100 text-rose-800' => $transaction->status === 'failed',
                                        'bg-violet-100 text-violet-800' => $transaction->status === 'refunded',
                                    ])>{{ ucfirst($transaction->status) }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $transaction->payment_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $transaction->order_reference ?? '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                        'bg-orange-100 text-orange-800' => ($transaction->reconciliation_status ?? 'unreconciled') === 'unreconciled',
                                        'bg-emerald-100 text-emerald-800' => $transaction->reconciliation_status === 'exact_match',
                                        'bg-amber-100 text-amber-800' => $transaction->reconciliation_status === 'underpaid',
                                        'bg-rose-100 text-rose-800' => $transaction->reconciliation_status === 'overpaid',
                                    ])>
                                        {{ str_replace('_', ' ', ucfirst($transaction->reconciliation_status ?? 'unreconciled')) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $transaction->reconciled ? 'Yes' : 'No' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex min-w-max flex-wrap gap-2">
                                        @can('update', $transaction)
                                            <a href="{{ route('transactions.edit', $transaction) }}" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-1">Edit</a>
                                            <form method="POST" action="{{ route('transactions.reconcile', $transaction) }}">
                                                @csrf
                                                <button type="submit" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-1">
                                                    {{ $transaction->reconciled ? 'Mark as unreconciled' : 'Reconcile' }}
                                                </button>
                                            </form>
                                        @endcan
                                        @can('delete', $transaction)
                                            <form method="POST" action="{{ route('transactions.destroy', $transaction) }}" onsubmit="return confirm('Are you sure you want to delete this transaction?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-md border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-600 focus:ring-offset-1">Delete</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <div class="mt-6 flex justify-center">
        {{ $transactions->links() }}
    </div>
@endsection
