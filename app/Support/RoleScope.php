<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class RoleScope
{
    public static function forOwnerTeamUnit(Builder $query, User $user, string $ownerColumn = 'owner_id', string $teamColumn = 'team_id', string $unitColumn = 'unit_id'): Builder
    {
        return $query
            ->when($user->isPenjualan(), fn (Builder $q) => $q->where($ownerColumn, $user->id))
            ->when($user->isManager(), fn (Builder $q) => $q->where($teamColumn, $user->team_id))
            ->when($user->isKepala(), fn (Builder $q) => $q->where($unitColumn, $user->unit_id));
    }

    public static function forSubmitterTeamUnit(Builder $query, User $user, string $relation = 'submitter'): Builder
    {
        return $query
            ->when($user->isManager(), fn (Builder $q) => $q->whereHas($relation, fn (Builder $sq) => $sq->where('team_id', $user->team_id)))
            ->when($user->isKepala(), fn (Builder $q) => $q->whereHas($relation, fn (Builder $sq) => $sq->where('unit_id', $user->unit_id)))
            ->when($user->isPenjualan(), fn (Builder $q) => $q->whereHas($relation, fn (Builder $sq) => $sq->where('id', $user->id)));
    }
}
