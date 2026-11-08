<?php

namespace App\Services;

use App\Models\School;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Shared school-ownership predicates for previously denormalized relations.
 * IDs are query builders so callers do not load all matching IDs into memory.
 */
class SchoolDataScope
{
    public function sectionIds(School $school): Builder
    {
        return DB::table('class_sections as scoped_sections')
            ->join('campuses as scoped_campuses','scoped_campuses.id','=','scoped_sections.campus_id')
            ->join('academic_years as scoped_years','scoped_years.id','=','scoped_sections.academic_year_id')
            ->where('scoped_campuses.school_id',$school->id)
            ->where('scoped_years.school_id',$school->id)
            ->select('scoped_sections.id');
    }

    public function enrollmentIds(School $school): Builder
    {
        return DB::table('class_enrollments as scoped_enrollments')
            ->join('students as scoped_students','scoped_students.id','=','scoped_enrollments.student_id')
            ->where('scoped_students.school_id',$school->id)
            ->whereIn('scoped_enrollments.class_section_id',$this->sectionIds($school))
            ->select('scoped_enrollments.id');
    }

    public function chargeIds(School $school): Builder
    {
        return DB::table('fee_charges as scoped_charges')
            ->join('fee_structures as scoped_fees','scoped_fees.id','=','scoped_charges.fee_structure_id')
            ->join('class_enrollments as scoped_enrollments','scoped_enrollments.id','=','scoped_charges.class_enrollment_id')
            ->whereColumn('scoped_fees.class_section_id','scoped_enrollments.class_section_id')
            ->whereIn('scoped_enrollments.id',$this->enrollmentIds($school))
            ->select('scoped_charges.id');
    }

    public function loanIds(School $school): Builder
    {
        return DB::table('library_loans as scoped_loans')
            ->join('library_copies as scoped_copies','scoped_copies.id','=','scoped_loans.library_copy_id')
            ->join('library_books as scoped_books','scoped_books.id','=','scoped_copies.library_book_id')
            ->where('scoped_books.school_id',$school->id)
            ->whereIn('scoped_loans.class_enrollment_id',$this->enrollmentIds($school))
            ->select('scoped_loans.id');
    }
}
