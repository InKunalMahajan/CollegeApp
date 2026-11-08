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
        return view('dashboard', [
            'students' => Student::count(),
            'active' => Student::where('status', 'active')->count(),
            'courses' => Course::count(),
            'departments' => Department::count(),
            'recent' => Student::with('course')->latest()->take(5)->get(),
        ]);
    }
}
