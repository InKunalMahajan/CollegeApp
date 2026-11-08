<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Department;
use App\Models\Student;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $legacy = config('school_security.legacy_global_routes_enabled', false);
        $staff = $legacy && auth()->user()->isStaff();

        return view('dashboard', [
            'canViewStudents' => $staff,
            'students' => $staff ? Student::count() : null,
            'active' => $staff ? Student::where('status', 'active')->count() : null,
            'courses' => $legacy ? Course::count() : 0,
            'departments' => $legacy ? Department::count() : 0,
            'recent' => $staff ? Student::with('course')->latest()->take(5)->get() : collect(),
        ]);
    }
}
