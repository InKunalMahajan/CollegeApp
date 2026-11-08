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
        $staff = auth()->user()->isStaff();

        return view('dashboard', [
            'canViewStudents' => $staff,
            'students' => $staff ? Student::count() : null,
            'active' => $staff ? Student::where('status', 'active')->count() : null,
            'courses' => Course::count(),
            'departments' => Department::count(),
            'recent' => $staff ? Student::with('course')->latest()->take(5)->get() : collect(),
        ]);
    }
}
