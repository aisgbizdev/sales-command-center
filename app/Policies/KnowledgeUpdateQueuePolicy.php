<?php

namespace App\Policies;

use App\Models\KnowledgeUpdateQueue;
use App\Models\User;

class KnowledgeUpdateQueuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isKepala();
    }

    public function view(User $user, KnowledgeUpdateQueue $knowledgeUpdateQueue): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isKepala()) {
            return $knowledgeUpdateQueue->chatReview?->submitter?->unit_id === $user->unit_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isKepala() || $user->isManager();
    }

    public function approve(User $user, KnowledgeUpdateQueue $knowledgeUpdateQueue): bool
    {
        return $user->isSuperAdmin();
    }
}
