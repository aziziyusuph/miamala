<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Transaction::query();

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
        return view('transactions.create');
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['reconciled'] = $request->boolean('reconciled', false);

        Transaction::create($data);

        return redirect()->route('transactions.index')->with('success', 'Transaction created successfully.');
    }

    public function edit(Transaction $transaction): View
    {
        return view('transactions.edit', compact('transaction'));
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $data = $request->validated();
        $data['reconciled'] = $request->boolean('reconciled', false);

        $transaction->fill($data);
        $transaction->save();

        return redirect()->route('transactions.index')->with('success', 'Transaction updated successfully.');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        $transaction->delete();

        return redirect()->route('transactions.index')->with('success', 'Transaction deleted successfully.');
    }
}
