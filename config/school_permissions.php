<?php

/*
|--------------------------------------------------------------------------
| SchoolApp RBAC permission registry
|--------------------------------------------------------------------------
|
| Role assignments are stored in existing school_memberships and
| school_department_memberships. This explicit allow-list is the source of
| truth for authenticated school-specific routes; unknown abilities deny.
| No automatic privileges from global User.role or a matching job title.
|
*/

return [
    'abilities' => [
        'school.dashboard.view' => 'View school-wide analytics',
        'school.portal.view' => 'View school overview',
        'school.security.view' => 'View school security and audit report',
        'school.departments.view' => 'List school departments',
        'school.departments.manage' => 'Create departments and manage department roles',
        'school.employees.view' => 'View allowed employee records',
        'school.employees.manage' => 'Add or edit employees',
        'school.employees.import' => 'Import employee CSV',
        'school.students.view' => 'View school students',
        'school.students.manage' => 'Create and edit school students',
        'school.academics.view' => 'View school academics',
        'school.academics.manage' => 'Record academic work and grades',
        'school.finance.view' => 'View school fees and payments',
        'school.finance.manage' => 'Record fee payments',
        'school.library.view' => 'View school library',
        'school.library.manage' => 'Issue and return books',
        'school.administration.manage' => 'Manage school membership and settings',
        'school.accounts.manage' => 'Manage student account links',
    ],

    'school_roles' => [
        'school_admin' => ['*'],
        'auditor' => [
            'school.dashboard.view',
            'school.portal.view',
            'school.security.view',
            'school.employees.view',
        ],
        'department_member' => [
            'school.departments.view',
            'school.employees.view',
        ],
    ],

    // Department abilities apply only to a verified department assignment.
    // Operational writes remain school-admin-only until separate, safely
    // scoped department workflows and policies are implemented.
    'department_roles' => [
        'department_head' => ['department.dashboard.view', 'department.employees.view'],
        'department_staff' => ['department.dashboard.view', 'department.employees.view'],
        'department_viewer' => ['department.dashboard.view', 'department.employees.view'],
    ],

    'department_abilities' => [
        'department.dashboard.view' => 'View an assigned department dashboard',
        'department.employees.view' => 'View allowed employees of an assigned department',
    ],
];
