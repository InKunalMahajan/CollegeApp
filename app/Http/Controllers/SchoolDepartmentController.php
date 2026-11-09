<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolDepartment;
use App\Models\SchoolDepartmentMembership;
use App\Models\SchoolMembership;
use App\Services\SchoolAccess;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SchoolDepartmentController extends Controller
{
    private function schoolRole(Request $request, School $school): ?string
    {
        return SchoolMembership::query()->where('school_id', $school->id)
            ->where('user_id', $request->user()->id)->value('role');
    }

    private function isAdmin(Request $request, School $school): bool
    {
        return app(SchoolAccess::class)->can(
            $request->user(), $school, 'school.departments.manage'
        );
    }

    private function authorizedMembership(Request $request, School $school, SchoolDepartment $department): ?SchoolDepartmentMembership
    {
        abort_unless($department->school_id === $school->id, 404);
        if ($this->isAdmin($request, $school)) {
            return null;
        }

        abort_unless($this->schoolRole($request, $school) === 'department_member', 403);

        $membership = SchoolDepartmentMembership::query()
            ->where('school_id', $school->id)
            ->where('school_department_id', $department->id)
            ->where('user_id', $request->user()->id)
            ->whereIn('role', SchoolDepartmentMembership::ROLES)->first();

        abort_unless(
            $membership && app(SchoolAccess::class)->canDepartment(
                $request->user(), $school, $department, 'department.dashboard.view'
            ),
            403
        );

        return $membership;
    }

    public function index(Request $request, School $school): View
    {
        $role = $this->schoolRole($request, $school);
        abort_unless(app(SchoolAccess::class)->can(
            $request->user(), $school, 'school.departments.view'
        ), 403);

        $departments = SchoolDepartment::query()->where('school_id', $school->id)
            ->when($role !== 'school_admin', fn ($query) => $query->whereHas(
                'memberships',
                fn ($members) => $members->where('school_id', $school->id)
                    ->where('user_id', $request->user()->id)
                    ->whereIn('role', SchoolDepartmentMembership::ROLES)
            ))->withCount('memberships')->orderBy('name')->get();

        return view('school-departments.index', [
            'school' => $school,
            'departments' => $departments,
            'canManage' => $role === 'school_admin',
        ]);
    }

    public function show(Request $request, School $school, SchoolDepartment $department): View
    {
        $membership = $this->authorizedMembership($request, $school, $department);
        $admin = $this->isAdmin($request, $school);
        $department->loadCount('memberships');

        $members = $admin
            ? $department->memberships()->with('user:id,name,email')->orderBy('role')->get()
            : collect();

        return view('school-departments.show', [
            'school' => $school,
            'department' => $department,
            'role' => $admin ? 'school_admin' : $membership->role,
            'canManage' => $admin,
            'members' => $members,
        ]);
    }

    public function store(Request $request, School $school): RedirectResponse
    {
        abort_unless($this->isAdmin($request, $school), 403);
        // Department codes have a single canonical form in SQLite and MySQL.
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('school_departments', 'code')->where('school_id', $school->id)],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $department = SchoolDepartment::create([
            ...$data,
            'code' => strtoupper($data['code']),
            'school_id' => $school->id,
        ]);

        $this->audit($school, $request->user()->id, 'department.created', $department->id, ['code' => $department->code]);

        return redirect()->route('school-departments.show', [$school, $department])
            ->with('success', 'Department created.');
    }

    public function update(Request $request, School $school, SchoolDepartment $department): RedirectResponse
    {
        abort_unless((int) $department->school_id === (int) $school->id, 404);
        abort_unless($this->isAdmin($request, $school), 403);

        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('school_departments', 'code')->where('school_id', $school->id)
                    ->ignore($department->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $school, $department, $data) {
            $record = SchoolDepartment::query()->where('school_id', $school->id)
                ->whereKey($department->id)->lockForUpdate()->firstOrFail();
            $record->fill($data);
            $changed = array_keys($record->getDirty());

            if ($changed) {
                $record->save();
                $this->audit($school, $request->user()->id, 'department.updated', $record->id, [
                    'fields' => $changed,
                ]);
            }
        });

        return redirect()->route('school-departments.show', [$school, $department])
            ->with('success', 'Department updated.');
    }

    public function destroy(Request $request, School $school, SchoolDepartment $department): RedirectResponse
    {
        abort_unless((int) $department->school_id === (int) $school->id, 404);
        abort_unless($this->isAdmin($request, $school), 403);

        DB::transaction(function () use ($request, $school, $department) {
            $record = SchoolDepartment::query()->where('school_id', $school->id)
                ->whereKey($department->id)->lockForUpdate()->firstOrFail();

            // Do not remove departments while employee or access records
            // reference them. Foreign keys provide a second line of defence.
            $hasEmployees = \App\Models\SchoolEmployee::withTrashed()
                ->where('school_id', $school->id)
                ->where('school_department_id', $record->id)->exists();
            $hasMembers = SchoolDepartmentMembership::query()
                ->where('school_id', $school->id)->where('school_department_id', $record->id)
                ->exists();

            if ($hasEmployees || $hasMembers) {
                throw ValidationException::withMessages([
                    'department' => 'Remove employee assignments and department member access before deleting.',
                ]);
            }

            $this->audit($school, $request->user()->id, 'department.deleted', $record->id, [
                'code' => $record->code,
            ]);
            $record->delete();
        });

        return redirect()->route('school-departments.index', $school)
            ->with('success', 'Empty department deleted.');
    }

    public function assign(Request $request, School $school, SchoolDepartment $department): RedirectResponse
    {
        abort_unless($department->school_id === $school->id, 404);
        abort_unless($this->isAdmin($request, $school), 403);
        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', Rule::in(SchoolDepartmentMembership::ROLES)],
        ]);
        $user = User::where('email', $data['email'])->firstOrFail();

        DB::transaction(function () use ($request, $user, $school, $department, $data) {
            $schoolMembership = SchoolMembership::query()
                ->where('school_id', $school->id)->where('user_id', $user->id)
                ->lockForUpdate()->first();

            if ($schoolMembership && !in_array($schoolMembership->role, ['department_member', 'school_admin'], true)) {
                throw ValidationException::withMessages(['email' => 'This school member has a different protected role.']);
            }

            // The school administrator retains administrator permissions.
            if (!$schoolMembership) {
                SchoolMembership::create([
                    'school_id' => $school->id,
                    'user_id' => $user->id,
                    'role' => 'department_member',
                ]);
            }

            $assignment = SchoolDepartmentMembership::updateOrCreate([
                'school_department_id' => $department->id,
                'user_id' => $user->id,
            ], [
                'school_id' => $school->id,
                'role' => $data['role'],
            ]);
            $this->audit($school, $request->user()->id, 'department.access.granted', $assignment->id, [
                'department_id' => $department->id,
                'user_id' => $user->id,
                'role' => $data['role'],
            ]);
        });

        return redirect()->route('school-departments.show', [$school, $department])
            ->with('success', 'Department access updated.');
    }

    public function revoke(Request $request, School $school, SchoolDepartment $department, SchoolDepartmentMembership $membership): RedirectResponse
    {
        abort_unless($department->school_id === $school->id, 404);
        abort_unless($this->isAdmin($request, $school), 403);
        abort_unless($membership->school_department_id === $department->id
            && $membership->school_id === $school->id, 404);

        DB::transaction(function () use ($request, $school, $department, $membership) {
            $userId = $membership->user_id;
            $this->audit($school, $request->user()->id, 'department.access.revoked', $membership->id, [
                'department_id' => $department->id,
                'user_id' => $userId,
                'role' => $membership->role,
            ]);
            $membership->delete();
            $remaining = SchoolDepartmentMembership::query()
                ->where('school_id', $school->id)->where('user_id', $userId)->exists();
            if (!$remaining) {
                SchoolMembership::query()->where('school_id', $school->id)
                    ->where('user_id', $userId)->where('role', 'department_member')->delete();
            }
        });

        return redirect()->route('school-departments.show', [$school, $department])
            ->with('success', 'Department access revoked.');
    }
    private function audit(School $school, int $actorId, string $action, int $subjectId, array $context): void
    {
        DB::table('school_audit_events')->insert([
            'school_id' => $school->id,
            'actor_user_id' => $actorId,
            'action' => $action,
            'subject_type' => 'school_department',
            'subject_id' => $subjectId,
            'context' => json_encode($context, JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }

}
