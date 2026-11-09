<?php

namespace App\Http\Middleware;

use App\Models\Campus;
use App\Models\School;
use App\Services\CampusAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCampusPermission
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $school = $request->route('school');
        $campus = $request->route('campus');

        abort_unless($school instanceof School && $campus instanceof Campus && $request->user(), 403);

        // A campus under a different organization is not a child resource.
        abort_unless((int) $campus->school_id === (int) $school->id, 404);

        abort_unless(app(CampusAccess::class)->can($request->user(), $school, $campus, $ability), 403);

        return $next($request);
    }
}
