<?php

// Feature planning catalog. A listed module is not necessarily implemented.
// Never use this configuration alone to authorize access to sensitive records.
return [
    ['key' => 'dashboard', 'name' => 'Dashboard', 'features' => 'Statistics, summaries, charts', 'phase' => 3, 'status' => 'partial'],
    ['key' => 'schools', 'name' => 'School Management', 'features' => 'School profile, campus settings', 'phase' => 4, 'status' => 'partial'],
    ['key' => 'users', 'name' => 'User Management', 'features' => 'Accounts, roles, permissions', 'phase' => 3, 'status' => 'partial'],
    ['key' => 'students', 'name' => 'Student Management', 'features' => 'Admissions, profiles, records', 'phase' => 1, 'status' => 'partial'],
    ['key' => 'teachers', 'name' => 'Teacher Management', 'features' => 'Staff profiles, assignments', 'phase' => 5, 'status' => 'partial'],
    ['key' => 'academics', 'name' => 'Academic Management', 'features' => 'Academic years, classes, subjects', 'phase' => 4, 'status' => 'partial'],
    ['key' => 'attendance', 'name' => 'Attendance Management', 'features' => 'Student and teacher attendance', 'phase' => 6, 'status' => 'partial'],
    ['key' => 'timetables', 'name' => 'Timetable Management', 'features' => 'Class schedules, teacher schedules', 'phase' => 6, 'status' => 'partial'],
    ['key' => 'exams', 'name' => 'Examination Management', 'features' => 'Exams, marks, grades, report cards', 'phase' => 7, 'status' => 'planned'],
    ['key' => 'curriculum', 'name' => 'Curriculum Management', 'features' => 'Syllabus, lessons, teaching progress', 'phase' => 7, 'status' => 'planned'],
    ['key' => 'assignments', 'name' => 'Assignment Management', 'features' => 'Homework, submissions, grading', 'phase' => 8, 'status' => 'planned'],
    ['key' => 'fees', 'name' => 'Fee Management', 'features' => 'Fees, payments, receipts', 'phase' => 9, 'status' => 'planned'],
    ['key' => 'communication', 'name' => 'Communication', 'features' => 'Notices, announcements, messages', 'phase' => 8, 'status' => 'planned'],
    ['key' => 'reports', 'name' => 'Reports & Analytics', 'features' => 'Academic and administrative reports', 'phase' => 10, 'status' => 'planned'],
    ['key' => 'library', 'name' => 'Library Management', 'features' => 'Books, borrowing, returns', 'phase' => 9, 'status' => 'planned'],
    ['key' => 'settings', 'name' => 'Settings', 'features' => 'School configuration and preferences', 'phase' => 4, 'status' => 'planned'],
];
