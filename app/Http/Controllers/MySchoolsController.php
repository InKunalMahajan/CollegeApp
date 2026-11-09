<?php

namespace App\Http\Controllers;

use App\Models\SchoolMembership;
use App\Models\CampusMembership;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MySchoolsController extends Controller
{
    public function index(Request $request): View
    {
        // Only the signed-in user's schools are exposed. No global school list.
        $memberships=SchoolMembership::with('school')
            ->where('user_id',$request->user()->id)
            ->whereIn('role',['school_admin','auditor','department_member','organization_viewer'])
            ->orderBy('school_id')->get();

        $campusMemberships = CampusMembership::query()->with('campus.school')
            ->where('user_id', $request->user()->id)
            ->whereIn('role', ['campus_admin','campus_member','campus_viewer'])
            ->orderBy('campus_id')->get();

        return view('school-admin.my-schools', compact('memberships', 'campusMemberships'));
    }
}
