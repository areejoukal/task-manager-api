<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $project->isMember($user->id);
    }

    public function update(User $user, Project $project): bool
    {
        return $project->canEdit($user->id);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->id === $project->user_id; // Owner فقط
    }

    /**
     * إدارة الأعضاء: Owner فقط
     */
    public function manageMembers(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }
}