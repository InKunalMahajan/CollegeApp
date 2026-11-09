<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Services\SchoolAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolPermission
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $school = $request->route('school');

        abort_unless(
            $school instanceof School
            && $request->user()
            && app(SchoolAccess::class)->can($request->user(), $school, $ability),
            403
        );

        return $next($request);
    }
}
