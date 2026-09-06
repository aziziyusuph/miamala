<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Business;
use App\Models\Transaction;
use App\Services\TransactionLifecycleService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $business = $this->currentBusiness();
        Gate::authorize('viewAny', Transaction::class);

        $query = $this->filteredTransactionQuery($request);

        $transactionCount = (clone $query)->count();
        $totalAmount = (clone $query)->sum('amount');
        $transactions = $query->orderByDesc('payment_date')->paginate(15)->appends($request->query());

        return view('transactions.index', compact('transactions', 'transactionCount', 'totalAmount'));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->currentBusiness();
        Gate::authorize('viewAny', Transaction::class);

        $filename = 'miamala-transactions-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($request): void {
            $output = fopen('php://output', 'wb');

            fputcsv($output, [
                'Transaction ID',
                'Customer',
                'Phone',
                'Provider',
                'Category',
                'Amount',
                'Status',
                'Payment Date',
                'Order Reference',
                'Expected Amount',
                'Reconciliation Status',
                'Reconciled',
                'Notes',
            ]);

            foreach ($this->filteredTransactionQuery($request)
                ->orderByDesc('payment_date')
                ->orderByDesc('id')
                ->cursor() as $transaction) {
                fputcsv($output, [
                    $this->csvValue($transaction->transaction_id),
                    $this->csvValue($transaction->customer_name),
                    $this->csvValue($transaction->phone),
                    $this->csvValue($transaction->provider),
                    $this->csvValue($transaction->category),
                    $this->csvValue($transaction->amount),
                    $this->csvValue($transaction->status),
                    $this->csvValue($transaction->payment_date?->format('Y-m-d H:i:s')),
                    $this->csvValue($transaction->order_reference),
                    $this->csvValue($transaction->expected_amount),
                    $this->csvValue($transaction->reconciliation_status),
                    $this->csvValue($transaction->reconciled ? 'Yes' : 'No'),
                    $this->csvValue($transaction->notes),
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function reconcile(int $transaction): RedirectResponse
    {
        $transaction = $this->transactionForCurrentBusiness($transaction);
        Gate::authorize('update', $transaction);

        $transaction->reconciled = ! $transaction->reconciled;
        $transaction->save();

        return redirect()->route('transactions.index')->with(
            'success',
            $transaction->reconciled ? 'Transaction reconciled successfully.' : 'Transaction marked as unreconciled.',
        );
    }

    public function create(): View
    {
        $this->currentBusiness();
        Gate::authorize('create', Transaction::class);

        return view('transactions.create');
    }

    public function show(int $transaction): View
    {
        $transaction = $this->transactionForCurrentBusiness($transaction);
        Gate::authorize('view', $transaction);

        return view('transactions.show', compact('transaction'));
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $business = $this->currentBusiness();
        Gate::authorize('create', Transaction::class);
        $data = $request->validated();
        $data['reconciled'] = $request->boolean('reconciled', false);
        $data['business_id'] = $business->id;

        Transaction::create($data);

        return redirect()->route('transactions.index')->with('success', 'Transaction created successfully.');
    }

    public function edit(int $transaction): View
    {
        $transaction = $this->transactionForCurrentBusiness($transaction);
        Gate::authorize('view', $transaction);

        return view('transactions.edit', compact('transaction'));
    }

    public function update(UpdateTransactionRequest $request, int $transaction): RedirectResponse
    {
        $transaction = $this->transactionForCurrentBusiness($transaction);
        Gate::authorize('update', $transaction);
        $data = $request->validated();
        $data['reconciled'] = $request->boolean('reconciled', false);
        $targetStatus = $data['status'];
        unset($data['status']);

        $transaction->fill($data);
        app(TransactionLifecycleService::class)->transition($transaction, $targetStatus);

        return redirect()->route('transactions.index')->with('success', 'Transaction updated successfully.');
    }

    public function destroy(int $transaction): RedirectResponse
    {
        $transaction = $this->transactionForCurrentBusiness($transaction);
        Gate::authorize('delete', $transaction);
        $transaction->delete();

        return redirect()->route('transactions.index')->with('success', 'Transaction deleted successfully.');
    }

    private function currentBusiness(): Business
    {
        $business = auth()->user()?->business;

        abort_unless($business instanceof Business, 404);

        return $business;
    }

    private function transactionForCurrentBusiness(int $transaction): Transaction
    {
        return $this->currentBusiness()->transactions()->findOrFail($transaction);
    }

    private function filteredTransactionQuery(Request $request): Builder|HasMany
    {
        $query = $this->currentBusiness()->transactions();

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%")
                    ->orWhere('order_reference', 'like', "%{$search}%");
            });
        }

        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('from')) {
            $query->whereDate('payment_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('payment_date', '<=', $request->to);
        }

        if ($request->has('reconciled') && in_array((string) $request->reconciled, ['0', '1'], true)) {
            $query->where('reconciled', $request->boolean('reconciled'));
        }

        return $query;
    }

    private function csvValue(mixed $value): string
    {
        $value = (string) ($value ?? '');

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'{$value}";
        }

        return $value;
    }
}
