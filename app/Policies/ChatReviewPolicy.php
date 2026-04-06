<?php

namespace App\Policies;

use App\Models\ChatReview;
use App\Models\User;

class ChatReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isKepala() || $user->isManager();
    }

    public function view(User $user, ChatReview $chatReview): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isKepala()) {
            return $chatReview->submitter?->unit_id === $user->unit_id;
        }

        if ($user->isManager()) {
            return $chatReview->submitter?->team_id === $user->team_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isKepala() || $user->isManager();
    }

    public function update(User $user, ChatReview $chatReview): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, ChatReview $chatReview): bool
    {
        return $user->isSuperAdmin();
    }

    public function comment(User $user, ChatReview $chatReview): bool
    {
        return $this->view($user, $chatReview) && ($user->isSuperAdmin() || $user->isKepala() || $user->isManager());
    }

    public function markImportant(User $user, ChatReview $chatReview): bool
    {
        return $this->comment($user, $chatReview);
    }
}
