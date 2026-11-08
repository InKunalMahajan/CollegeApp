<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolAuditEvent;
use App\Models\SchoolMembership;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchoolSecurityController extends Controller
{
    // This is an explicitly scoped portal, separate from the legacy global
    // administrator dashboards. It fails closed without membership.
    private function assertMember(Request $request, School $school): void
    {
        abort_unless(
            SchoolMembership::where('school_id', $school->id)
                ->where('user_id', $request->user()->id)
                ->whereIn('role', ['school_admin', 'auditor'])
                ->exists(),
            403
        );
    }

    public function report(Request $request, School $school): View
    {
        $this->assertMember($request, $school);

        $sections = DB::table('class_sections as cs')
            ->join('campuses as ca', 'ca.id', '=', 'cs.campus_id')
            ->where('ca.school_id', $school->id)
            ->pluck('cs.id');

        $attendance = DB::table('student_attendances as a')
            ->join('class_enrollments as e', 'e.id', '=', 'a.class_enrollment_id')
            ->whereIn('e.class_section_id', $sections)
            ->selectRaw('COUNT(*) as recorded')
            ->selectRaw("SUM(CASE WHEN a.status IN ('present','late') THEN 1 ELSE 0 END) as attended")
            ->first();

        $exams = DB::table('exam_results as er')
            ->join('examinations as x', 'x.id', '=', 'er.examination_id')
            ->whereIn('x.class_section_id', $sections)
            ->where('er.status', 'graded')
            ->count();

        $feeCharges = DB::table('fee_charges as fc')
            ->join('class_enrollments as e', 'e.id', '=', 'fc.class_enrollment_id')
            ->whereIn('e.class_section_id', $sections)
            ->sum('fc.amount_paise');

        $feePayments = DB::table('fee_payments as fp')
            ->join('fee_charges as fc', 'fc.id', '=', 'fp.fee_charge_id')
            ->join('class_enrollments as e', 'e.id', '=', 'fc.class_enrollment_id')
            ->whereIn('e.class_section_id', $sections)
            ->sum('fp.amount_paise');

        $activeLoans = DB::table('library_loans as l')
            ->join('class_enrollments as e', 'e.id', '=', 'l.class_enrollment_id')
            ->whereIn('e.class_section_id', $sections)
            ->whereNull('l.returned_on')->count();

        $events = SchoolAuditEvent::where('school_id', $school->id)
            ->latest('id')->limit(25)->get();

        return view('security.school-report', [
            'school' => $school,
            'attendance' => $attendance,
            'gradedResults' => $exams,
            'chargedPaise' => (int) $feeCharges,
            'paidPaise' => (int) $feePayments,
            'activeLoans' => $activeLoans,
            'events' => $events,
        ]);
    }
}
