<?php

namespace App\Services;

use App\Models\Campus;
use App\Models\CampusMembership;
use App\Models\School;
use App\Models\User;

class CampusAccess
{
    private const ABILITIES = [
        'campus.dashboard.view',
        'campus.operations.view',
        'campus.employees.view',
        'campus.employees.manage',
        'campus.students.view',
        'campus.students.manage',
    ];

    public function isOrganizationAdmin(User $user, School $school): bool
    {
        return app(SchoolAccess::class)->can($user, $school, 'school.administration.manage');
    }

    public function can(User $user, School $school, Campus $campus, string $ability): bool
    {
        if ((int) $campus->school_id !== (int) $school->id
            || ! in_array($ability, self::ABILITIES, true)) {
            return false;
        }

        if ($this->isOrganizationAdmin($user, $school)) {
            return true;
        }

        // A global User.role value never grants campus permissions. A campus
        // administrator cannot become a school administrator by guessing IDs.
        return CampusMembership::query()
            ->where('campus_id', $campus->id)
            ->where('user_id', $user->id)
            ->where('role', 'campus_admin')
            ->exists();
    }
}
