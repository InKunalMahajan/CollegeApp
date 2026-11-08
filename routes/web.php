<?php

use App\Http\Controllers\AcademicSetupController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModuleCatalogController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherAssignmentController;
use App\Http\Controllers\MyTeachingController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('students', StudentController::class)
        ->except('show')
        ->middleware('role:admin,staff');

    Route::get('/my-timetable', [TimetableController::class, 'mine'])->middleware('role:teacher')->name('timetables.mine');

    Route::get('/my-teaching', MyTeachingController::class)
        ->middleware('role:teacher')->name('my-teaching');

    Route::middleware('role:admin')->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance/enroll', [AttendanceController::class, 'enroll'])->name('attendance.enroll');
        Route::post('/attendance/student', [AttendanceController::class, 'storeStudent'])->name('attendance.student.store');
        Route::post('/attendance/staff', [AttendanceController::class, 'storeStaff'])->name('attendance.staff.store');
        Route::get('/timetables', [TimetableController::class, 'index'])->name('timetables.index');
        Route::post('/timetables', [TimetableController::class, 'store'])->name('timetables.store');
        Route::delete('/timetables/{entry}', [TimetableController::class, 'destroy'])->name('timetables.destroy');
        Route::resource('teachers', TeacherController::class)->except(['show', 'destroy']);
        Route::get('/teacher-assignments', [TeacherAssignmentController::class, 'index'])->name('teacher-assignments.index');
        Route::post('/teacher-assignments/subjects', [TeacherAssignmentController::class, 'storeSubject'])->name('teacher-assignments.subject.store');
        Route::delete('/teacher-assignments/subjects/{assignment}', [TeacherAssignmentController::class, 'destroySubject'])->name('teacher-assignments.subject.destroy');
        Route::post('/teacher-assignments/classes', [TeacherAssignmentController::class, 'storeClass'])->name('teacher-assignments.class.store');
        Route::delete('/teacher-assignments/classes/{assignment}', [TeacherAssignmentController::class, 'destroyClass'])->name('teacher-assignments.class.destroy');
        Route::get('/setup/{module}', [AcademicSetupController::class, 'index'])->name('setup.index');
        Route::get('/setup/{module}/create', [AcademicSetupController::class, 'create'])->name('setup.create');
        Route::post('/setup/{module}', [AcademicSetupController::class, 'store'])->name('setup.store');
        Route::get('/setup/{module}/{record}/edit', [AcademicSetupController::class, 'edit'])->name('setup.edit');
        Route::put('/setup/{module}/{record}', [AcademicSetupController::class, 'update'])->name('setup.update');
        Route::delete('/setup/{module}/{record}', [AcademicSetupController::class, 'destroy'])->name('setup.destroy');
        Route::get('/modules', ModuleCatalogController::class)->name('modules.index');
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
    });
});
