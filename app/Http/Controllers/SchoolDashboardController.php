<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\SchoolDashboardAnalytics;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolDashboardController extends Controller
{
    private function dates(Request $request): array
    {
        $data = $request->validate([
            'start_date'=>['nullable','date_format:Y-m-d'],
            'end_date'=>['nullable','date_format:Y-m-d'],
        ]);
        $end = isset($data['end_date'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', $data['end_date'])
            : CarbonImmutable::today();
        $start = isset($data['start_date'])
            ? CarbonImmutable::createFromFormat('!Y-m-d', $data['start_date'])
            : $end->subDays(29);
        if ($start->greaterThan($end) || $start->diffInDays($end) > 365) {
            throw ValidationException::withMessages([
                'start_date'=>'Choose a date range of 1 to 366 days, with the start on or before the end.',
            ]);
        }
        return [$start,$end];
    }

    public function index(Request $request, School $school, SchoolDashboardAnalytics $analytics): View
    {
        [$start,$end]=$this->dates($request);
        $metrics=$analytics->summarize($school,$start,$end);
        return view('school-dashboard.index',compact('school','metrics'));
    }

    public function export(Request $request, School $school, SchoolDashboardAnalytics $analytics): StreamedResponse
    {
        [$start,$end]=$this->dates($request);
        $data=$analytics->summarize($school,$start,$end);
        // Aggregate-only CSV: no student names, emails or admission numbers.
        $rows=[
            ['Metric','Value'],
            ['School',$school->name],
            ['Start date',$data['start']],
            ['End date',$data['end']],
            ['Students (total)',$data['students']['total']],
            ['Students (active)',$data['students']['active']],
            ['Students (enrolled)',$data['students']['enrolled']],
            ['Teachers',$data['teachers']],
            ['Attendance records (period)',$data['attendance']['recorded']],
            ['Present or late (period)',$data['attendance']['attended']],
            ['Attendance rate (percent)',$data['attendance']['rate']],
            ['Graded exam results (period)',$data['exams']['graded']],
            ['Passed exam results (period)',$data['exams']['passed']],
            ['Exam pass rate (percent)',$data['exams']['pass_rate']],
            ['Exam average (percent)',$data['exams']['average_percent']],
            ['Lifetime charges (INR)',$data['fees']['charged_paise']/100],
            ['Lifetime payments (INR)',$data['fees']['paid_paise']/100],
            ['Outstanding (INR)',$data['fees']['outstanding_paise']/100],
            ['Payments in period (INR)',$data['fees']['period_paid_paise']/100],
            ['Book titles',$data['library']['titles']],
            ['Book copies',$data['library']['copies']],
            ['Active library loans',$data['library']['active_loans']],
        ];
        return response()->streamDownload(function () use ($rows) {
            $output=fopen('php://output','w');
            foreach ($rows as $row) {
                // Prevent spreadsheet formula injection when a school name is user-supplied.
                $safe=array_map(static function ($value) {
                    $text=(string)$value;
                    return preg_match('/^[\s\x00-\x1F]*[=+@\-]/u',$text) ? "'".$text : $text;
                },$row);
                fputcsv($output,$safe);
            }
            fclose($output);
        },'schoolapp-dashboard-'.$school->id.'-'.$data['start'].'-to-'.$data['end'].'.csv',[
            'Content-Type'=>'text/csv; charset=UTF-8',
            'Cache-Control'=>'no-store, private',
        ]);
    }
}
