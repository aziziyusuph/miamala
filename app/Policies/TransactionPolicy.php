<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;
use App\Services\DemoEnvironment;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->business()->exists();
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $this->ownsTransaction($user, $transaction);
    }

    public function create(User $user): bool
    {
        return ! app(DemoEnvironment::class)->isDemoUser($user)
            && $user->business()->exists();
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return ! app(DemoEnvironment::class)->isDemoUser($user)
            && $this->ownsTransaction($user, $transaction);
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return ! app(DemoEnvironment::class)->isDemoUser($user)
            && $this->ownsTransaction($user, $transaction);
    }

    private function ownsTransaction(User $user, Transaction $transaction): bool
    {
        return $user->business_id !== null && $transaction->business_id === $user->business_id;
    }
}
