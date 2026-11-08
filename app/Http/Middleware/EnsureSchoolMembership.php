<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Models\SchoolMembership;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolMembership
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $school = $request->route('school');
        abort_unless($school instanceof School && $request->user(), 403);
        $membership = SchoolMembership::where('school_id', $school->id)
            ->where('user_id', $request->user()->id)->first();

        abort_unless($membership && in_array($membership->role, $roles, true), 403);

        return $next($request);
    }
}
