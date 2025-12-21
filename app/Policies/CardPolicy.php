<?php

namespace App\Policies;

use App\Models\Card;
use App\Models\User;

class CardPolicy
{
    /**
     * Determine if the user can view the card.
     */
    public function view(User $user, Card $card): bool
    {
        return $user->id === $card->user_id;
    }

    /**
     * Determine if the user can update the card.
     */
    public function update(User $user, Card $card): bool
    {
        return $user->id === $card->user_id;
    }

    /**
     * Determine if the user can delete the card.
     */
    public function delete(User $user, Card $card): bool
    {
        return $user->id === $card->user_id;
    }
}
