<?php

namespace App\Http\Controllers;

use App\Models\TeacherProfile;
use Illuminate\Contracts\View\View;

class MyTeachingController extends Controller
{
    public function __invoke(): View
    {
        $profile = TeacherProfile::with([
            'school', 'campus', 'subjectAssignments.subject',
            'subjectAssignments.classSection', 'classTeacherAssignments.classSection',
        ])->where('user_id', auth()->id())->first();

        return view('teachers.mine', compact('profile'));
    }
}
