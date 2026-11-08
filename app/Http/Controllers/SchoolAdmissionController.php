<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Student;
use App\Services\SchoolAuditTrail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SchoolAdmissionController extends Controller
{
    public function store(Request $request, School $school, SchoolAuditTrail $audit): RedirectResponse
    {
        $data = $request->validate([
            'admission_number' => ['required','string','max:40', Rule::unique('students','admission_number')],
            'first_name' => ['required','string','max:255'],
            'last_name' => ['required','string','max:255'],
            'email' => ['nullable','email','max:255',Rule::unique('students','email')],
            'admission_date' => ['required','date'],
            'status' => ['required',Rule::in(['active','inactive','graduated'])],
        ]);

        DB::transaction(function () use ($school, $data, $audit) {
            $student=Student::create($data+['school_id'=>$school->id]);
            $audit->record($school->id,auth()->id(),
                'academics.student.admitted','student',$student->id);
        });
        return to_route('school-academics.index',$school)->with('success','Student admitted to school.');
    }
}
