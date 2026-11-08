<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Student;
use App\Services\SchoolAuditTrail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SchoolStudentController extends Controller
{
    private function owned(School $school, Student $student): Student
    {
        abort_unless((int) $student->school_id === (int) $school->id, 404);
        return $student;
    }

    public function index(Request $request, School $school): View
    {
        $filters = $request->validate([
            'search' => ['nullable','string','max:100'],
            'status' => ['nullable',Rule::in(['active','inactive','graduated'])],
        ]);
        $search=trim($filters['search'] ?? '');
        $status=$filters['status'] ?? '';

        $students=Student::query()->where('school_id',$school->id)
            ->when($status !== '',fn($q)=>$q->where('status',$status))
            ->when($search !== '',fn($q)=>$q->where(function ($q) use ($search) {
                $q->where('first_name','like','%'.$search.'%')
                    ->orWhere('last_name','like','%'.$search.'%')
                    ->orWhere('admission_number','like','%'.$search.'%');
            }))
            ->orderBy('first_name')->orderBy('last_name')->orderBy('id')
            ->paginate(15)->withQueryString();

        return view('school-students.index',compact('school','students','search','status'));
    }

    public function show(School $school, Student $student): View
    {
        $student=$this->owned($school,$student);
        return view('school-students.show',compact('school','student'));
    }

    public function edit(School $school, Student $student): View
    {
        $student=$this->owned($school,$student);
        return view('school-students.edit',compact('school','student'));
    }

    public function update(Request $request, School $school, Student $student, SchoolAuditTrail $audit): RedirectResponse
    {
        $student=$this->owned($school,$student);
        $data=$request->validate([
            'admission_number'=>['required','string','max:40',Rule::unique('students','admission_number')->ignore($student->id)],
            'first_name'=>['required','string','max:255'],
            'last_name'=>['required','string','max:255'],
            'email'=>['nullable','email','max:255',Rule::unique('students','email')->ignore($student->id)],
            'admission_date'=>['required','date'],
            'status'=>['required',Rule::in(['active','inactive','graduated'])],
        ]);
        // school_id is intentionally not accepted from user input.
        DB::transaction(function () use ($school,$student,$data,$audit) {
            $record=Student::query()->whereKey($student->id)
                ->where('school_id',$school->id)->lockForUpdate()->firstOrFail();
            $previousStatus=$record->status;
            $record->fill($data);
            if (!$record->isDirty()) {
                return;
            }
            $record->save();
            $audit->record($school->id,auth()->id(),'academics.student.profile_updated',
                'student',$record->id,[
                    'fields'=>array_values(array_keys($record->getChanges())),
                    'previous_status'=>$previousStatus,
                    'new_status'=>$record->status,
                ]);
        });

        return to_route('school-students.show',[$school,$student])
            ->with('success','Student profile updated.');
    }
}
