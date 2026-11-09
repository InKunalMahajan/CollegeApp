@extends('layouts.app')
@section('title', 'Campus Enrollments')
@section('heading', 'Campus Enrollments')
@section('content')
<x-ui.page-heading :title="$campus->name.' · Enrollments'" :description="$school->name.' · Verified campus student-to-class assignments'"/>
@if(session('success'))<x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>@endif
@if($errors->any())<x-ui.alert type="error" class="mb-4">{{ $errors->first() }}</x-ui.alert>@endif
<p class="mb-4"><a class="text-sm font-semibold text-indigo-700 hover:underline" href="{{ route('campus.operations.index', [$school, $campus]) }}">← Campus operations</a></p>
<x-ui.card title="Enroll student into class" description="Only active students and classes from this campus are available. Existing enrollments are never deleted.">
<form method="POST" action="{{ route('campus.enrollments.store', [$school, $campus]) }}" class="grid gap-3 sm:grid-cols-2">
@csrf
<label class="text-sm">Student
<select name="student_id" required class="mt-1 w-full">
<option value="">Select student</option>
@foreach($students as $student)<option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->first_name }} {{ $student->last_name }} ({{ $student->admission_number }})</option>@endforeach
</select></label>
<label class="text-sm">Class section
<select name="class_section_id" required class="mt-1 w-full">
<option value="">Select class</option>
@foreach($sections as $section)<option value="{{ $section->id }}" @selected(old('class_section_id') == $section->id)>{{ $section->name }} ({{ $section->code }})</option>@endforeach
</select></label>
<div class="sm:col-span-2"><button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Save enrollment</button></div>
</form>
</x-ui.card>
<x-ui.card class="mt-5" title="Verified enrollments" description="Only matching organization, campus, class and student records are displayed.">
<div class="space-y-3">
@forelse($enrollments as $enrollment)
<div class="rounded-xl border border-slate-200 p-3 text-sm">
<strong>{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</strong> · {{ $enrollment->student->admission_number }}
<p class="text-slate-600">{{ $enrollment->classSection->name }} · {{ $enrollment->classSection->code }}</p>
</div>
@empty <p class="text-sm text-slate-500">No verified campus enrollments found.</p>
@endforelse
</div>
<div class="mt-4">{{ $enrollments->links() }}</div>
</x-ui.card>
@endsection
