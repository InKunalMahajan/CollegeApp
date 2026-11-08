<?php

namespace App\Http\Controllers;

use App\Models\TeacherProfile;
use Illuminate\Contracts\View\View;

class MyTeachingController extends Controller
{
    public function __invoke(): View
    {
        $profile = TeacherProfile::with([
            'school', 'campus',
            'subjectAssignments' => fn($q) => $q
                ->whereHas('subject', fn($s) => $s->whereIn('school_id',
                    TeacherProfile::where('user_id',auth()->id())->select('school_id')))
                ->whereHas('classSection.campus', fn($c) => $c->whereIn('school_id',
                    TeacherProfile::where('user_id',auth()->id())->select('school_id')))
                ->with('subject','classSection'),
            'classTeacherAssignments' => fn($q) => $q
                ->whereHas('classSection.campus', fn($c) => $c->whereIn('school_id',
                    TeacherProfile::where('user_id',auth()->id())->select('school_id')))
                ->with('classSection'),
        ])->where('user_id', auth()->id())
            ->whereHas('campus',fn($q)=>$q->whereColumn('campuses.school_id','teacher_profiles.school_id'))->first();

        return view('teachers.mine', compact('profile'));
    }
}
