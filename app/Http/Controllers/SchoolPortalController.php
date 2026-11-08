<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class SchoolPortalController extends Controller
{
    public function index(School $school): View
    {
        $sectionIds = DB::table('class_sections as cs')
            ->join('campuses as ca', 'ca.id', '=', 'cs.campus_id')
            ->where('ca.school_id', $school->id)->select('cs.id');

        // DISTINCT removes duplicate enrollments for students across sections.
        $students = DB::table('students as st')
            ->join('class_enrollments as en', 'en.student_id', '=', 'st.id')
            ->whereIn('en.class_section_id', $sectionIds)
            ->select('st.id','st.admission_number','st.first_name','st.last_name')
            ->distinct()->orderBy('st.first_name')->limit(100)->get();

        $teachers = DB::table('teacher_profiles as t')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->where('t.school_id', $school->id)
            ->select('t.employee_code', 'u.name', 't.designation')
            ->orderBy('u.name')->limit(100)->get();

        $charges = DB::table('fee_charges as fc')
            ->join('class_enrollments as en', 'en.id', '=', 'fc.class_enrollment_id')
            ->join('fee_structures as fs', 'fs.id', '=', 'fc.fee_structure_id')
            ->whereIn('en.class_section_id', $sectionIds)
            ->whereIn('fs.class_section_id', $sectionIds)
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
