<?php

namespace App\Policies;

use App\Models\Content;
use App\Models\User;

class ContentPolicy
{
    public function update(User $user, Content $content): bool
    {
        return $user->active && ($user->canPublish() || ($content->user_id === $user->id && in_array($content->status, ['draft', 'review'], true)));
    }

    public function delete(User $user, Content $content): bool
    {
        return $this->update($user, $content);
    }
}
