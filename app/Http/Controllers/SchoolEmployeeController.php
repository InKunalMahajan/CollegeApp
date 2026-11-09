<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolDepartment;
use App\Models\SchoolDepartmentMembership;
use App\Models\SchoolEmployee;
use App\Models\SchoolMembership;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SchoolEmployeeController extends Controller
{
    private function schoolRole(Request $request, School $school): ?string
    {
        return SchoolMembership::query()
            ->where('school_id', $school->id)
            ->where('user_id', $request->user()->id)
            ->value('role');
    }

    private function ensureAdmin(Request $request, School $school): void
    {
        abort_unless($this->schoolRole($request, $school) === 'school_admin', 403);
    }

    private function allowedDepartmentIds(Request $request, School $school, string $schoolRole): array
    {
        if ($schoolRole !== 'department_member') {
            return [];
        }

        return SchoolDepartmentMembership::query()
            ->where('school_id', $school->id)
            ->where('user_id', $request->user()->id)
            ->whereIn('role', SchoolDepartmentMembership::ROLES)
            ->pluck('school_department_id')->all();
    }

    private function validateEmployee(Request $request, School $school, ?SchoolEmployee $employee = null): array
    {
        $rules = [
            'employee_number' => [
                'required', 'string', 'max:40',
                Rule::unique('school_employees', 'employee_number')
                    ->where('school_id', $school->id)->ignore($employee?->id),
            ],
            'display_name' => ['required', 'string', 'max:160'],
            'job_title' => ['required', 'string', 'max:160'],
            'department_label' => ['nullable', 'string', 'max:180'],
            'location' => ['nullable', 'string', 'max:100'],
            'school_department_id' => [
                'nullable', 'integer',
                Rule::exists('school_departments', 'id')->where('school_id', $school->id),
            ],
            'manager_id' => [
                'nullable', 'integer',
                Rule::exists('school_employees', 'id')->where('school_id', $school->id),
            ],
        ];

        return $request->validate($rules);
    }

    public function index(Request $request, School $school): View
    {
        $role = $this->schoolRole($request, $school);
        abort_unless(in_array($role, ['school_admin', 'auditor', 'department_member'], true), 403);

        $query = SchoolEmployee::query()->where('school_id', $school->id);
        if ($role === 'department_member') {
            $ids = $this->allowedDepartmentIds($request, $school, $role);
            $query->whereIn('school_department_id', $ids);
        }

        $employees = $query->orderBy('display_name')->get();
        $visibleIds = $employees->pluck('id')->flip();
        $childCounts = $employees->filter(
            fn ($employee) => $employee->manager_id !== null && $visibleIds->has($employee->manager_id)
        )->countBy('manager_id');

        $items = $employees->map(fn ($employee) => [
            'id' => $employee->id,
            'name' => $employee->display_name,
            'number' => $employee->employee_number,
            'title' => $employee->job_title,
            'department' => $employee->department_label ?: ($employee->department?->name ?? 'Unassigned'),
            'location' => $employee->location ?? 'Not specified',
            'manager_id' => $employee->manager_id !== null && $visibleIds->has($employee->manager_id)
                ? $employee->manager_id : null,
            'reportees' => (int) ($childCounts[$employee->id] ?? 0),
        ])->all();

        return view('school-employees.index', [
            'school' => $school,
            'items' => $items,
            'canManage' => $role === 'school_admin',
            'employeeCount' => $employees->count(),
            'departments' => $role === 'school_admin'
                ? SchoolDepartment::where('school_id', $school->id)->orderBy('name')->get()
                : collect(),
            'managers' => $role === 'school_admin'
                ? SchoolEmployee::where('school_id', $school->id)->orderBy('display_name')->get()
                : collect(),
        ]);
    }

    public function store(Request $request, School $school): RedirectResponse
    {
        $this->ensureAdmin($request, $school);
        $data = $this->validateEmployee($request, $school);

        DB::transaction(function () use ($data, $request, $school) {
            $employee = SchoolEmployee::create(['school_id' => $school->id, ...$data]);
            $this->audit($school, $request->user()->id, 'employee.created', $employee->id);
        });

        return redirect()->route('school-employees.index', $school)
            ->with('success', 'Employee added. Reporting manager is assigned only when explicitly selected.');
    }

    public function update(Request $request, School $school, SchoolEmployee $employee): RedirectResponse
    {
        abort_unless($employee->school_id === $school->id, 404);
        $this->ensureAdmin($request, $school);

        $data = $this->validateEmployee($request, $school, $employee);
        if (isset($data['manager_id'])) {
            $managerId = (int) $data['manager_id'];
            if ($managerId === $employee->id) {
                throw ValidationException::withMessages(['manager_id' => 'An employee cannot report to themselves.']);
            }
            $visited = [];
            while ($managerId) {
                if (isset($visited[$managerId]) || $managerId === $employee->id) {
                    throw ValidationException::withMessages(['manager_id' => 'Reporting lines cannot form a cycle.']);
                }
                $visited[$managerId] = true;
                $managerId = (int) (SchoolEmployee::where('school_id', $school->id)
                    ->where('id', $managerId)->value('manager_id') ?? 0);
            }
        }

        DB::transaction(function () use ($employee, $data, $request, $school) {
            $employee->update($data);
            $this->audit($school, $request->user()->id, 'employee.updated', $employee->id);
        });

        return redirect()->route('school-employees.index', $school)
            ->with('success', 'Employee updated.');
    }

    private function audit(School $school, int $actorId, string $action, int $employeeId): void
    {
        DB::table('school_audit_events')->insert([
            'school_id' => $school->id,
            'actor_user_id' => $actorId,
            'action' => $action,
            'subject_type' => 'school_employee',
            'subject_id' => $employeeId,
            'context' => json_encode(['employee_id' => $employeeId], JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }
}
