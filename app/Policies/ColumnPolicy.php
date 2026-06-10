<?php

namespace App\Policies;

use App\Models\Column;
use App\Models\User;

class ColumnPolicy
{
    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Column $column): bool
    {
        return $column->user_id === $user->id;
    }
}
