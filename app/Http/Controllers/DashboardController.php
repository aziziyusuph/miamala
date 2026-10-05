<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\TransactionReconciliationService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $business = auth()->user()?->business;

        abort_unless($business instanceof Business, 404);

        $transactions = $business->transactions();
        $totalTransactions = (clone $transactions)->count();
        $completedAmount = (clone $transactions)->where('status', 'completed')->sum('amount');
        $pendingTransactions = (clone $transactions)->where('status', 'pending')->count();
        $needsReview = (clone $transactions)->where('reconciled', false)->count();
        $reconciledTransactions = (clone $transactions)->where('reconciled', true)->count();

        $statusCounts = $this->countsBy($transactions, 'status');
        $providerCounts = $this->countsBy($transactions, 'provider');
        $reconciliationCounts = $this->countsBy($transactions, 'reconciliation_status');
        $reconciliationStatuses = [
            'Exact match' => TransactionReconciliationService::STATUS_EXACT_MATCH,
            'Underpaid' => TransactionReconciliationService::STATUS_UNDERPAID,
            'Overpaid' => TransactionReconciliationService::STATUS_OVERPAID,
            'Unreconciled' => TransactionReconciliationService::STATUS_UNRECONCILED,
        ];

        $statusCounts = array_replace(array_fill_keys(config('transactions.statuses'), 0), $statusCounts);
        $reconciliationCounts = array_replace(array_fill_keys(array_values($reconciliationStatuses), 0), $reconciliationCounts);

        $recentTransactions = (clone $transactions)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'totalTransactions',
            'completedAmount',
            'pendingTransactions',
            'needsReview',
            'reconciledTransactions',
            'statusCounts',
            'providerCounts',
            'reconciliationCounts',
            'reconciliationStatuses',
            'recentTransactions',
        ));
    }

    /**
     * @return array<string, int>
     */
    private function countsBy(HasMany $transactions, string $column): array
    {
        return (clone $transactions)
            ->selectRaw("{$column}, COUNT(*) as aggregate")
            ->groupBy($column)
            ->pluck('aggregate', $column)
            ->map(fn ($count): int => (int) $count)
            ->all();
    }
}
