<?php

namespace App\Http\Controllers;

use App\Models\ClassEnrollment;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\Student;
use App\Models\User;
use App\Services\SchoolAuditTrail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SchoolAdministrationController extends Controller
{
    public function index(School $school): View
    {
        $memberships=SchoolMembership::with('user')
            ->where('school_id',$school->id)
            ->orderBy('role')->orderBy('id')->get();

        // Legacy students are not listed globally. Verification requires the
        // school administrator to supply an existing admission number.
        return view('school-admin.index',compact('school','memberships'));
    }

    public function addAuditor(Request $request, School $school, SchoolAuditTrail $audit): RedirectResponse
    {
        $data=$request->validate([
            'email'=>['required','email','max:255'],
        ]);
        $user=User::where('email',$data['email'])->first();
        // Do not reveal to callers which email addresses exist.
        if (!$user) {
            throw ValidationException::withMessages(['email'=>'No eligible existing account was found.']);
        }
        // Never create / elevate a global administrator through a school route.
        // New school_admin memberships must be securely provisioned out of band.
        DB::transaction(function () use ($school,$user,$audit) {
            $existing=SchoolMembership::where('school_id',$school->id)
                ->where('user_id',$user->id)->lockForUpdate()->first();
            if ($existing) {
                throw ValidationException::withMessages(['email'=>'Account already belongs to this school.']);
            }
            $membership=SchoolMembership::create([
                'school_id'=>$school->id,'user_id'=>$user->id,'role'=>'auditor',
            ]);
            $audit->record($school->id,auth()->id(),'school.membership.auditor_added',
                'school_membership',$membership->id, ['user_id'=>$user->id]);
        });

        return to_route('school-admin.index',$school)->with('success','Auditor access granted.');
    }

    public function removeAuditor(School $school, SchoolMembership $membership, SchoolAuditTrail $audit): RedirectResponse
    {
        // Members from other schools and school administrators cannot be
        // removed through this endpoint, including via guessed IDs.
        abort_unless((int)$membership->school_id === (int)$school->id
            && $membership->role === 'auditor', 404);

        DB::transaction(function () use ($school,$membership,$audit) {
            $current=SchoolMembership::whereKey($membership->id)->lockForUpdate()->firstOrFail();
            abort_unless((int)$current->school_id === (int)$school->id
                && $current->role === 'auditor',404);
            $userId=$current->user_id;
            $current->delete();
            $audit->record($school->id,auth()->id(),'school.membership.auditor_removed',
                'school_membership',$current->id,['user_id'=>$userId]);
        });

        return to_route('school-admin.index',$school)->with('success','Auditor access removed.');
    }

    public function claimLegacyStudent(Request $request, School $school, SchoolAuditTrail $audit): RedirectResponse
    {
        $data=$request->validate([
            'admission_number'=>['required','string','max:40'],
        ]);

        DB::transaction(function () use ($data,$school,$audit) {
            $student=Student::where('admission_number',$data['admission_number'])
                ->lockForUpdate()->first();
            // Prevent claiming already-owned records or probing other schools.
            abort_unless($student && $student->school_id === null,404);

            $enrollments=ClassEnrollment::with('classSection.campus')
                ->where('student_id',$student->id)->get();
            if ($enrollments->isEmpty() || $enrollments->contains(
                fn ($enrollment) => !$enrollment->classSection
                    || !$enrollment->classSection->campus
                    || (int)$enrollment->classSection->campus->school_id !== (int)$school->id
            )) {
                throw ValidationException::withMessages([
                    'admission_number'=>'Ownership cannot be verified from matching school enrollments.',
                ]);
            }

            $student->update(['school_id'=>$school->id]);
            $audit->record($school->id,auth()->id(),'academics.student.legacy_claimed',
                'student',$student->id,['enrollment_count'=>$enrollments->count()]);
        });

        return to_route('school-admin.index',$school)->with(
            'success','Student ownership assigned from existing matching enrollments.'
        );
    }
}
