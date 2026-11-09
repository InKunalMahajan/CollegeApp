<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\ClassEnrollment;
use App\Models\ClassSection;
use App\Models\Examination;
use App\Models\ExamResult;
use App\Models\School;
use App\Models\Subject;
use App\Services\SchoolAuditTrail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CampusExamController extends Controller
{
    private function sections(School $school, Campus $campus)
    {
        return ClassSection::where('campus_id',$campus->id)
            ->whereHas('academicYear',fn ($q) => $q->where('school_id',$school->id));
    }

    private function exams(School $school, Campus $campus)
    {
        return Examination::whereHas('classSection',fn ($q) => $q->where('campus_id',$campus->id)
            ->whereHas('academicYear',fn ($y) => $y->where('school_id',$school->id)))
            ->whereHas('subject',fn ($q) => $q->where('school_id',$school->id));
    }

    public function index(School $school, Campus $campus): View
    {
        $sections = $this->sections($school,$campus)->orderBy('name')->get();
        $subjects = Subject::where('school_id',$school->id)->orderBy('name')->get();
        $exams = $this->exams($school,$campus)->with(['classSection','subject'])->latest()->paginate(20);
        $enrollments = ClassEnrollment::with('student')
            ->whereHas('student',fn ($q) => $q->where('school_id',$school->id)
                ->where('campus_id',$campus->id))
            ->whereIn('class_section_id',$this->sections($school,$campus)->select('id'))
            ->orderBy('id')->limit(300)->get();
        $results = ExamResult::with(['examination','enrollment.student'])
            ->whereIn('examination_id',$this->exams($school,$campus)->select('id'))
            ->whereHas('enrollment',fn ($q) => $q
                ->whereHas('student',fn ($s) => $s->where('school_id',$school->id)
                    ->where('campus_id',$campus->id))
                ->whereIn('class_section_id',$this->sections($school,$campus)->select('id')))
            ->latest()->paginate(20, ['*'], 'results_page');
        return view('school-campuses.exams', compact('school','campus','sections','subjects','exams','enrollments','results'));
    }

    public function store(Request $request, School $school, Campus $campus, SchoolAuditTrail $audit): RedirectResponse
    {
        $data = $request->validate([
            'class_section_id'=>['required','integer'],
            'subject_id'=>['required','integer'],
            'name'=>['required','string','max:150'],
            'exam_date'=>['required','date_format:Y-m-d'],
            'max_marks'=>['required','integer','between:1,10000'],
            'pass_marks'=>['required','integer','min:0','lte:max_marks'],
        ]);
        DB::transaction(function () use ($request,$school,$campus,$data,$audit) {
            $section = $this->sections($school,$campus)->whereKey($data['class_section_id'])
                ->lockForUpdate()->firstOrFail();
            $subject = Subject::whereKey($data['subject_id'])->where('school_id',$school->id)
                ->firstOrFail();
            if (Examination::where('class_section_id',$section->id)->where('subject_id',$subject->id)
                ->where('name',$data['name'])->exists()) {
                throw ValidationException::withMessages(['name'=>'Examination already exists.']);
            }
            $exam = Examination::create($data);
            $audit->record($school->id,$request->user()->id,'campus.examination.created',
                'examination',$exam->id,['campus_id'=>$campus->id]);
        });
        return to_route('campus.exams.index',[$school,$campus])->with('success','Exam created.');
    }

    public function result(Request $request, School $school, Campus $campus, Examination $exam, SchoolAuditTrail $audit): RedirectResponse
    {
        $exam = $this->exams($school,$campus)->whereKey($exam->id)->firstOrFail();
        $data = $request->validate([
            'class_enrollment_id'=>['required','integer'],
            'status'=>['required',Rule::in(['graded','absent','exempt'])],
            'marks_obtained'=>['nullable','required_if:status,graded','integer','min:0','max:'.$exam->max_marks],
            'remarks'=>['nullable','string','max:2000'],
        ]);
        if ($data['status'] !== 'graded' && isset($data['marks_obtained'])) {
            throw ValidationException::withMessages(['marks_obtained'=>'Marks only allowed for graded results.']);
        }
        DB::transaction(function () use ($request,$school,$campus,$exam,$data,$audit) {
            $lockedExam = $this->exams($school,$campus)->whereKey($exam->id)->lockForUpdate()->firstOrFail();
            $enrollment = ClassEnrollment::whereKey($data['class_enrollment_id'])
                ->where('class_section_id',$lockedExam->class_section_id)
                ->whereHas('student',fn ($q) => $q->where('school_id',$school->id)
                    ->where('campus_id',$campus->id))
                ->lockForUpdate()->firstOrFail();
            $record = ExamResult::updateOrCreate(
                ['examination_id'=>$lockedExam->id,'class_enrollment_id'=>$enrollment->id],
                ['status'=>$data['status'],
                 'marks_obtained'=>$data['status']==='graded' ? $data['marks_obtained'] : null,
                 'remarks'=>$data['remarks'] ?? null]
            );
            $audit->record($school->id,$request->user()->id,'campus.examination.result.saved',
                'exam_result',$record->id,['campus_id'=>$campus->id,'status'=>$data['status']]);
        });
        return to_route('campus.exams.index',[$school,$campus])->with('success','Result saved.');
    }
}
