<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\SchoolDataScope;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class SchoolPortalController extends Controller
{
    public function index(School $school, SchoolDataScope $scope): View
    {
        // DISTINCT removes duplicate enrollments for students across sections.
        $students = DB::table('students as st')
            ->join('class_enrollments as en', 'en.student_id', '=', 'st.id')
            ->where('st.school_id', $school->id)
            ->whereIn('en.id', $scope->enrollmentIds($school))
            ->select('st.id','st.admission_number','st.first_name','st.last_name')
            ->distinct()->orderBy('st.first_name')->limit(100)->get();

        $teachers = DB::table('teacher_profiles as t')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->join('campuses as tc','tc.id','=','t.campus_id')
            ->where('t.school_id', $school->id)
            ->where('tc.school_id', $school->id)
            ->select('t.employee_code', 'u.name', 't.designation')
            ->orderBy('u.name')->limit(100)->get();

        $charges = DB::table('fee_charges as fc')
            ->join('class_enrollments as en', 'en.id', '=', 'fc.class_enrollment_id')
            ->join('fee_structures as fs', 'fs.id', '=', 'fc.fee_structure_id')
            ->whereIn('fc.id', $scope->chargeIds($school))
            ->select('fc.id','fc.amount_paise','fs.name')
            ->orderByDesc('fc.id')->limit(100)->get();

        $books = DB::table('library_books as b')
            ->leftJoin('library_copies as c', 'c.library_book_id', '=', 'b.id')
            ->where('b.school_id', $school->id)
            ->select('b.id','b.title','b.author')
            ->selectRaw('COUNT(c.id) as copies')
            ->groupBy('b.id','b.title','b.author')
            ->orderBy('b.title')->limit(100)->get();

        return view('school-portal.index', compact('school','students','teachers','charges','books'));
    }
}
