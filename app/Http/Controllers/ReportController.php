<?php

namespace App\Http\Controllers;

use App\Models\ClassSection;
use App\Services\SchoolReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private function options(Request $request): array
    {
        $data = $request->validate([
            'class_section_id' => ['nullable', 'integer', Rule::exists('class_sections', 'id')],
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);
        return [
            'classId' => $data['class_section_id'] ?? null,
            'start' => $data['start_date'],
            'end' => $data['end_date'],
        ];
    }

    public function index(Request $request, SchoolReportService $reports): View
    {
        if (!$request->has('start_date')) {
            $request->merge(['start_date' => now()->startOfMonth()->toDateString()]);
        }
        if (!$request->has('end_date')) {
            $request->merge(['end_date' => now()->toDateString()]);
        }
        $options = $this->options($request);
        return view('reports.index', [
            'sections' => ClassSection::with('campus')->orderBy('name')->get(),
            'options' => $options,
            'report' => $reports->build($options['classId'], $options['start'], $options['end']),
        ]);
    }

    public function export(Request $request, SchoolReportService $reports): StreamedResponse
    {
        $options = $this->options($request);
        $data = $reports->build($options['classId'], $options['start'], $options['end']);
        $type = $request->validate(['type' => ['required', Rule::in(['attendance','exams','fees','library'])]])['type'];

        // Prevent spreadsheet formulas from executing in Excel/Sheets.
        $safe = static function ($value): string {
            $text = (string) $value;
            if (preg_match('/^[\s\x00-\x1F]*[=+@\-]/u', $text)) return "'".$text;
            return $text;
        };

        $rows = match ($type) {
            'attendance' => array_merge(
                [['Class','Recorded days','Attended days','Absent days','Attendance rate %']],
                $data['attendance']->map(fn ($r) => [$r['class'],$r['records'],$r['attended'],$r['absent'],$r['rate']])->all()
            ),
            'exams' => array_merge(
                [['Class','Exam','Graded results','Passed results','Pass rate %','Average %']],
                $data['exams']->map(fn ($r) => [$r['class'],$r['exam'],$r['graded'],$r['passed'],$r['pass_rate'],$r['average_percent']])->all()
            ),
            'fees' => [
                ['Metric','Value (INR unless specified)'],
                ['Charge count',$data['fees']['charge_count']],
                ['Total charges',number_format($data['fees']['charged_paise']/100, 2, '.', '')],
                ['Period payment count',$data['fees']['payment_count']],
                ['Payments received in period',number_format($data['fees']['period_paid_paise']/100, 2, '.', '')],
                ['Current outstanding',number_format($data['fees']['outstanding_paise']/100, 2, '.', '')],
            ],
            'library' => [
                ['Metric','Count'],
                ['Loans issued in period',$data['library']['period_issued']],
                ['Of those, returned',$data['library']['period_issued_returned']],
                ['Currently active loans',$data['library']['active']],
            ],
        };
        return response()->streamDownload(function () use ($rows, $safe) {
            $output = fopen('php://output', 'w');
            foreach ($rows as $row) fputcsv($output, array_map($safe, $row));
            fclose($output);
        }, 'schoolapp-'.$type.'-'.$options['start'].'-to-'.$options['end'].'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
