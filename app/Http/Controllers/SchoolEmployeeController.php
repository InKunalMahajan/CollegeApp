<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolDepartment;
use App\Models\SchoolDepartmentMembership;
use App\Models\SchoolEmployee;
use App\Models\SchoolMembership;
use App\Services\SchoolAccess;
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
        abort_unless(app(SchoolAccess::class)->can(
            $request->user(), $school, 'school.employees.manage'
        ), 403);
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
            'employment_status' => ['sometimes', 'required', Rule::in(SchoolEmployee::STATUSES)],
            'employment_type' => ['sometimes', 'nullable', Rule::in(SchoolEmployee::TYPES)],
            'joined_on' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'work_email' => [
                'sometimes', 'nullable', 'email', 'max:255',
                Rule::unique('school_employees', 'work_email')
                    ->where('school_id', $school->id)->ignore($employee?->id),
            ],
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
        abort_unless(app(SchoolAccess::class)->can(
            $request->user(), $school, 'school.employees.view'
        ), 403);

        $query = SchoolEmployee::query()->where('school_id', $school->id);
        if ($role === 'department_member') {
            $ids = $this->allowedDepartmentIds($request, $school, $role);
            $query->whereIn('school_department_id', $ids);
        }

        $filters = $request->validate([
            'employment_status' => ['nullable', Rule::in(SchoolEmployee::STATUSES)],
        ]);
        $statusFilter = $filters['employment_status'] ?? '';
        $query->when($statusFilter !== '', fn ($q) => $q->where('employment_status', $statusFilter));

        $employees = $query->with('department')->orderBy('display_name')->get();
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
            'status' => $employee->employment_status,
        ])->all();

        return view('school-employees.index', [
            'school' => $school,
            'items' => $items,
            'canManage' => $role === 'school_admin',
            'employeeCount' => $employees->count(),
            'statusFilter' => $statusFilter,
            'statusCounts' => $employees->countBy('employment_status'),
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
            $this->audit($school, $request->user()->id, 'employee.created', $employee->id, [
                'fields' => array_keys($data),
            ]);
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
            $employee->fill($data);
            $fields = array_keys($employee->getDirty());
            if ($fields === []) {
                return;
            }
            $employee->save();
            $this->audit($school, $request->user()->id, 'employee.updated', $employee->id, [
                'fields' => $fields,
            ]);
        });

        return redirect()->route('school-employees.index', $school)
            ->with('success', 'Employee updated.');
    }


    public function import(Request $request, School $school): RedirectResponse
    {
        abort_unless(app(SchoolAccess::class)->can(
            $request->user(), $school, 'school.employees.import'
        ), 403);
        $data = $request->validate([
            'employee_csv' => ['required', 'file', 'max:2048'],
        ]);

        $file = $data['employee_csv'];
        $handle = fopen($file->getRealPath(), 'rb');
        if (!$handle) {
            throw ValidationException::withMessages(['employee_csv' => 'CSV could not be opened.']);
        }

        try {
            $header = fgetcsv($handle);
            if (!is_array($header)) {
                throw ValidationException::withMessages(['employee_csv' => 'CSV is empty.']);
            }
            $header[0] = preg_replace('/^\\xEF\\xBB\\xBF/', '', $header[0]);
            $expected = [
                'Employee Number', 'Display Name', 'Job Title',
                'Department', 'Location', 'Reportees Count',
            ];
            if ($header !== $expected) {
                throw ValidationException::withMessages([
                    'employee_csv' => 'CSV headers must match the six OrgTree columns exactly.',
                ]);
            }

            $rows = [];
            $seen = [];
            while (($row = fgetcsv($handle)) !== false) {
                if ($row === [null]) {
                    continue;
                }
                if (count($row) !== 6 || count($rows) >= 1000) {
                    throw ValidationException::withMessages([
                        'employee_csv' => 'CSV rows must have six columns; maximum 1000 employees.',
                    ]);
                }

                [$number, $name, $title, $department, $location, $reportees] = array_map('trim', $row);
                if ($number === '' || $name === '' || $title === ''
                    || mb_strlen($number) > 40 || mb_strlen($name) > 160
                    || mb_strlen($title) > 160 || mb_strlen($department) > 180
                    || mb_strlen($location) > 100
                    || filter_var($reportees, FILTER_VALIDATE_INT) === false
                    || (int) $reportees < 0) {
                    throw ValidationException::withMessages([
                        'employee_csv' => 'CSV contains an invalid employee row.',
                    ]);
                }

                if (isset($seen[$number])) {
                    throw ValidationException::withMessages([
                        'employee_csv' => 'Duplicate employee numbers in the CSV.',
                    ]);
                }
                $seen[$number] = true;
                $rows[] = [
                    'school_id' => $school->id,
                    'employee_number' => $number,
                    'display_name' => $name,
                    'job_title' => $title,
                    'department_label' => $department ?: null,
                    'location' => $location ?: null,
                    // Reportees count is not a manager ID: never invent reporting lines.
                    'manager_id' => null,
                ];
            }

            if (!$rows) {
                throw ValidationException::withMessages(['employee_csv' => 'CSV has no employee rows.']);
            }
        } finally {
            fclose($handle);
        }

        DB::transaction(function () use ($rows, $school, $request) {
            foreach ($rows as $row) {
                $existing = SchoolEmployee::query()
                    ->where('school_id', $school->id)
                    ->where('employee_number', $row['employee_number'])->first();
                if ($existing) {
                    // Keep confirmed manager relationships and assigned department links.
                    $existing->update(collect($row)->except(['school_id', 'manager_id'])->all());
                } else {
                    $existing = SchoolEmployee::create($row);
                }
                $this->audit($school, $request->user()->id, 'employee.imported', $existing->id);
            }
        });

        return redirect()->route('school-employees.index', $school)
            ->with('success', count($rows).' employee records imported. Reporting lines remain unassigned until verified.');
    }

    private function audit(School $school, int $actorId, string $action, int $employeeId, array $context = []): void
    {
        DB::table('school_audit_events')->insert([
            'school_id' => $school->id,
            'actor_user_id' => $actorId,
            'action' => $action,
            'subject_type' => 'school_employee',
            'subject_id' => $employeeId,
            'context' => json_encode(['employee_id' => $employeeId, ...$context], JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }
}
