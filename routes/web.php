<?php

use App\Http\Controllers\AcademicSetupController;
use App\Http\Controllers\SchoolSecurityController;
use App\Http\Controllers\SchoolPortalController;
use App\Http\Controllers\SchoolDashboardController;
use App\Http\Controllers\SchoolDepartmentController;
use App\Http\Controllers\SchoolEmployeeController;
use App\Http\Controllers\SchoolCampusController;
use App\Http\Controllers\CampusWorkspaceController;
use App\Http\Controllers\CampusOperationsController;
use App\Http\Controllers\CampusEnrollmentController;
use App\Http\Controllers\CampusTeachingController;
use App\Http\Controllers\CampusAttendanceController;
use App\Http\Controllers\CampusExamController;
use App\Http\Controllers\CampusFinanceLibraryController;
use App\Http\Controllers\WebsiteOwnerUserController;
use App\Http\Controllers\WebsiteOwnerDashboardController;
use App\Http\Controllers\SaasOnboardingController;
use App\Http\Controllers\SaasPlanController;
use App\Http\Controllers\SaasInvoiceController;
use App\Http\Controllers\OrganizationUserController;
use App\Http\Controllers\CampusUserController;
use App\Http\Controllers\SchoolEmployeeProfileController;
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
    Route::get('/saas/onboarding', [SaasOnboardingController::class, 'index'])->name('saas.onboarding.index');
    Route::post('/saas/onboarding', [SaasOnboardingController::class, 'store'])->middleware('throttle:3,60')->name('saas.onboarding.store');
    Route::get('/website-owner/onboarding', [SaasOnboardingController::class, 'review'])->middleware('website.owner')->name('saas.onboarding.review');
    Route::get('/website-owner/plans', [SaasPlanController::class, 'index'])->middleware('website.owner')->name('saas.plans.index');
    Route::get('/website-owner/invoices', [SaasInvoiceController::class, 'index'])->middleware('website.owner')->name('saas.invoices.index');
    Route::post('/website-owner/invoices', [SaasInvoiceController::class, 'store'])->middleware('website.owner')->name('saas.invoices.store');
    Route::post('/website-owner/plans', [SaasPlanController::class, 'store'])->middleware('website.owner')->name('saas.plans.store');
    Route::post('/website-owner/subscriptions', [SaasPlanController::class, 'assign'])->middleware('website.owner')->name('saas.plans.assign');
    Route::post('/website-owner/onboarding/{onboarding}/approve', [SaasOnboardingController::class, 'approve'])->middleware('website.owner')->name('saas.onboarding.approve');
    Route::post('/website-owner/onboarding/{onboarding}/reject', [SaasOnboardingController::class, 'reject'])->middleware('website.owner')->name('saas.onboarding.reject');
    // Explicit platform owner routes must not depend on legacy global admin
    // privileges or the optional legacy-global-route feature flag.
    Route::get('/users', [UserManagementController::class, 'index'])->middleware('website.owner')->name('users.index');
    Route::get('/users/create', [UserManagementController::class, 'create'])->middleware('website.owner')->name('users.create');
    Route::post('/users', [UserManagementController::class, 'store'])->middleware('website.owner')->name('users.store');
    Route::prefix('website-owner')->middleware('website.owner')->group(function () {
        Route::get('/dashboard', [WebsiteOwnerDashboardController::class, 'index'])->name('website-owner.dashboard');
        Route::get('/users', [WebsiteOwnerUserController::class, 'index'])->name('website-owner.users.index');
        Route::post('/users', [WebsiteOwnerUserController::class, 'store'])->name('website-owner.users.store');
        Route::put('/users/{user}', [WebsiteOwnerUserController::class, 'update'])->name('website-owner.users.update');
        Route::delete('/users/{user}', [WebsiteOwnerUserController::class, 'destroy'])->name('website-owner.users.destroy');
        Route::post('/users/{user}/restore', [WebsiteOwnerUserController::class, 'restore'])->name('website-owner.users.restore');
        Route::post('/organization-admins', [WebsiteOwnerUserController::class, 'grant'])->name('website-owner.admins.grant');
        Route::delete('/organization-admins/{membership}', [WebsiteOwnerUserController::class, 'revoke'])->name('website-owner.admins.revoke');
    });
    Route::prefix('schools/{school}/user-access')->middleware('school.can:school.users.manage')->group(function () {
        Route::get('/', [OrganizationUserController::class, 'index'])->name('organization-users.index');
        Route::post('/', [OrganizationUserController::class, 'store'])->name('organization-users.store');
        Route::put('/{membership}', [OrganizationUserController::class, 'update'])->name('organization-users.update');
        Route::delete('/{membership}', [OrganizationUserController::class, 'destroy'])->name('organization-users.destroy');
    });
    // School is the parent organization (e.g. TEACH). Only its explicitly
    // authorized organization administrator can grant campus administration.
    Route::prefix('schools/{school}/campuses')
        ->middleware('school.can:school.administration.manage')->group(function () {
            Route::get('/', [SchoolCampusController::class, 'index'])->name('school-campuses.index');
            Route::post('/', [SchoolCampusController::class, 'store'])->name('school-campuses.store');
            Route::put('/{campus}', [SchoolCampusController::class, 'update'])->name('school-campuses.update');
            Route::delete('/{campus}', [SchoolCampusController::class, 'destroy'])->name('school-campuses.destroy');
            Route::post('/{campus}/admins', [SchoolCampusController::class, 'grant'])->name('school-campuses.grant');
            Route::delete('/{campus}/admins/{membership}', [SchoolCampusController::class, 'revoke'])->name('school-campuses.revoke');
            Route::post('/{campus}/employees/{employee}/assign', [SchoolCampusController::class, 'assignEmployee'])->name('school-campuses.assign-employee');
            Route::post('/{campus}/students/{student}/assign', [SchoolCampusController::class, 'assignStudent'])->name('school-campuses.assign-student');
        });

    // Campus administrators never receive the school_admin role. Campus
    // middleware checks BOTH the campus's organization and their assignment.
    Route::prefix('schools/{school}/campuses/{campus}')->group(function () {
        Route::get('/user-access', [CampusUserController::class, 'index'])
            ->middleware('campus.can:campus.users.manage')->name('campus-users.index');
        Route::post('/user-access', [CampusUserController::class, 'store'])
            ->middleware('campus.can:campus.users.manage')->name('campus-users.store');
        Route::put('/user-access/{membership}', [CampusUserController::class, 'update'])
            ->middleware('campus.can:campus.users.manage')->name('campus-users.update');
        Route::delete('/user-access/{membership}', [CampusUserController::class, 'destroy'])
            ->middleware('campus.can:campus.users.manage')->name('campus-users.destroy');
        Route::get('/finance-library', [CampusFinanceLibraryController::class, 'index'])
            ->middleware('campus.can:campus.finance.view')->name('campus.finance-library.index');
        Route::get('/exams', [CampusExamController::class, 'index'])
            ->middleware('campus.can:campus.exams.view')->name('campus.exams.index');
        Route::post('/exams', [CampusExamController::class, 'store'])
            ->middleware('campus.can:campus.exams.manage')->name('campus.exams.store');
        Route::post('/exams/{exam}/results', [CampusExamController::class, 'result'])
            ->middleware('campus.can:campus.exams.manage')->name('campus.exams.result');
        Route::get('/attendance', [CampusAttendanceController::class, 'index'])
            ->middleware('campus.can:campus.attendance.view')->name('campus.attendance.index');
        Route::post('/attendance/students', [CampusAttendanceController::class, 'student'])
            ->middleware('campus.can:campus.attendance.manage')->name('campus.attendance.student');
        Route::post('/attendance/staff', [CampusAttendanceController::class, 'staff'])
            ->middleware('campus.can:campus.attendance.manage')->name('campus.attendance.staff');
        Route::get('/teaching', [CampusTeachingController::class, 'index'])
            ->middleware('campus.can:campus.teaching.view')->name('campus.teaching.index');
        Route::post('/teaching', [CampusTeachingController::class, 'store'])
            ->middleware('campus.can:campus.teaching.manage')->name('campus.teaching.store');
        Route::get('/enrollments', [CampusEnrollmentController::class, 'index'])
            ->middleware('campus.can:campus.enrollments.view')->name('campus.enrollments.index');
        Route::post('/enrollments', [CampusEnrollmentController::class, 'store'])
            ->middleware('campus.can:campus.enrollments.manage')->name('campus.enrollments.store');
        Route::get('/operations', [CampusOperationsController::class, 'index'])
            ->middleware('campus.can:campus.operations.view')->name('campus.operations.index');
        Route::get('/workspace', [CampusWorkspaceController::class, 'index'])
            ->middleware('campus.can:campus.dashboard.view')->name('campus-workspace.index');
        Route::post('/employees', [CampusWorkspaceController::class, 'createEmployee'])
            ->middleware('campus.can:campus.employees.manage')->name('campus.employees.store');
        Route::put('/employees/{employee}', [CampusWorkspaceController::class, 'updateEmployee'])
            ->middleware('campus.can:campus.employees.manage')->name('campus.employees.update');
        Route::delete('/employees/{employee}', [CampusWorkspaceController::class, 'archiveEmployee'])
            ->middleware('campus.can:campus.employees.manage')->name('campus.employees.archive');
        Route::post('/employees/{employee}/restore', [CampusWorkspaceController::class, 'restoreEmployee'])
            ->middleware('campus.can:campus.employees.manage')->name('campus.employees.restore');
        Route::post('/students', [CampusWorkspaceController::class, 'createStudent'])
            ->middleware('campus.can:campus.students.manage')->name('campus.students.store');
        Route::put('/students/{student}', [CampusWorkspaceController::class, 'updateStudent'])
            ->middleware('campus.can:campus.students.manage')->name('campus.students.update');
        Route::delete('/students/{student}', [CampusWorkspaceController::class, 'deactivateStudent'])
            ->middleware('campus.can:campus.students.manage')->name('campus.students.deactivate');
    });

    Route::get('/schools/{school}/permissions', SchoolPermissionMatrixController::class)
        ->middleware('school.can:school.security.view')
        ->name('school-permissions.index');

    Route::prefix('schools/{school}/employees')->group(function () {
        Route::get('/', [SchoolEmployeeController::class, 'index'])->middleware('school.can:school.employees.view')->name('school-employees.index');
        Route::get('/{employee}', [SchoolEmployeeProfileController::class, 'show'])->middleware('school.can:school.employees.view')->name('school-employees.show');
        Route::post('/', [SchoolEmployeeController::class, 'store'])->middleware('school.can:school.employees.manage')->name('school-employees.store');
        Route::post('/import', [SchoolEmployeeController::class, 'import'])->middleware('school.can:school.employees.import')->name('school-employees.import');
        Route::put('/{employee}', [SchoolEmployeeController::class, 'update'])->middleware('school.can:school.employees.manage')->name('school-employees.update');
        Route::delete('/{employee}', [SchoolEmployeeController::class, 'destroy'])->middleware('school.can:school.employees.manage')->name('school-employees.destroy');
        Route::post('/{employee}/restore', [SchoolEmployeeController::class, 'restore'])->middleware('school.can:school.employees.manage')->name('school-employees.restore');
    });

    Route::prefix('schools/{school}/departments')->group(function () {
        Route::get('/', [SchoolDepartmentController::class, 'index'])->middleware('school.can:school.departments.view')->name('school-departments.index');
        Route::get('/{department}', [SchoolDepartmentController::class, 'show'])->middleware('department.can:department.dashboard.view')->name('school-departments.show');
        Route::post('/', [SchoolDepartmentController::class, 'store'])->middleware('school.can:school.departments.manage')->name('school-departments.store');
        Route::put('/{department}', [SchoolDepartmentController::class, 'update'])->middleware('school.can:school.departments.manage')->name('school-departments.update');
        Route::delete('/{department}', [SchoolDepartmentController::class, 'destroy'])->middleware('school.can:school.departments.manage')->name('school-departments.destroy');
        Route::post('/{department}/members', [SchoolDepartmentController::class, 'assign'])->middleware('school.can:school.departments.manage')->name('school-departments.assign');
        Route::delete('/{department}/members/{membership}', [SchoolDepartmentController::class, 'revoke'])->middleware('school.can:school.departments.manage')->name('school-departments.revoke');
    });
    // Explicit school membership is checked inside the read-only controller.
    Route::middleware('school.member:school_admin,auditor,organization_viewer')->group(function () {
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
    });
});
