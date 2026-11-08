<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\User;
use App\Services\SchoolAuditTrail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SchoolStudentAccountController extends Controller
{
    public function index(School $school): View
    {
        // No global student/account directory or other-school login identities.
        $students=Student::query()
            ->where('school_id',$school->id)
            ->with('school')
            ->orderBy('first_name')->orderBy('id')->get(['id','school_id','admission_number','first_name','last_name']);

        $links=StudentAccount::query()
            ->whereHas('student', fn($q)=>$q->where('school_id',$school->id))
            ->with(['student','user'])
            ->orderBy('id')->paginate(25);

        return view('school-student-accounts.index',compact('school','students','links'));
    }

    public function store(Request $request, School $school, SchoolAuditTrail $audit): RedirectResponse
    {
        $data=$request->validate([
            'student_id'=>['required','integer'],
            'email'=>['required','email','max:255'],
        ]);

        DB::transaction(function () use ($data,$school,$audit) {
            // Lock both target rows. Global unique DB indexes remain the final
            // protection against simultaneous links from other schools.
            $student=Student::query()->whereKey($data['student_id'])
                ->where('school_id',$school->id)->lockForUpdate()->first();
            abort_unless($student,404);

            $user=User::query()->where('email',$data['email'])
                ->where('role','student')->lockForUpdate()->first();
            if (!$user) {
                throw ValidationException::withMessages([
                    'email'=>'No eligible, available student login was found.',
                ]);
            }
            if (StudentAccount::where('student_id',$student->id)->exists()
                || StudentAccount::where('user_id',$user->id)->exists()) {
                throw ValidationException::withMessages([
                    'email'=>'This student or login is already linked.',
                ]);
            }

            $link=StudentAccount::create([
                'student_id'=>$student->id,'user_id'=>$user->id,
            ]);
            $audit->record($school->id,auth()->id(),
                'student_account.linked','student_account',$link->id,
                ['student_id'=>$student->id,'user_id'=>$user->id]);
        });

        return to_route('school-student-accounts.index',$school)
            ->with('success','Student login linked.');
    }

    public function destroy(School $school, StudentAccount $account, SchoolAuditTrail $audit): RedirectResponse
    {
        // Never allow guessed account IDs to unlink another school's student.
        DB::transaction(function () use ($school,$account,$audit) {
            $current=StudentAccount::query()->whereKey($account->id)
                ->lockForUpdate()->firstOrFail();
            $student=Student::query()->whereKey($current->student_id)
                ->where('school_id',$school->id)->lockForUpdate()->first();
            abort_unless($student,404);

            $id=$current->id;
            $userId=$current->user_id;
            $current->delete();
            $audit->record($school->id,auth()->id(),
                'student_account.unlinked','student_account',$id,
                ['student_id'=>$student->id,'user_id'=>$userId]);
        });

        return to_route('school-student-accounts.index',$school)
            ->with('success','Student login unlinked. Coursework records are retained.');
    }
}
