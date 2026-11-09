<?php

namespace App\Services;

use App\Models\SaasTenant;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class SaasTenantAccess
{
    // A platform owner is not automatically a tenant administrator.
    public function canManage(User $user, School $school): bool
    {
        return app(SchoolAccess::class)->can($user, $school, 'school.administration.manage');
    }

    public function scopeToSchool(Builder $query, School $school): Builder
    {
        return $query->where('school_id', $school->id);
    }

    // Existing schools remain valid without requiring any destructive backfill.
    // A tenant record is created on onboarding or explicitly by a trusted owner.
    public function status(School $school): string
    {
        return SaasTenant::where('school_id', $school->id)->value('status') ?? 'active';
    }

    public function mayOnboard(User $user, School $school): bool
    {
        return $this->canManage($user, $school)
            && $this->status($school) === 'active'
            && SchoolMembership::where('school_id', $school->id)
                ->where('user_id', $user->id)->where('role', 'school_admin')->exists();
    }
}
