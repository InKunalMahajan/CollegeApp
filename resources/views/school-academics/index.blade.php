@extends('layouts.app')
@section('title','School Academics')
@section('heading','School Academic Management')
@section('content')
<h2 class="mb-2 text-xl font-semibold">{{ $school->name }} — Academics</h2>
<a href="{{ route('school-students.index',$school) }}" class="mb-4 inline-block text-sm font-semibold text-indigo-700 inline-flex items-center gap-2"><x-ui.icon name="users"/> Browse and edit students</a>
<p class="mb-5 text-sm text-slate-600">School-specific enrollment, attendance, teacher assignments, timetable and exams. Limited to verified school administrators.</p>
@if($errors->any())<div role="alert" class="mb-5 rounded-lg bg-rose-50 p-4 text-sm text-rose-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if(session('success'))<p role="status" class="mb-5 rounded-lg bg-slate-100 p-4 text-sm">{{ session('success') }}</p>@endif
<section class="mb-5 rounded-xl border border-slate-200 bg-white p-5"><h3 class="mb-3 font-semibold">Admit student to this school</h3>
<form method="POST" action="{{ route('school-admissions.store',$school) }}" class="grid gap-3 sm:grid-cols-3">@csrf
<div><label for="admission-number" class="block text-sm">Admission number</label><input id="admission-number" name="admission_number" maxlength="40" required class="w-full rounded-lg border border-slate-300 p-2"></div>
<div><label for="first-name" class="block text-sm">First name</label><input id="first-name" name="first_name" maxlength="255" required class="w-full rounded-lg border border-slate-300 p-2"></div>
<div><label for="last-name" class="block text-sm">Last name</label><input id="last-name" name="last_name" maxlength="255" required class="w-full rounded-lg border border-slate-300 p-2"></div>
<div><label for="admission-date" class="block text-sm">Admission date</label><input id="admission-date" name="admission_date" type="date" required value="{{ now()->toDateString() }}" class="w-full rounded-lg border border-slate-300 p-2"></div>
<div><label for="admission-status" class="block text-sm">Status</label><select id="admission-status" name="status" class="w-full rounded-lg border border-slate-300 p-2"><option value="active">Active</option><option value="inactive">Inactive</option><option value="graduated">Graduated</option></select></div>
<div class="flex items-end"><button class="rounded-lg bg-indigo-600 px-4 py-2.5 font-semibold text-white">Admit student</button></div>
</form><p class="mt-3 text-xs text-slate-500">Admission records are linked to this school. Existing unassigned records must be reviewed before enrollment.</p></section>
<div class="grid gap-5 xl:grid-cols-2">
<section class="rounded-xl border border-slate-200 bg-white p-5"><h3 class="mb-3 font-semibold">Enroll admitted student</h3>
<p class="mb-3 text-xs text-slate-500">Student ID must already exist. Cross-school enrolled students are rejected.</p>
<form method="POST" action="{{ route('school-academics.enroll',$school) }}" class="grid gap-3">@csrf
<label class="text-sm" for="student-id">Student ID</label><input id="student-id" name="student_id" type="number" min="1" required class="rounded-lg border border-slate-300 p-2.5" placeholder="Existing student ID">
<label class="text-sm" for="enroll-section">Class section</label><select id="enroll-section" name="class_section_id" required class="rounded-lg border border-slate-300 p-2.5"><option value="">Select class</option>@foreach($sections as $section)<option value="{{ $section->id }}">{{ $section->name }} · {{ $section->campus->name }}</option>@endforeach</select>
<button class="rounded-lg bg-indigo-600 px-4 py-2.5 font-semibold text-white">Enroll student</button></form></section>
<section class="rounded-xl border border-slate-200 bg-white p-5"><h3 class="mb-3 font-semibold">Teacher and subject assignment</h3>
<form method="POST" action="{{ route('school-academics.teaching',$school) }}" class="grid gap-3">@csrf
<label class="text-sm" for="teacher">Teacher</label><select id="teacher" name="teacher_profile_id" required class="rounded-lg border border-slate-300 p-2.5"><option value="">Select teacher</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->user->name }} ({{ $teacher->employee_code }})</option>@endforeach</select>
<label class="text-sm" for="subject">Subject</label><select id="subject" name="subject_id" required class="rounded-lg border border-slate-300 p-2.5"><option value="">Select subject</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select>
<label class="text-sm" for="teaching-section">Class section</label><select id="teaching-section" name="class_section_id" required class="rounded-lg border border-slate-300 p-2.5"><option value="">Select class</option>@foreach($sections as $section)<option value="{{ $section->id }}">{{ $section->name }}</option>@endforeach</select>
<button class="rounded-lg bg-indigo-600 px-4 py-2.5 font-semibold text-white">Assign teacher</button></form></section>
<section class="rounded-xl border border-slate-200 bg-white p-5"><h3 class="mb-3 font-semibold">Student attendance</h3>
<form method="POST" action="{{ route('school-academics.attendance',$school) }}" class="grid gap-3">@csrf
<label for="attendance-enrollment" class="text-sm">Student enrollment</label><select id="attendance-enrollment" name="class_enrollment_id" required class="rounded-lg border border-slate-300 p-2.5"><option value="">Select student</option>@foreach($enrollments as $enrollment)<option value="{{ $enrollment->id }}">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }} · {{ $enrollment->classSection->name }}</option>@endforeach</select>
<label for="attendance-date" class="text-sm">Date</label><input id="attendance-date" type="date" name="attendance_date" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required class="rounded-lg border border-slate-300 p-2.5">
<label for="attendance-status" class="text-sm">Status</label><select id="attendance-status" name="status" class="rounded-lg border border-slate-300 p-2.5"><option value="present">Present</option><option value="absent">Absent</option><option value="late">Late</option><option value="excused">Excused</option></select>
<button class="rounded-lg bg-indigo-600 px-4 py-2.5 font-semibold text-white">Save attendance</button></form></section>
<section class="rounded-xl border border-slate-200 bg-white p-5"><h3 class="mb-3 font-semibold">Timetable period</h3>
<form method="POST" action="{{ route('school-academics.timetable',$school) }}" class="grid gap-3">@csrf
<label class="text-sm" for="timetable-assignment">Teacher assignment</label><select id="timetable-assignment" name="teacher_subject_assignment_id" required class="rounded-lg border border-slate-300 p-2.5"><option value="">Select teaching assignment</option>@foreach($teachings as $teaching)<option value="{{ $teaching->id }}">{{ $teaching->teacherProfile->user->name }} · {{ $teaching->subject->name }} · {{ $teaching->classSection->name }}</option>@endforeach</select>
<label class="text-sm" for="day">Day of week (1 = Monday)</label><select id="day" name="day_of_week" class="rounded-lg border border-slate-300 p-2.5">@foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $i=>$day)<option value="{{ $i+1 }}">{{ $day }}</option>@endforeach</select>
<div class="grid grid-cols-2 gap-3"><div><label class="block text-sm" for="start">Start</label><input id="start" name="starts_at" type="time" required class="w-full rounded-lg border border-slate-300 p-2.5"></div><div><label class="block text-sm" for="end">End</label><input id="end" name="ends_at" type="time" required class="w-full rounded-lg border border-slate-300 p-2.5"></div></div>
<label class="text-sm" for="room">Room (optional)</label><input id="room" name="room" maxlength="80" class="rounded-lg border border-slate-300 p-2.5">
<button class="rounded-lg bg-indigo-600 px-4 py-2.5 font-semibold text-white">Create period</button></form></section>
<section class="rounded-xl border border-slate-200 bg-white p-5"><h3 class="mb-3 font-semibold">Create examination</h3>
<form method="POST" action="{{ route('school-academics.exams',$school) }}" class="grid gap-3">@csrf
<label class="text-sm" for="exam-class">Class</label><select id="exam-class" name="class_section_id" required class="rounded-lg border border-slate-300 p-2.5"><option value="">Select class</option>@foreach($sections as $section)<option value="{{ $section->id }}">{{ $section->name }}</option>@endforeach</select>
<label class="text-sm" for="exam-subject">Subject</label><select id="exam-subject" name="subject_id" required class="rounded-lg border border-slate-300 p-2.5"><option value="">Select subject</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select>
<label class="text-sm" for="exam-name">Exam name</label><input id="exam-name" name="name" required maxlength="150" class="rounded-lg border border-slate-300 p-2.5">
<label class="text-sm" for="exam-date">Exam date</label><input id="exam-date" type="date" name="exam_date" required class="rounded-lg border border-slate-300 p-2.5">
<div class="grid grid-cols-2 gap-3"><div><label class="block text-sm" for="max-marks">Maximum marks</label><input id="max-marks" type="number" name="max_marks" min="1" max="10000" required class="w-full rounded-lg border border-slate-300 p-2.5"></div><div><label class="block text-sm" for="pass-marks">Pass marks</label><input id="pass-marks" type="number" name="pass_marks" min="0" required class="w-full rounded-lg border border-slate-300 p-2.5"></div></div>
<button class="rounded-lg bg-indigo-600 px-4 py-2.5 font-semibold text-white">Create exam</button></form></section>
<section class="rounded-xl border border-slate-200 bg-white p-5"><h3 class="mb-3 font-semibold">Record exam result</h3>
<p class="mb-3 text-xs text-slate-500">Choose an exam and a student enrollment from the same class.</p>
<form method="POST" id="result-form" action="{{ $exams->isNotEmpty() ? route('school-academics.results',[$school,$exams->first()]) : '#' }}" class="grid gap-3">@csrf
<label class="text-sm" for="result-exam">Exam</label><select id="result-exam" required class="rounded-lg border border-slate-300 p-2.5" onchange="document.getElementById('result-form').action=this.value"><option value="">Select exam</option>@foreach($exams as $exam)<option value="{{ route('school-academics.results',[$school,$exam]) }}">{{ $exam->name }} · {{ $exam->classSection->name }}</option>@endforeach</select>
<label class="text-sm" for="result-student">Student enrollment</label><select id="result-student" name="class_enrollment_id" required class="rounded-lg border border-slate-300 p-2.5"><option value="">Select student</option>@foreach($enrollments as $enrollment)<option value="{{ $enrollment->id }}">{{ $enrollment->student->first_name }} · {{ $enrollment->classSection->name }}</option>@endforeach</select>
<label class="text-sm" for="result-status">Result status</label><select id="result-status" name="status" class="rounded-lg border border-slate-300 p-2.5"><option value="graded">Graded</option><option value="absent">Absent</option><option value="exempt">Exempt</option></select>
<label class="text-sm" for="marks">Marks (only for graded results)</label><input id="marks" type="number" name="marks_obtained" min="0" class="rounded-lg border border-slate-300 p-2.5">
<button class="rounded-lg bg-indigo-600 px-4 py-2.5 font-semibold text-white" @disabled($exams->isEmpty())>Save result</button></form></section>
</div>
<div class="mt-7 grid gap-5 lg:grid-cols-2">
<section class="rounded-xl border border-slate-200 bg-white p-5"><h3 class="mb-3 font-semibold">Recent attendance</h3><ul class="space-y-2 text-sm">@forelse($attendance as $row)<li>{{ $row->enrollment->student->first_name }} · {{ $row->attendance_date->format('Y-m-d') }} · {{ ucfirst($row->status) }}</li>@empty<li>No attendance recorded.</li>@endforelse</ul></section>
<section class="rounded-xl border border-slate-200 bg-white p-5"><h3 class="mb-3 font-semibold">Timetable</h3><ul class="space-y-2 text-sm">@forelse($timetable as $row)<li>Day {{ $row->day_of_week }} · {{ $row->starts_at }}–{{ $row->ends_at }} · {{ $row->classSection->name }} · {{ $row->subject->name }}</li>@empty<li>No timetable entries.</li>@endforelse</ul></section>
</div>
@endsection
