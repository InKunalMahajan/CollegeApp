<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Models\SchoolDepartment;
use App\Services\SchoolAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDepartmentPermission
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $school = $request->route('school');
        $department = $request->route('department');

        abort_unless(
            $school instanceof School
            && $department instanceof SchoolDepartment
            && $request->user(),
            403
        );

        abort_unless($department->school_id === $school->id, 404);

        abort_unless(
            app(SchoolAccess::class)->canDepartment(
                $request->user(), $school, $department, $ability
            ),
            403
        );

        return $next($request);
    }
}
