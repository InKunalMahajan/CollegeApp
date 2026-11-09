<?php

use App\Http\Controllers\AcademicSetupController;
use App\Http\Controllers\SchoolSecurityController;
use App\Http\Controllers\SchoolPortalController;
use App\Http\Controllers\SchoolDashboardController;
use App\Http\Controllers\SchoolDepartmentController;
use App\Http\Controllers\SchoolEmployeeController;
use App\Http\Controllers\SchoolPermissionMatrixController;
use App\Http\Controllers\SchoolFinanceLibraryController;
use App\Http\Controllers\SchoolAcademicController;
use App\Http\Controllers\SchoolAdmissionController;
use App\Http\Controllers\MySchoolsController;
use App\Http\Controllers\SchoolAdministrationController;
use App\Http\Controllers\SchoolStudentAccountController;
use App\Http\Controllers\SchoolStudentController;
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

    Route::get('/announcements', [AnnouncementController::class, 'index'])->middleware('legacy.global')->name('announcements.index');
    Route::get('/my-schools', [MySchoolsController::class, 'index'])->name('my-schools.index');
    Route::get('/schools/{school}/permissions', SchoolPermissionMatrixController::class)
        ->middleware('school.can:school.security.view')
        ->name('school-permissions.index');

    Route::prefix('schools/{school}/employees')->group(function () {
        Route::get('/', [SchoolEmployeeController::class, 'index'])->middleware('school.can:school.employees.view')->name('school-employees.index');
        Route::post('/', [SchoolEmployeeController::class, 'store'])->middleware('school.can:school.employees.manage')->name('school-employees.store');
        Route::post('/import', [SchoolEmployeeController::class, 'import'])->middleware('school.can:school.employees.import')->name('school-employees.import');
        Route::put('/{employee}', [SchoolEmployeeController::class, 'update'])->middleware('school.can:school.employees.manage')->name('school-employees.update');
    });

    Route::prefix('schools/{school}/departments')->group(function () {
        Route::get('/', [SchoolDepartmentController::class, 'index'])->middleware('school.can:school.departments.view')->name('school-departments.index');
        Route::get('/{department}', [SchoolDepartmentController::class, 'show'])->middleware('department.can:department.dashboard.view')->name('school-departments.show');
        Route::post('/', [SchoolDepartmentController::class, 'store'])->middleware('school.can:school.departments.manage')->name('school-departments.store');
        Route::post('/{department}/members', [SchoolDepartmentController::class, 'assign'])->middleware('school.can:school.departments.manage')->name('school-departments.assign');
        Route::delete('/{department}/members/{membership}', [SchoolDepartmentController::class, 'revoke'])->middleware('school.can:school.departments.manage')->name('school-departments.revoke');
    });
    // Explicit school membership is checked inside the read-only controller.
    Route::middleware('school.member:school_admin,auditor')->group(function () {
        Route::get('/schools/{school}/security-report', [SchoolSecurityController::class, 'report'])->middleware('school.can:school.security.view')->name('school-security.report');
        Route::get('/schools/{school}/portal', [SchoolPortalController::class, 'index'])->middleware('school.can:school.portal.view')->name('school-portal.index');
        Route::get('/schools/{school}/dashboard', [SchoolDashboardController::class, 'index'])->middleware('school.can:school.dashboard.view')->name('school-dashboard.index');
        Route::get('/schools/{school}/dashboard/export', [SchoolDashboardController::class, 'export'])->middleware('school.can:school.dashboard.view')->name('school-dashboard.export');
    });

    Route::prefix('schools/{school}')->middleware('school.member:school_admin')->group(function () {
        Route::get('/fees', [SchoolFinanceLibraryController::class, 'fees'])->middleware('school.can:school.finance.view')->name('school-finance.fees');
        Route::post('/fees/charges/{charge}/payments', [SchoolFinanceLibraryController::class, 'payment'])->middleware('school.can:school.finance.manage')->name('school-finance.payment');
        Route::get('/library', [SchoolFinanceLibraryController::class, 'library'])->middleware('school.can:school.library.view')->name('school-finance.library');
        Route::post('/library/loans', [SchoolFinanceLibraryController::class, 'issue'])->middleware('school.can:school.library.manage')->name('school-finance.issue');
        Route::post('/library/loans/{loan}/return', [SchoolFinanceLibraryController::class, 'returnBook'])->middleware('school.can:school.library.manage')->name('school-finance.return');
    });

    Route::prefix('schools/{school}')->middleware('school.member:school_admin')->group(function () {
        Route::get('/academics', [SchoolAcademicController::class, 'index'])->middleware('school.can:school.academics.view')->name('school-academics.index');
        Route::post('/students', [SchoolAdmissionController::class, 'store'])->middleware('school.can:school.students.manage')->name('school-admissions.store');
        Route::get('/students', [SchoolStudentController::class, 'index'])->middleware('school.can:school.students.view')->name('school-students.index');
        Route::get('/students/{student}', [SchoolStudentController::class, 'show'])->middleware('school.can:school.students.view')->name('school-students.show');
        Route::get('/students/{student}/edit', [SchoolStudentController::class, 'edit'])->middleware('school.can:school.students.manage')->name('school-students.edit');
        Route::put('/students/{student}', [SchoolStudentController::class, 'update'])->middleware('school.can:school.students.manage')->name('school-students.update');
        Route::post('/academics/enrollments', [SchoolAcademicController::class, 'enroll'])->middleware('school.can:school.academics.manage')->name('school-academics.enroll');
        Route::post('/academics/teacher-assignments', [SchoolAcademicController::class, 'assignTeacher'])->middleware('school.can:school.academics.manage')->name('school-academics.teaching');
        Route::post('/academics/attendance', [SchoolAcademicController::class, 'recordAttendance'])->middleware('school.can:school.academics.manage')->name('school-academics.attendance');
        Route::post('/academics/timetable', [SchoolAcademicController::class, 'storeTimetable'])->middleware('school.can:school.academics.manage')->name('school-academics.timetable');
        Route::post('/academics/exams', [SchoolAcademicController::class, 'storeExam'])->middleware('school.can:school.academics.manage')->name('school-academics.exams');
        Route::post('/academics/exams/{exam}/results', [SchoolAcademicController::class, 'recordResult'])->middleware('school.can:school.academics.manage')->name('school-academics.results');
    });

    Route::prefix('schools/{school}')->middleware('school.member:school_admin')->group(function () {
        Route::get('/administration', [SchoolAdministrationController::class, 'index'])->middleware('school.can:school.administration.manage')->name('school-admin.index');
        Route::get('/student-accounts', [SchoolStudentAccountController::class, 'index'])->middleware('school.can:school.accounts.manage')->name('school-student-accounts.index');
        Route::post('/student-accounts', [SchoolStudentAccountController::class, 'store'])->middleware('school.can:school.accounts.manage')->name('school-student-accounts.store');
        Route::delete('/student-accounts/{account}', [SchoolStudentAccountController::class, 'destroy'])->middleware('school.can:school.accounts.manage')->name('school-student-accounts.destroy');
        Route::post('/administration/auditors', [SchoolAdministrationController::class, 'addAuditor'])->middleware('school.can:school.administration.manage')->name('school-admin.auditors.store');
        Route::delete('/administration/auditors/{membership}', [SchoolAdministrationController::class, 'removeAuditor'])->middleware('school.can:school.administration.manage')->name('school-admin.auditors.destroy');
        Route::post('/administration/legacy-students/claim', [SchoolAdministrationController::class, 'claimLegacyStudent'])->middleware('school.can:school.administration.manage')->name('school-admin.students.claim');
    });

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
        ->middleware(['role:admin,staff', 'legacy.global']);

    Route::get('/my-timetable', [TimetableController::class, 'mine'])->middleware('role:teacher')->name('timetables.mine');

    Route::get('/my-teaching', MyTeachingController::class)
        ->middleware('role:teacher')->name('my-teaching');

    Route::middleware('role:teacher')->group(function () {
        Route::get('/my-curriculum', [CurriculumController::class, 'myProgress'])->name('curriculum.mine');
        Route::post('/my-curriculum/progress', [CurriculumController::class, 'storeProgress'])->name('curriculum.progress.store');
    });

    Route::middleware(['role:admin', 'legacy.global'])->group(function () {
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
