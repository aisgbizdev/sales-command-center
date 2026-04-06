<?php

namespace App\Policies;

use App\Models\Prospect;
use App\Models\User;

class ProspectPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, User::ROLES, true);
    }

    public function view(User $user, Prospect $prospect): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isKepala()) {
            return $prospect->unit_id === $user->unit_id;
        }

        if ($user->isManager()) {
            return $prospect->team_id === $user->team_id;
        }

        return $prospect->owner_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isPenjualan();
    }

    public function update(User $user, Prospect $prospect): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isManager()) {
            return $prospect->team_id === $user->team_id;
        }

        return $user->isPenjualan() && $prospect->owner_id === $user->id;
    }

    public function delete(User $user, Prospect $prospect): bool
    {
        return $user->isSuperAdmin();
    }
}
