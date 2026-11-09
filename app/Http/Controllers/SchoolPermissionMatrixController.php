<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Contracts\View\View;

class SchoolPermissionMatrixController extends Controller
{
    public function __invoke(School $school): View
    {
        return view('school-admin.permissions', [
            'school' => $school,
            'abilities' => config('school_permissions.abilities', []),
            'schoolRoles' => config('school_permissions.school_roles', []),
            'departmentAbilities' => config('school_permissions.department_abilities', []),
            'departmentRoles' => config('school_permissions.department_roles', []),
        ]);
    }
}
