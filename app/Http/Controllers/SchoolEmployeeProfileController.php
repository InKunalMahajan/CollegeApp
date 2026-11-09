<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolEmployee;
use App\Models\SchoolAuditEvent;
use App\Services\SchoolAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SchoolEmployeeProfileController extends Controller
{
    public function show(Request $request, School $school, SchoolEmployee $employee): View
    {
        abort_unless((int) $employee->school_id === (int) $school->id, 404);

        $access = app(SchoolAccess::class);
        abort_unless($access->can($request->user(), $school, 'school.employees.view'), 403);

        $canManage = $access->can($request->user(), $school, 'school.employees.manage');
        if (! $canManage && ! $access->can($request->user(), $school, 'school.security.view')) {
            $departments = $access->assignedDepartmentIds($request->user(), $school);
            abort_unless($employee->school_department_id !== null
                && in_array($employee->school_department_id, $departments), 403);
        }

        $employee->load(['department', 'manager']);
        // Department-only viewers must not see an unrelated manager's identity.
        if (! $canManage && ! $access->can($request->user(), $school, 'school.security.view')
            && $employee->manager
            && ! in_array($employee->manager->school_department_id,
                $access->assignedDepartmentIds($request->user(), $school), true)) {
            $employee->setRelation('manager', null);
        }
        $history = $canManage
            ? SchoolAuditEvent::query()
                ->where('school_id', $school->id)
                ->where('subject_type', 'school_employee')
                ->where('subject_id', $employee->id)
                ->latest('id')->limit(25)->get()
            : collect();

        return view('school-employees.show', compact('school', 'employee', 'canManage', 'history'));
    }
}
