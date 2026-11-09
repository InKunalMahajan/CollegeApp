<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolDepartment;
use App\Models\SchoolDepartmentMembership;
use App\Models\SchoolMembership;
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
        return $this->schoolRole($request, $school) === 'school_admin';
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

        abort_unless($membership, 403);

        return $membership;
    }

    public function index(Request $request, School $school): View
    {
        $role = $this->schoolRole($request, $school);
        abort_unless(in_array($role, ['school_admin', 'department_member'], true), 403);

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

        return redirect()->route('school-departments.show', [$school, $department])
            ->with('success', 'Department created.');
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

        DB::transaction(function () use ($user, $school, $department, $data) {
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

            SchoolDepartmentMembership::updateOrCreate([
                'school_department_id' => $department->id,
                'user_id' => $user->id,
            ], [
                'school_id' => $school->id,
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

        DB::transaction(function () use ($school, $membership) {
            $userId = $membership->user_id;
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
}
