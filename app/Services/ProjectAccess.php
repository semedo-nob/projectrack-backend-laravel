<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;

class ProjectAccess
{
    public const ROLE_SUPERVISOR = 'supervisor';

    public const ROLE_OWNER = 'owner';

    public const ROLE_VIEWER = 'viewer';

    public static function member(User $user, Project|string $project): ?ProjectMember
    {
        $projectId = $project instanceof Project ? $project->id : $project;

        return ProjectMember::query()
            ->where('project_id', $projectId)
            ->where('user_id', $user->id)
            ->first();
    }

    public static function canView(User $user, Project|string $project): bool
    {
        return self::member($user, $project) !== null;
    }

    public static function canWrite(User $user, Project|string $project): bool
    {
        $member = self::member($user, $project);
        if ($member === null) {
            return false;
        }

        return in_array($member->role, [self::ROLE_SUPERVISOR, self::ROLE_OWNER], true);
    }

    public static function canManageShares(User $user, Project|string $project): bool
    {
        $member = self::member($user, $project);
        if ($member === null) {
            return false;
        }

        return $member->role === self::ROLE_SUPERVISOR
            || ($project instanceof Project && $project->owner_id === $user->id)
            || (is_string($project) && Project::where('id', $project)->where('owner_id', $user->id)->exists());
    }

    public static function ensureView(User $user, Project|string $project): void
    {
        abort_unless(self::canView($user, $project), 403, 'You do not have access to this project.');
    }

    public static function ensureWrite(User $user, Project|string $project): void
    {
        abort_unless(self::canWrite($user, $project), 403, 'You cannot modify this project.');
    }
}
