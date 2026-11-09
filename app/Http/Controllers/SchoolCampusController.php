<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\CampusMembership;
use App\Models\ClassEnrollment;
use App\Models\School;
use App\Models\SchoolEmployee;
use App\Models\Student;
use App\Models\User;
use App\Services\CampusAccess;
use App\Services\SchoolAuditTrail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SchoolCampusController extends Controller
{
    public function index(Request $request, School $school): View
    {
        $this->requireOrganizationAdmin($request, $school);
        $campuses = Campus::where('school_id', $school->id)
            ->with(['school'])->orderBy('name')->get();
        $memberships = CampusMembership::query()
            ->whereIn('campus_id', $campuses->pluck('id'))
            ->with(['user:id,name,email', 'campus:id,name,school_id'])
            ->orderBy('campus_id')->get();
        $employees = SchoolEmployee::where('school_id', $school->id)
            ->whereNull('campus_id')->orderBy('display_name')->limit(100)->get();
        $students = Student::where('school_id', $school->id)
            ->whereNull('campus_id')->orderBy('first_name')->limit(100)->get();

        return view('school-campuses.index', compact(
            'school', 'campuses', 'memberships', 'employees', 'students'
        ));
    }

    private function requireOrganizationAdmin(Request $request, School $school): void
    {
        abort_unless($request->user()
            && app(CampusAccess::class)->isOrganizationAdmin($request->user(), $school), 403);
    }

    private function owned(School $school, Campus $campus): void
    {
        abort_unless((int) $campus->school_id === (int) $school->id, 404);
    }

    public function store(Request $request, School $school, SchoolAuditTrail $audit): RedirectResponse
    {
        $this->requireOrganizationAdmin($request, $school);
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('campuses', 'code')->where('school_id', $school->id)],
            'city' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($request, $school, $data, $audit) {
            $campus = Campus::create(['school_id' => $school->id, ...$data]);
            $audit->record($school->id, $request->user()->id, 'campus.created',
                'campus', $campus->id, ['fields' => array_keys($data)]);
        });

        return to_route('school-campuses.index', $school)->with('success', 'Campus created.');
    }

    public function update(
        Request $request, School $school, Campus $campus, SchoolAuditTrail $audit
    ): RedirectResponse {
        $this->owned($school, $campus);
        $this->requireOrganizationAdmin($request, $school);
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('campuses', 'code')->where('school_id', $school->id)
                    ->ignore($campus->id)],
            'city' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($request, $school, $campus, $data, $audit) {
            $record = Campus::query()->where('school_id', $school->id)
                ->whereKey($campus->id)->lockForUpdate()->firstOrFail();
            $record->fill($data);
            $fields = array_keys($record->getDirty());
            if ($fields) {
                $record->save();
                $audit->record($school->id, $request->user()->id, 'campus.updated',
                    'campus', $record->id, ['fields' => $fields]);
            }
        });

        return to_route('school-campuses.index', $school)->with('success', 'Campus updated.');
    }

    public function destroy(
        Request $request, School $school, Campus $campus, SchoolAuditTrail $audit
    ): RedirectResponse {
        $this->owned($school, $campus);
        $this->requireOrganizationAdmin($request, $school);

        DB::transaction(function () use ($request, $school, $campus, $audit) {
            $record = Campus::query()->where('school_id', $school->id)
                ->whereKey($campus->id)->lockForUpdate()->firstOrFail();

            // SchoolApp campus records can be referenced by historical
            // academics, staff and access links; deletion is for empty
            // campuses only, never as a bulk record-removal operation.
            $references = CampusMembership::where('campus_id', $record->id)->exists()
                || SchoolEmployee::withTrashed()->where('campus_id', $record->id)->exists()
                || Student::where('campus_id', $record->id)->exists()
                || DB::table('class_sections')->where('campus_id', $record->id)->exists()
                || DB::table('teacher_profiles')->where('campus_id', $record->id)->exists();
            if ($references) {
                throw ValidationException::withMessages([
                    'campus' => 'This campus has members, employees, students, classes or teachers. '
                        .'Remove or transfer those references through approved workflows first.',
                ]);
            }

            $audit->record($school->id, $request->user()->id, 'campus.deleted',
                'campus', $record->id, ['code' => $record->code]);
            $record->delete();
        });

        return to_route('school-campuses.index', $school)->with('success', 'Empty campus deleted.');
    }

    public function grant(Request $request, School $school, Campus $campus, SchoolAuditTrail $audit): RedirectResponse
    {
        $this->owned($school, $campus);
        $this->requireOrganizationAdmin($request, $school);

        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $user = User::where('email', $data['email'])->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'No eligible existing account was found.',
            ]);
        }

        DB::transaction(function () use ($request, $school, $campus, $user, $audit) {
            // Serialize concurrent grants to different campuses for the same
            // user, so both admins cannot pass the one-campus check at once.
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            // Keep campus administrators limited to one campus of a given
            // organization unless separately elevated by approved governance.
            $other = CampusMembership::query()->where('user_id', $user->id)
                ->where('campus_id', '<>', $campus->id)
                ->whereHas('campus', fn ($q) => $q->where('school_id', $school->id))
                ->exists();

            if ($other) {
                throw ValidationException::withMessages([
                    'email' => 'This account already administers another campus of this organization.',
                ]);
            }

            $membership = CampusMembership::updateOrCreate([
                'campus_id' => $campus->id, 'user_id' => $user->id,
            ], ['role' => 'campus_admin']);

            $audit->record($school->id, $request->user()->id, 'campus.admin.granted',
                'campus_membership', $membership->id,
                ['campus_id' => $campus->id, 'user_id' => $user->id]);
        });

        return to_route('school-campuses.index', $school)
            ->with('success', 'Campus administrator access saved.');
    }

    public function revoke(
        Request $request, School $school, Campus $campus,
        CampusMembership $membership, SchoolAuditTrail $audit
    ): RedirectResponse {
        $this->owned($school, $campus);
        $this->requireOrganizationAdmin($request, $school);
        abort_unless((int) $membership->campus_id === (int) $campus->id, 404);

        DB::transaction(function () use ($request, $school, $campus, $membership, $audit) {
            $record = CampusMembership::query()->where('campus_id', $campus->id)
                ->whereKey($membership->id)->lockForUpdate()->firstOrFail();
            $audit->record($school->id, $request->user()->id, 'campus.admin.revoked',
                'campus_membership', $record->id,
                ['campus_id' => $campus->id, 'user_id' => $record->user_id]);
            $record->delete();
        });

        return to_route('school-campuses.index', $school)->with('success', 'Access revoked.');
    }

    public function assignEmployee(
        Request $request, School $school, Campus $campus,
        SchoolEmployee $employee, SchoolAuditTrail $audit
    ): RedirectResponse {
        $this->owned($school, $campus);
        $this->requireOrganizationAdmin($request, $school);
        abort_unless((int) $employee->school_id === (int) $school->id, 404);

        DB::transaction(function () use ($request, $school, $campus, $employee, $audit) {
            $record = SchoolEmployee::query()->where('school_id', $school->id)
                ->whereKey($employee->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->campus_id === null || (int) $record->campus_id === (int) $campus->id, 422);

            // Do not create manager/reportee links across assigned campuses.
            if ($record->manager_id
                && SchoolEmployee::query()->whereKey($record->manager_id)
                    ->whereNotNull('campus_id')->where('campus_id', '<>', $campus->id)->exists()) {
                throw ValidationException::withMessages([
                    'employee' => 'The assigned manager belongs to another campus.',
                ]);
            }
            if (SchoolEmployee::query()->where('school_id', $school->id)
                ->where('manager_id', $record->id)->whereNotNull('campus_id')
                ->where('campus_id', '<>', $campus->id)->exists()) {
                throw ValidationException::withMessages([
                    'employee' => 'A direct reportee belongs to another campus.',
                ]);
            }

            $record->update(['campus_id' => $campus->id]);
            $audit->record($school->id, $request->user()->id, 'campus.employee.assigned',
                'school_employee', $record->id, ['campus_id' => $campus->id]);
        });

        return to_route('school-campuses.index', $school)->with('success', 'Employee assigned.');
    }

    public function assignStudent(
        Request $request, School $school, Campus $campus,
        Student $student, SchoolAuditTrail $audit
    ): RedirectResponse {
        $this->owned($school, $campus);
        $this->requireOrganizationAdmin($request, $school);
        abort_unless((int) $student->school_id === (int) $school->id, 404);

        DB::transaction(function () use ($request, $school, $campus, $student, $audit) {
            $record = Student::query()->where('school_id', $school->id)
                ->whereKey($student->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->campus_id === null || (int) $record->campus_id === (int) $campus->id, 422);

            if (ClassEnrollment::query()->where('student_id', $record->id)
                ->whereHas('classSection', fn ($q) => $q->where('campus_id', '<>', $campus->id))
                ->exists()) {
                throw ValidationException::withMessages([
                    'student' => 'Existing enrollment belongs to a different campus.',
                ]);
            }

            $record->update(['campus_id' => $campus->id]);
            $audit->record($school->id, $request->user()->id, 'campus.student.assigned',
                'student', $record->id, ['campus_id' => $campus->id]);
        });

        return to_route('school-campuses.index', $school)->with('success', 'Student assigned.');
    }
}
