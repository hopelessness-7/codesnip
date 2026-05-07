<?php

namespace App\Policies;

use App\Models\Snippet;
use App\Models\User;

class SnippetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Snippet $snippet): bool
    {
        return $snippet->user_id === $user->id || $snippet->is_public;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Snippet $snippet): bool
    {
        return $snippet->user_id === $user->id;
    }

    public function delete(User $user, Snippet $snippet): bool
    {
        return $snippet->user_id === $user->id;
    }
}
