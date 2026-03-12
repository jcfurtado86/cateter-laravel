<?php

namespace App\Policies;

use App\Models\User;

class CatheterRecordPolicy
{
    public function manage(User $user): bool
    {
        return $user->role === 'DOCTOR';
    }
}
