<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolDepartment;
use App\Models\SchoolDepartmentMembership;
use App\Models\SchoolMembership;
use App\Models\User;

class SchoolAccess
{
    /**
     * School-scoped permission, with no global role bypass.
     * Department membership by itself is never a school-admin grant.
     */
    public function can(User $user, School $school, string $ability): bool
    {
        if (! array_key_exists($ability, config('school_permissions.abilities', []))) {
            return false;
        }

        $membership = SchoolMembership::query()
            ->where('school_id', $school->id)
            ->where('user_id', $user->id)->first();

        if (! $membership) {
            return false;
        }

        $permissions = config('school_permissions.school_roles.'.$membership->role, []);
        if (! is_array($permissions)) {
            return false;
        }

        if (! in_array('*', $permissions, true)
            && ! in_array($ability, $permissions, true)) {
            return false;
        }

        // Department-only users must still have at least one current
        // department assignment to prevent orphaned school membership access.
        if ($membership->role === 'department_member') {
            return SchoolDepartmentMembership::query()
                ->where('school_id', $school->id)
                ->where('user_id', $user->id)
                ->whereIn('role', array_keys(config('school_permissions.department_roles', [])))
                ->whereHas('department', fn ($q) => $q->where('school_id', $school->id))
                ->exists();
        }

        return true;
    }

    /**
     * A department permission is tied to the actual school and department.
     * The school administrator can manage their own school's departments.
     */
    public function canDepartment(
        User $user,
        School $school,
        SchoolDepartment $department,
        string $ability
    ): bool {
        if ($department->school_id !== $school->id
            || ! array_key_exists($ability, config('school_permissions.department_abilities', []))) {
            return false;
        }

        if ($this->can($user, $school, 'school.departments.manage')) {
            return true;
        }

        if (! $this->can($user, $school, 'school.departments.view')) {
            return false;
        }

        $membership = SchoolDepartmentMembership::query()
            ->where('school_id', $school->id)
            ->where('school_department_id', $department->id)
            ->where('user_id', $user->id)->first();

        if (! $membership) {
            return false;
        }

        return in_array(
            $ability,
            config('school_permissions.department_roles.'.$membership->role, []),
            true
        );
    }

    public function assignedDepartmentIds(User $user, School $school): array
    {
        if (! $this->can($user, $school, 'school.employees.view')) {
            return [];
        }

        return SchoolDepartmentMembership::query()
            ->where('school_id', $school->id)
            ->where('user_id', $user->id)
            ->whereIn('role', array_keys(config('school_permissions.department_roles', [])))
            ->whereHas('department', fn ($q) => $q->where('school_id', $school->id))
            ->pluck('school_department_id')->all();
    }
}
