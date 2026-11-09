@extends('layouts.app')
@section('title','Campus Attendance')
@section('heading','Campus Attendance')
@section('content')
<x-ui.page-heading :title="$campus->name.' · Attendance'" :description="$school->name.' · Student and teacher attendance'"/>
@if(session('success'))<x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>@endif
@if($errors->any())<x-ui.alert type="error" class="mb-4">{{ $errors->first() }}</x-ui.alert>@endif
<p class="mb-4"><a href="{{ route('campus.operations.index',[$school,$campus]) }}">← Campus operations</a></p>
<div class="grid gap-5 lg:grid-cols-2">
<x-ui.card title="Record student attendance">
<form method="POST" action="{{ route('campus.attendance.student',[$school,$campus]) }}" class="grid gap-3">@csrf
<label>Student and class<select name="class_enrollment_id" class="w-full" required><option value="">Select enrollment</option>@foreach($enrollments as $enrollment)<option value="{{ $enrollment->id }}">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }} · {{ $enrollment->classSection->name }}</option>@endforeach</select></label>
<label>Date<input type="date" name="attendance_date" required max="{{ now()->toDateString() }}" class="w-full"></label>
<label>Status<select name="status" class="w-full" required>@foreach(['present','absent','late','excused'] as $status)<option value="{{ $status }}">{{ ucfirst($status) }}</option>@endforeach</select></label>
<button class="rounded-xl bg-indigo-600 px-4 py-2 text-white">Save student attendance</button>
</form></x-ui.card>
<x-ui.card title="Record staff attendance">
<form method="POST" action="{{ route('campus.attendance.staff',[$school,$campus]) }}" class="grid gap-3">@csrf
<label>Teacher<select name="teacher_profile_id" class="w-full" required><option value="">Select teacher</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->user?->name ?? $teacher->employee_code }}</option>@endforeach</select></label>
<label>Date<input type="date" name="attendance_date" required max="{{ now()->toDateString() }}" class="w-full"></label>
<label>Status<select name="status" class="w-full" required>@foreach(['present','absent','late','excused'] as $status)<option value="{{ $status }}">{{ ucfirst($status) }}</option>@endforeach</select></label>
<button class="rounded-xl bg-indigo-600 px-4 py-2 text-white">Save staff attendance</button>
</form></x-ui.card>
</div>
<div class="grid gap-5 lg:grid-cols-2 mt-5">
<x-ui.card title="Student attendance history">@forelse($studentRecords as $record)<div class="border-b py-2 text-sm">{{ $record->enrollment->student->first_name }} · {{ $record->attendance_date->format('Y-m-d') }} · {{ $record->status }}</div>@empty<p>No student records.</p>@endforelse {{ $studentRecords->links() }}</x-ui.card>
<x-ui.card title="Staff attendance history">@forelse($staffRecords as $record)<div class="border-b py-2 text-sm">{{ $record->teacherProfile->user?->name ?? $record->teacherProfile->employee_code }} · {{ $record->attendance_date->format('Y-m-d') }} · {{ $record->status }}</div>@empty<p>No staff records.</p>@endforelse {{ $staffRecords->links() }}</x-ui.card>
</div>
@endsection