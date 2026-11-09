<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\School;
use App\Models\SchoolEmployee;
use App\Models\Student;
use App\Services\SchoolAuditTrail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CampusWorkspaceController extends Controller
{
    public function index(Request $request, School $school, Campus $campus): View
    {
        $employees = SchoolEmployee::query()->where('school_id', $school->id)
            ->where('campus_id', $campus->id)->orderBy('display_name')
            ->paginate(15, ['*'], 'employees_page')->withQueryString();
        $students = Student::query()->where('school_id', $school->id)
            ->where('campus_id', $campus->id)->orderBy('first_name')
            ->paginate(15, ['*'], 'students_page')->withQueryString();
        $archived = SchoolEmployee::onlyTrashed()->where('school_id', $school->id)
            ->where('campus_id', $campus->id)->orderBy('display_name')->limit(50)->get();

        return view('school-campuses.show', compact('school', 'campus', 'employees', 'students', 'archived'));
    }

    private function employee(School $school, Campus $campus, SchoolEmployee $employee): SchoolEmployee
    {
        abort_unless((int) $employee->school_id === (int) $school->id
            && (int) $employee->campus_id === (int) $campus->id, 404);

        return $employee;
    }

    private function student(School $school, Campus $campus, Student $student): Student
    {
        abort_unless((int) $student->school_id === (int) $school->id
            && (int) $student->campus_id === (int) $campus->id, 404);

        return $student;
    }

    private function employeeData(Request $request, School $school, ?SchoolEmployee $employee = null): array
    {
        return $request->validate([
            'employee_number' => ['required', 'string', 'max:40',
                Rule::unique('school_employees', 'employee_number')
                    ->where('school_id', $school->id)->ignore($employee?->id)],
            'display_name' => ['required', 'string', 'max:160'],
            'job_title' => ['required', 'string', 'max:160'],
            'location' => ['nullable', 'string', 'max:100'],
            'employment_status' => ['required', Rule::in(SchoolEmployee::STATUSES)],
            'work_email' => ['nullable', 'email', 'max:255',
                Rule::unique('school_employees', 'work_email')
                    ->where('school_id', $school->id)->ignore($employee?->id)],
        ]);
    }

    private function studentData(Request $request, ?Student $student = null): array
    {
        return $request->validate([
            'admission_number' => ['required', 'string', 'max:40',
                Rule::unique('students', 'admission_number')->ignore($student?->id)],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255',
                Rule::unique('students', 'email')->ignore($student?->id)],
            'admission_date' => ['required', 'date_format:Y-m-d'],
            'status' => ['required', Rule::in(['active', 'inactive', 'graduated'])],
        ]);
    }

    public function createEmployee(
        Request $request, School $school, Campus $campus, SchoolAuditTrail $audit
    ): RedirectResponse {
        $data = $this->employeeData($request, $school);
        DB::transaction(function () use ($request, $school, $campus, $data, $audit) {
            $employee = SchoolEmployee::create([
                'school_id' => $school->id, 'campus_id' => $campus->id, ...$data,
            ]);
            $audit->record($school->id, $request->user()->id, 'campus.employee.created',
                'school_employee', $employee->id, ['campus_id' => $campus->id]);
        });

        return to_route('campus-workspace.index', [$school, $campus])->with('success', 'Employee created.');
    }

    public function updateEmployee(
        Request $request, School $school, Campus $campus,
        SchoolEmployee $employee, SchoolAuditTrail $audit
    ): RedirectResponse {
        $this->employee($school, $campus, $employee);
        $data = $this->employeeData($request, $school, $employee);
        DB::transaction(function () use ($request, $school, $campus, $employee, $data, $audit) {
            $record = SchoolEmployee::where('school_id', $school->id)
                ->where('campus_id', $campus->id)
                ->whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $record->fill($data);
            $fields = array_keys($record->getDirty());
            if ($fields) {
                $record->save();
                $audit->record($school->id, $request->user()->id, 'campus.employee.updated',
                    'school_employee', $record->id, ['campus_id' => $campus->id, 'fields' => $fields]);
            }
        });

        return to_route('campus-workspace.index', [$school, $campus])->with('success', 'Employee updated.');
    }

    public function archiveEmployee(
        Request $request, School $school, Campus $campus,
        SchoolEmployee $employee, SchoolAuditTrail $audit
    ): RedirectResponse {
        $this->employee($school, $campus, $employee);
        DB::transaction(function () use ($request, $school, $campus, $employee, $audit) {
            $record = SchoolEmployee::where('school_id', $school->id)
                ->where('campus_id', $campus->id)
                ->whereKey($employee->id)->lockForUpdate()->firstOrFail();
            if (SchoolEmployee::query()->where('school_id', $school->id)
                ->where('manager_id', $record->id)->exists()) {
                throw ValidationException::withMessages([
                    'employee' => 'Reassign direct reportees before archiving this employee.',
                ]);
            }
            $record->delete();
            $audit->record($school->id, $request->user()->id, 'campus.employee.archived',
                'school_employee', $record->id, ['campus_id' => $campus->id]);
        });

        return to_route('campus-workspace.index', [$school, $campus])->with('success', 'Employee archived.');
    }

    public function restoreEmployee(
        Request $request, School $school, Campus $campus,
        int $employee, SchoolAuditTrail $audit
    ): RedirectResponse {
        DB::transaction(function () use ($request, $school, $campus, $employee, $audit) {
            $record = SchoolEmployee::onlyTrashed()
                ->where('school_id', $school->id)
                ->where('campus_id', $campus->id)
                ->whereKey($employee)->lockForUpdate()->firstOrFail();
            $record->restore();
            $audit->record($school->id, $request->user()->id, 'campus.employee.restored',
                'school_employee', $record->id, ['campus_id' => $campus->id]);
        });

        return to_route('campus-workspace.index', [$school, $campus])->with('success', 'Employee restored.');
    }

    public function createStudent(
        Request $request, School $school, Campus $campus, SchoolAuditTrail $audit
    ): RedirectResponse {
        $data = $this->studentData($request);
        DB::transaction(function () use ($request, $school, $campus, $data, $audit) {
            $student = Student::create([
                'school_id' => $school->id, 'campus_id' => $campus->id, ...$data,
            ]);
            $audit->record($school->id, $request->user()->id, 'campus.student.admitted',
                'student', $student->id, ['campus_id' => $campus->id]);
        });

        return to_route('campus-workspace.index', [$school, $campus])->with('success', 'Student admitted.');
    }

    public function updateStudent(
        Request $request, School $school, Campus $campus,
        Student $student, SchoolAuditTrail $audit
    ): RedirectResponse {
        $this->student($school, $campus, $student);
        $data = $this->studentData($request, $student);
        DB::transaction(function () use ($request, $school, $campus, $student, $data, $audit) {
            $record = Student::where('school_id', $school->id)
                ->where('campus_id', $campus->id)
                ->whereKey($student->id)->lockForUpdate()->firstOrFail();
            $record->fill($data);
            $fields = array_keys($record->getDirty());
            if ($fields) {
                $record->save();
                $audit->record($school->id, $request->user()->id, 'campus.student.updated',
                    'student', $record->id, ['campus_id' => $campus->id, 'fields' => $fields]);
            }
        });

        return to_route('campus-workspace.index', [$school, $campus])->with('success', 'Student updated.');
    }

    public function deactivateStudent(
        Request $request, School $school, Campus $campus,
        Student $student, SchoolAuditTrail $audit
    ): RedirectResponse {
        $this->student($school, $campus, $student);
        DB::transaction(function () use ($request, $school, $campus, $student, $audit) {
            $record = Student::where('school_id', $school->id)
                ->where('campus_id', $campus->id)
                ->whereKey($student->id)->lockForUpdate()->firstOrFail();
            $record->update(['status' => 'inactive']);
            $audit->record($school->id, $request->user()->id, 'campus.student.deactivated',
                'student', $record->id, ['campus_id' => $campus->id]);
        });

        return to_route('campus-workspace.index', [$school, $campus])->with('success', 'Student deactivated; history retained.');
    }
}
