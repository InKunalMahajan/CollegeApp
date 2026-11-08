<?php

namespace App\Services;

use App\Models\ClassSection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class SchoolReportService
{
    /**
     * A report is a snapshot of existing saved records, not an estimate or
     * scheduled-days attendance calculation. The optional class filter also
     * applies to fee charges and borrowing records.
     */
    public function build(?int $classId, string $start, string $end): array
    {
        $attendance = DB::table('student_attendances as a')
            ->join('class_enrollments as e', 'e.id', '=', 'a.class_enrollment_id')
            ->join('class_sections as c', 'c.id', '=', 'e.class_section_id')
            ->select('c.id as class_id', 'c.name as class_name')
            ->selectRaw('COUNT(*) as recorded_days')
            ->selectRaw("SUM(CASE WHEN a.status IN ('present', 'late') THEN 1 ELSE 0 END) as attended_days")
            ->selectRaw("SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_days")
            ->whereBetween('a.attendance_date', [$start, $end])
            ->when($classId, fn ($q) => $q->where('c.id', $classId))
            ->groupBy('c.id', 'c.name')->orderBy('c.name')->get()
            ->map(fn ($row) => [
                'class' => $row->class_name,
                'records' => (int) $row->recorded_days,
                'attended' => (int) $row->attended_days,
                'absent' => (int) $row->absent_days,
                'rate' => $row->recorded_days ? round(100 * $row->attended_days / $row->recorded_days, 2) : 0,
            ]);

        $exams = DB::table('exam_results as r')
            ->join('examinations as x', 'x.id', '=', 'r.examination_id')
            ->join('class_sections as c', 'c.id', '=', 'x.class_section_id')
            ->select('x.id', 'x.name as exam', 'c.name as class_name', 'x.max_marks', 'x.pass_marks')
            ->selectRaw("SUM(CASE WHEN r.status = 'graded' AND r.marks_obtained IS NOT NULL THEN 1 ELSE 0 END) as graded_count")
            ->selectRaw("SUM(CASE WHEN r.status = 'graded' AND r.marks_obtained >= x.pass_marks THEN 1 ELSE 0 END) as passed_count")
            ->selectRaw("SUM(CASE WHEN r.status = 'graded' THEN r.marks_obtained ELSE 0 END) as marks_total")
            ->whereBetween('x.exam_date', [$start, $end])
            ->when($classId, fn ($q) => $q->where('x.class_section_id', $classId))
            ->groupBy('x.id', 'x.name', 'c.name', 'x.max_marks', 'x.pass_marks')->orderBy('x.name')->get()
            ->map(fn ($row) => [
                'class' => $row->class_name,
                'exam' => $row->exam,
                'graded' => (int) $row->graded_count,
                'passed' => (int) $row->passed_count,
                'pass_rate' => $row->graded_count ? round(100 * $row->passed_count / $row->graded_count, 2) : 0,
                'average_percent' => $row->graded_count && $row->max_marks
                    ? round(100 * $row->marks_total / ($row->graded_count * $row->max_marks), 2) : 0,
            ]);

        // Fee liabilities are lifetime charge snapshots for the selected class,
        // not limited to the report date range. Only payments are date-filtered.
        $charges = DB::table('fee_charges as ch')
            ->join('class_enrollments as e', 'e.id', '=', 'ch.class_enrollment_id')
            ->selectRaw('COUNT(*) as charge_count, COALESCE(SUM(ch.amount_paise), 0) as charged_paise')
            ->when($classId, fn ($q) => $q->where('e.class_section_id', $classId))->first();

        $payments = DB::table('fee_payments as p')
            ->join('fee_charges as ch', 'ch.id', '=', 'p.fee_charge_id')
            ->join('class_enrollments as e', 'e.id', '=', 'ch.class_enrollment_id')
            ->whereBetween('p.paid_on', [$start, $end])
            ->when($classId, fn ($q) => $q->where('e.class_section_id', $classId))
            ->selectRaw('COUNT(*) as payment_count, COALESCE(SUM(p.amount_paise), 0) as paid_paise')->first();

        $totalPaid = DB::table('fee_payments as p')
            ->join('fee_charges as ch', 'ch.id', '=', 'p.fee_charge_id')
            ->join('class_enrollments as e', 'e.id', '=', 'ch.class_enrollment_id')
            ->when($classId, fn ($q) => $q->where('e.class_section_id', $classId))
            ->sum('p.amount_paise');

        $loans = DB::table('library_loans as l')
            ->join('class_enrollments as e', 'e.id', '=', 'l.class_enrollment_id')
            ->whereBetween('l.issued_on', [$start, $end])
            ->when($classId, fn ($q) => $q->where('e.class_section_id', $classId))
            ->selectRaw('COUNT(*) as issued')
            ->selectRaw('SUM(CASE WHEN l.returned_on IS NOT NULL THEN 1 ELSE 0 END) as returned')
            ->first();

        $activeLoans = DB::table('library_loans as l')
            ->join('class_enrollments as e', 'e.id', '=', 'l.class_enrollment_id')
            ->whereNull('l.returned_on')
            ->when($classId, fn ($q) => $q->where('e.class_section_id', $classId))
            ->count();

        return [
            'attendance' => $attendance,
            'exams' => $exams,
            'fees' => [
                'charge_count' => (int) $charges->charge_count,
                'charged_paise' => (int) $charges->charged_paise,
                'payment_count' => (int) $payments->payment_count,
                'period_paid_paise' => (int) $payments->paid_paise,
                'outstanding_paise' => max(0, (int) $charges->charged_paise - (int) $totalPaid),
            ],
            'library' => [
                'period_issued' => (int) $loans->issued,
                'period_issued_returned' => (int) $loans->returned,
                'active' => $activeLoans,
            ],
        ];
    }
}
