<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\CampusMembership;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\User;
use App\Models\WebsiteOwner;
use Illuminate\Contracts\View\View;

class WebsiteOwnerDashboardController extends Controller
{
    /**
     * Platform metadata only. School academic, finance and learner details
     * belong to a separately authorized organization workspace.
     */
    public function index(): View
    {
        $metrics = [
            'organizations' => School::count(),
            'campuses' => Campus::count(),
            'accounts' => User::count(),
            'enabled_accounts' => User::whereNull('disabled_at')->count(),
            'disabled_accounts' => User::whereNotNull('disabled_at')->count(),
            'website_owners' => WebsiteOwner::count(),
            'organization_admin_assignments' => SchoolMembership::where('role', 'school_admin')->count(),
            'campus_admin_assignments' => CampusMembership::where('role', 'campus_admin')->count(),
        ];

        $organizations = School::query()->withCount('campuses')
            ->orderBy('name')->paginate(12);

        $myAdminSchoolIds = SchoolMembership::query()
            ->where('user_id', auth()->id())
            ->where('role', 'school_admin')
            ->pluck('school_id')->all();

        return view('website-owner.dashboard', compact('metrics', 'organizations', 'myAdminSchoolIds'));
    }
}
