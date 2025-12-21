<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    /**
     * Determine if the user can view the account.
     */
    public function view(User $user, Account $account): bool
    {
        return $user->id === $account->user_id;
    }

    /**
     * Determine if the user can update the account.
     */
    public function update(User $user, Account $account): bool
    {
        return $user->id === $account->user_id;
    }

    /**
     * Determine if the user can delete the account.
     */
    public function delete(User $user, Account $account): bool
    {
        return $user->id === $account->user_id;
    }
}
