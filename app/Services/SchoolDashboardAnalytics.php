<?php

namespace App\Services;

use App\Models\School;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class SchoolDashboardAnalytics
{
    public function __construct(private readonly SchoolDataScope $scope) {}

    /**
     * Attendance and exam metrics use the selected period. Financial balances
     * are lifetime balances, while collections use the selected payment period.
     * Every sensitive relation goes through the Phase 20 ownership predicates.
     */
    public function summarize(School $school, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $sections = $this->scope->sectionIds($school);
        $enrollments = $this->scope->enrollmentIds($school);
        $charges = $this->scope->chargeIds($school);

        $students = DB::table('students')->where('school_id', $school->id);
        $studentCount = (clone $students)->count();
        $activeStudents = (clone $students)->where('status', 'active')->count();
        $enrolledStudents = DB::table('class_enrollments as e')
            ->whereIn('e.id', $enrollments)
            ->distinct()->count('e.student_id');

        $teacherCount = DB::table('teacher_profiles as t')
            ->join('campuses as c', 'c.id', '=', 't.campus_id')
            ->where('t.school_id', $school->id)
            ->where('c.school_id', $school->id)
            ->distinct()->count('t.id');

        $attendanceQuery = DB::table('student_attendances as a')
            ->whereIn('a.class_enrollment_id', $this->scope->enrollmentIds($school))
            ->whereDate('a.attendance_date', '>=', $start->toDateString())
            ->whereDate('a.attendance_date', '<=', $end->toDateString());
        $attendance = (clone $attendanceQuery)
            ->selectRaw('COUNT(*) as recorded')
            ->selectRaw("SUM(CASE WHEN a.status IN ('present','late') THEN 1 ELSE 0 END) as attended")
            ->first();

        $dailyRows = (clone $attendanceQuery)
            ->select('a.attendance_date')
            ->selectRaw('COUNT(*) as recorded')
            ->selectRaw("SUM(CASE WHEN a.status IN ('present','late') THEN 1 ELSE 0 END) as attended")
            ->groupBy('a.attendance_date')
            ->orderBy('a.attendance_date')
            ->get();
        $dailyAttendance = $dailyRows->map(fn ($row) => [
            'date' => (string) $row->attendance_date,
            'recorded' => (int) $row->recorded,
            'attended' => (int) $row->attended,
            'rate' => $this->percent((int) $row->attended, (int) $row->recorded),
        ])->all();

        $results = DB::table('exam_results as er')
            ->join('examinations as ex', 'ex.id', '=', 'er.examination_id')
            ->join('class_enrollments as en', 'en.id', '=', 'er.class_enrollment_id')
            ->join('subjects as su', 'su.id', '=', 'ex.subject_id')
            ->whereIn('en.id', $this->scope->enrollmentIds($school))
            ->whereColumn('en.class_section_id', 'ex.class_section_id')
            ->where('su.school_id', $school->id)
            ->whereIn('ex.class_section_id', $this->scope->sectionIds($school))
            ->whereDate('ex.exam_date', '>=', $start->toDateString())
            ->whereDate('ex.exam_date', '<=', $end->toDateString())
            ->where('er.status', 'graded')
            ->whereNotNull('er.marks_obtained')
            ->where('ex.max_marks', '>', 0)
            ->selectRaw('COUNT(*) as graded')
            ->selectRaw('SUM(CASE WHEN er.marks_obtained >= ex.pass_marks THEN 1 ELSE 0 END) as passed')
            ->selectRaw('AVG(er.marks_obtained * 100.0 / ex.max_marks) as average_percent')
            ->first();

        $chargedPaise = (int) DB::table('fee_charges')
            ->whereIn('id', $charges)->sum('amount_paise');
        $payments = DB::table('fee_payments as fp')
            ->whereIn('fp.fee_charge_id', $this->scope->chargeIds($school));
        $paidPaise = (int) (clone $payments)->sum('fp.amount_paise');
        $periodPaidPaise = (int) (clone $payments)
            ->whereDate('fp.paid_on', '>=', $start->toDateString())
            ->whereDate('fp.paid_on', '<=', $end->toDateString())
            ->sum('fp.amount_paise');

        $books = DB::table('library_books')->where('school_id', $school->id);
        $bookTitles = (clone $books)->count();
        $bookCopies = DB::table('library_copies as c')
            ->join('library_books as b', 'b.id', '=', 'c.library_book_id')
            ->where('b.school_id', $school->id)
            ->count();
        $activeLoans = DB::table('library_loans')
            ->whereIn('id', $this->scope->loanIds($school))
            ->whereNull('returned_on')->count();

        $recorded = (int) ($attendance->recorded ?? 0);
        $attended = (int) ($attendance->attended ?? 0);
        $graded = (int) ($results->graded ?? 0);
        $passed = (int) ($results->passed ?? 0);

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'students' => ['total'=>$studentCount,'active'=>$activeStudents,'enrolled'=>$enrolledStudents],
            'teachers' => $teacherCount,
            'attendance' => [
                'recorded'=>$recorded,'attended'=>$attended,
                'rate'=>$this->percent($attended,$recorded),
                'daily'=>$dailyAttendance,
            ],
            'exams' => [
                'graded'=>$graded,'passed'=>$passed,
                'pass_rate'=>$this->percent($passed,$graded),
                'average_percent'=>$graded ? round((float) $results->average_percent, 1) : 0.0,
            ],
            'fees' => [
                'charged_paise'=>$chargedPaise,'paid_paise'=>$paidPaise,
                'outstanding_paise'=>max(0,$chargedPaise-$paidPaise),
                'period_paid_paise'=>$periodPaidPaise,
            ],
            'library' => ['titles'=>$bookTitles,'copies'=>$bookCopies,'active_loans'=>$activeLoans],
        ];
    }

    private function percent(int $numerator, int $denominator): float
    {
        return $denominator > 0 ? round($numerator * 100 / $denominator, 1) : 0.0;
    }
}
