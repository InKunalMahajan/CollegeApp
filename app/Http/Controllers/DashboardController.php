<?php

namespace App\Http\Controllers;

use App\Models\CampusMembership;
use App\Models\Course;
use App\Models\Department;
use App\Models\SchoolMembership;
use App\Models\Student;
use App\Models\WebsiteOwner;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // One login, distinct workspaces. Platform ownership never implies
        // organization authorization; organization access requires membership.
        if (WebsiteOwner::where('user_id', $user->id)->exists()) {
            return to_route('website-owner.dashboard');
        }

        $organization = SchoolMembership::query()
            ->where('user_id', $user->id)
            ->where('role', 'school_admin')
            ->orderBy('school_id')->first();

        if ($organization) {
            return to_route('school-dashboard.index', $organization->school_id);
        }

        $campus = CampusMembership::query()
            ->where('user_id', $user->id)
            ->where('role', 'campus_admin')
            ->orderBy('campus_id')->first();

        if ($campus) {
            return to_route('campus-workspace.index', [$campus->campus->school_id, $campus->campus_id]);
        }

        $legacy = config('school_security.legacy_global_routes_enabled', false);
        $staff = $legacy && $user->isStaff();

        return view('dashboard', [
            'canViewStudents' => $staff,
            'students' => $staff ? Student::count() : null,
            'active' => $staff ? Student::where('status', 'active')->count() : null,
            'courses' => $legacy ? Course::count() : 0,
            'departments' => $legacy ? Department::count() : 0,
            'recent' => $staff ? Student::with('course')->latest()->take(5)->get() : collect(),
        ]);
    }
}
