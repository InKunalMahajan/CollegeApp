<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureCampusPermission;
use App\Http\Middleware\EnsureSchoolPermission;
use App\Http\Middleware\EnsureDepartmentPermission;
use App\Http\Middleware\EnsureSchoolMembership;
use App\Http\Middleware\EnsureLegacyGlobalAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['role' => EnsureRole::class, 'school.member' => EnsureSchoolMembership::class, 'legacy.global' => EnsureLegacyGlobalAccess::class, 'school.can' => EnsureSchoolPermission::class, 'department.can' => EnsureDepartmentPermission::class, 'campus.can' => EnsureCampusPermission::class]);
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
