@extends('layouts.app')
@section('title', 'Attendance')
@section('heading', 'Attendance Management')
@section('content')
<p class="mb-6 text-sm text-slate-600">Administrative attendance register. Dates cannot be in the future. Re-entering a record for the same person and date updates its status.</p>
@if($errors->any())<div role="alert" class="mb-6 rounded-lg bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="grid gap-5 xl:grid-cols-3">
<section class="schoolapp-panel rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
<h2 class="mb-4 font-semibold">Enroll student in class</h2>
<form method="POST" action="{{ route('attendance.enroll') }}" class="space-y-3">@csrf
<label for="student_id" class="block text-sm font-medium">Student</label>
<select required id="student_id" name="student_id" class="w-full rounded-lg border border-slate-300 p-2.5"><option value="">Select student</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->admission_number }} · {{ $student->first_name }} {{ $student->last_name }}</option>@endforeach</select>
<label for="section_id" class="block text-sm font-medium">Class section</label>
<select required id="section_id" name="class_section_id" class="w-full rounded-lg border border-slate-300 p-2.5"><option value="">Select class</option>@foreach($sections as $section)<option value="{{ $section->id }}">{{ $section->campus->name }} · {{ $section->name }}</option>@endforeach</select>
<button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Enroll student</button></form>
</section>
<section class="schoolapp-panel rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
<h2 class="mb-4 font-semibold">Record student attendance</h2>
<form method="POST" action="{{ route('attendance.student.store') }}" class="space-y-3">@csrf
<label for="enrollment_id" class="block text-sm font-medium">Enrollment</label>
<select required id="enrollment_id" name="class_enrollment_id" class="w-full rounded-lg border border-slate-300 p-2.5"><option value="">Select enrollment</option>@foreach($enrollments as $enrollment)<option value="{{ $enrollment->id }}">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }} · {{ $enrollment->classSection->name }}</option>@endforeach</select>
<label for="student_date" class="block text-sm font-medium">Date</label><input required type="date" max="{{ now()->toDateString() }}" id="student_date" name="attendance_date" value="{{ now()->toDateString() }}" class="w-full rounded-lg border border-slate-300 p-2.5">
<label for="student_status" class="block text-sm font-medium">Status</label><select required id="student_status" name="status" class="w-full rounded-lg border border-slate-300 p-2.5">@foreach(['present','absent','late','excused'] as $status)<option value="{{ $status }}">{{ ucfirst($status) }}</option>@endforeach</select>
<button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Save student attendance</button></form>
</section>
<section class="schoolapp-panel rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
<h2 class="mb-4 font-semibold">Record teacher attendance</h2>
<form method="POST" action="{{ route('attendance.staff.store') }}" class="space-y-3">@csrf
<label for="teacher_id" class="block text-sm font-medium">Teacher</label><select required id="teacher_id" name="teacher_profile_id" class="w-full rounded-lg border border-slate-300 p-2.5"><option value="">Select teacher</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->user->name }}</option>@endforeach</select>
<label for="staff_date" class="block text-sm font-medium">Date</label><input required id="staff_date" type="date" max="{{ now()->toDateString() }}" name="attendance_date" value="{{ now()->toDateString() }}" class="w-full rounded-lg border border-slate-300 p-2.5">
<label for="staff_status" class="block text-sm font-medium">Status</label><select required id="staff_status" name="status" class="w-full rounded-lg border border-slate-300 p-2.5">@foreach(['present','absent','late','excused'] as $status)<option value="{{ $status }}">{{ ucfirst($status) }}</option>@endforeach</select>
<button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Save teacher attendance</button></form>
</section>
</div>
<div class="mt-8 grid gap-6 lg:grid-cols-2">
<section class="schoolapp-panel rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
<h3 class="mb-4 font-semibold">Recent student attendance</h3>
<div class="space-y-3">@forelse($studentRows as $row)<div class="flex justify-between gap-4 border-b border-slate-100 pb-3 text-sm"><span>{{ $row->enrollment->student->first_name }} · {{ $row->enrollment->classSection->name }}</span><span class="whitespace-nowrap">{{ $row->attendance_date->format('Y-m-d') }} · {{ ucfirst($row->status) }}</span></div>@empty<p class="text-sm text-slate-500">No records yet.</p>@endforelse</div>
</section>
<section class="schoolapp-panel rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
<h3 class="mb-4 font-semibold">Recent teacher attendance</h3>
<div class="space-y-3">@forelse($staffRows as $row)<div class="flex justify-between gap-4 border-b border-slate-100 pb-3 text-sm"><span>{{ $row->teacherProfile->user->name }}</span><span class="whitespace-nowrap">{{ $row->attendance_date->format('Y-m-d') }} · {{ ucfirst($row->status) }}</span></div>@empty<p class="text-sm text-slate-500">No records yet.</p>@endforelse</div>
</section>
</div>
@endsection
