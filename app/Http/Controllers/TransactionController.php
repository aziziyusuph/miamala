<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Business;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $business = $this->currentBusiness();
        Gate::authorize('viewAny', Transaction::class);

        $query = $business->transactions();

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

        $transactions = $query->orderByDesc('payment_date')->paginate(15)->appends($request->query());

        return view('transactions.index', compact('transactions'));
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

        $transaction->fill($data);
        $transaction->save();

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
}
