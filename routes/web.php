<?php

use App\Http\Controllers\AcademicSetupController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\SchoolAssignmentController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExaminationController;
use App\Http\Controllers\CurriculumController;
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

    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');

    Route::middleware('role:teacher')->group(function () {
        Route::get('/teaching-assignments', [SchoolAssignmentController::class, 'teacherIndex'])->name('assignments.teacher');
        Route::post('/teaching-assignments', [SchoolAssignmentController::class, 'store'])->name('assignments.store');
        Route::post('/teaching-assignments/{assignment}/publish', [SchoolAssignmentController::class, 'publish'])->name('assignments.publish');
        Route::get('/teaching-assignments/{assignment}/submissions', [SchoolAssignmentController::class, 'submissions'])->name('assignments.submissions');
        Route::post('/teaching-assignments/submissions/{submission}/grade', [SchoolAssignmentController::class, 'grade'])->name('assignments.grade');
    });

    Route::middleware('role:student')->group(function () {
        Route::get('/my-assignments', [SchoolAssignmentController::class, 'studentIndex'])->name('assignments.student');
        Route::post('/my-assignments/{assignment}/submit', [SchoolAssignmentController::class, 'submit'])->name('assignments.submit');
    });

    Route::resource('students', StudentController::class)
        ->except('show')
        ->middleware('role:admin,staff');

    Route::get('/my-timetable', [TimetableController::class, 'mine'])->middleware('role:teacher')->name('timetables.mine');

    Route::get('/my-teaching', MyTeachingController::class)
        ->middleware('role:teacher')->name('my-teaching');

    Route::middleware('role:teacher')->group(function () {
        Route::get('/my-curriculum', [CurriculumController::class, 'myProgress'])->name('curriculum.mine');
        Route::post('/my-curriculum/progress', [CurriculumController::class, 'storeProgress'])->name('curriculum.progress.store');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('/fees', [FeeController::class, 'index'])->name('fees.index');
        Route::post('/fees/structures', [FeeController::class, 'storeStructure'])->name('fees.structures.store');
        Route::post('/fees/structures/{structure}/issue', [FeeController::class, 'issue'])->name('fees.structures.issue');
        Route::post('/fees/charges/{charge}/payments', [FeeController::class, 'recordPayment'])->name('fees.payments.store');
        Route::get('/fees/receipts/{payment}', [FeeController::class, 'receipt'])->name('fees.receipt');

        Route::get('/library', [LibraryController::class, 'index'])->name('library.index');
        Route::post('/library/books', [LibraryController::class, 'storeBook'])->name('library.books.store');
        Route::post('/library/copies', [LibraryController::class, 'storeCopy'])->name('library.copies.store');
        Route::post('/library/loans', [LibraryController::class, 'issue'])->name('library.loans.store');
        Route::post('/library/loans/{loan}/return', [LibraryController::class, 'returnBook'])->name('library.loans.return');
        Route::get('/student-links', [SchoolAssignmentController::class, 'studentLinks'])->name('student-links.index');
        Route::post('/student-links', [SchoolAssignmentController::class, 'linkStudent'])->name('student-links.store');
        Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::post('/announcements/{announcement}/publish', [AnnouncementController::class, 'publish'])->name('announcements.publish');
        Route::get('/exams', [ExaminationController::class, 'index'])->name('exams.index');
        Route::post('/exams', [ExaminationController::class, 'store'])->name('exams.store');
        Route::get('/exams/{exam}/results', [ExaminationController::class, 'results'])->name('exams.results');
        Route::post('/exams/{exam}/results', [ExaminationController::class, 'storeResult'])->name('exams.results.store');
        Route::get('/curriculum', [CurriculumController::class, 'index'])->name('curriculum.index');
        Route::post('/curriculum/units', [CurriculumController::class, 'storeUnit'])->name('curriculum.units.store');
        Route::post('/curriculum/lessons', [CurriculumController::class, 'storeLesson'])->name('curriculum.lessons.store');
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
