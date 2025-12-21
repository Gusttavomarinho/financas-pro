<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Determine if the user can update the category.
     */
    public function update(User $user, Category $category): bool
    {
        // System categories can't be updated
        if ($category->is_system) {
            return false;
        }
        return $user->id === $category->user_id;
    }

    /**
     * Determine if the user can delete the category.
     */
    public function delete(User $user, Category $category): bool
    {
        // System categories can't be deleted
        if ($category->is_system) {
            return false;
        }
        return $user->id === $category->user_id;
    }
}
