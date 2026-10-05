@extends('layouts.app')

@section('title', 'Overview')
@section('page-title', 'Overview')

@section('content')
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <p class="max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">Payment activity and reconciliation overview for your business.</p>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('transactions.index') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">View Transactions</a>
            @can('create', \App\Models\Transaction::class)
                <a href="{{ route('transactions.create') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">Add Transaction</a>
            @endcan
            <a href="{{ route('transactions.export') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">Export Transactions</a>
        </div>
    </div>

    <section aria-label="Business transaction summary">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['label' => 'Total transactions', 'value' => number_format($totalTransactions), 'detail' => 'Recorded for this business'],
                ['label' => 'Completed amount', 'value' => number_format((float) $completedAmount, 2), 'detail' => 'Completed transactions'],
                ['label' => 'Pending transactions', 'value' => number_format($pendingTransactions), 'detail' => 'Awaiting an outcome'],
                ['label' => 'Needs reconciliation', 'value' => number_format($needsReview), 'detail' => 'Not yet marked reconciled'],
            ] as $metric)
                <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-medium text-slate-600">{{ $metric['label'] }}</h2>
                    <p class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">{{ $metric['value'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $metric['detail'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    @if ($totalTransactions === 0)
        <section class="mt-6 rounded-xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center sm:py-14" aria-label="No transactions">
            <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-emerald-50 text-lg font-semibold text-emerald-800" aria-hidden="true">+</span>
            <h2 class="mt-4 text-lg font-semibold text-slate-950">No transactions yet</h2>
            <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-600">Transactions will appear here once your organization records them. Add a transaction to begin comparing received and expected amounts.</p>
            @can('create', \App\Models\Transaction::class)
                <a href="{{ route('transactions.create') }}" class="mt-5 inline-flex min-h-10 items-center justify-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">Add your first transaction</a>
            @endcan
        </section>
    @endif

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="status-summary-title">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 id="status-summary-title" class="text-base font-semibold text-slate-950">Transaction status</h2>
                    <p class="mt-1 text-sm text-slate-500">Counts by recorded status</p>
                </div>
                <a href="{{ route('transactions.index') }}" class="text-sm font-semibold text-emerald-800 hover:text-emerald-950 focus:outline-none focus:underline">View all</a>
            </div>
            <dl class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($statusCounts as $status => $count)
                    <div class="rounded-lg bg-slate-50 px-4 py-3">
                        <dt class="text-xs font-medium capitalize text-slate-500">{{ $status }}</dt>
                        <dd class="mt-1 text-xl font-semibold text-slate-900">{{ number_format($count) }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="reconciliation-summary-title">
            <div>
                <h2 id="reconciliation-summary-title" class="text-base font-semibold text-slate-950">Reconciliation</h2>
                <p class="mt-1 text-sm text-slate-500">Calculated match category and review state</p>
            </div>
            <dl class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($reconciliationStatuses as $label => $status)
                    <div class="rounded-lg bg-slate-50 px-4 py-3">
                        <dt class="text-xs font-medium text-slate-500">{{ $label }}</dt>
                        <dd class="mt-1 text-xl font-semibold text-slate-900">{{ number_format($reconciliationCounts[$status]) }}</dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-4 text-xs leading-5 text-slate-500">{{ number_format($reconciledTransactions) }} transactions marked reconciled.</p>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 xl:col-span-1" aria-labelledby="provider-summary-title">
            <h2 id="provider-summary-title" class="text-base font-semibold text-slate-950">Payment channels</h2>
            <p class="mt-1 text-sm text-slate-500">Recorded transaction channels, not live integrations.</p>
            @if (count($providerCounts) > 0)
                <ul class="mt-4 divide-y divide-slate-100">
                    @foreach ($providerCounts as $provider => $count)
                        <li class="flex items-center justify-between gap-4 py-3 text-sm">
                            <span class="font-medium text-slate-700">{{ $provider }}</span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ number_format($count) }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mt-4 text-sm text-slate-500">No payment channels recorded yet.</p>
            @endif
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm xl:col-span-2" aria-labelledby="recent-transactions-title">
            <div class="flex items-center justify-between gap-4 px-5 py-5 sm:px-6">
                <div>
                    <h2 id="recent-transactions-title" class="text-base font-semibold text-slate-950">Recent transactions</h2>
                    <p class="mt-1 text-sm text-slate-500">Latest payment records for your business</p>
                </div>
                <a href="{{ route('transactions.index') }}" class="shrink-0 text-sm font-semibold text-emerald-800 hover:text-emerald-950 focus:outline-none focus:underline">View all</a>
            </div>
            @if ($recentTransactions->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="whitespace-nowrap px-5 py-3 sm:px-6">Customer</th>
                                <th scope="col" class="whitespace-nowrap px-5 py-3">Channel</th>
                                <th scope="col" class="whitespace-nowrap px-5 py-3">Amount</th>
                                <th scope="col" class="whitespace-nowrap px-5 py-3">Status</th>
                                <th scope="col" class="whitespace-nowrap px-5 py-3">Reconciliation</th>
                                <th scope="col" class="whitespace-nowrap px-5 py-3 sm:pr-6">Payment date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach ($recentTransactions as $transaction)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-900 sm:px-6">{{ $transaction->customer_name }}</td>
                                    <td class="whitespace-nowrap px-5 py-3">{{ $transaction->provider }}</td>
                                    <td class="whitespace-nowrap px-5 py-3">{{ number_format((float) $transaction->amount, 2) }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 capitalize">{{ $transaction->status }}</td>
                                    <td class="whitespace-nowrap px-5 py-3">{{ str_replace('_', ' ', ucfirst($transaction->reconciliation_status)) }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 sm:pr-6">{{ $transaction->payment_date?->format('Y-m-d') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="px-5 pb-5 text-sm text-slate-500 sm:px-6">No recent transactions to show.</p>
            @endif
        </section>
    </div>
@endsection
