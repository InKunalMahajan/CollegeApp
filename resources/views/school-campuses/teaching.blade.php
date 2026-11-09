@extends('layouts.app')
@section('title','Campus Teaching')
@section('heading','Campus Teaching')
@section('content')
<x-ui.page-heading :title="$campus->name.' · Teaching'" :description="$school->name.' · Campus teaching assignments'"/>
@if(session('success'))<x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>@endif
@if($errors->any())<x-ui.alert type="error" class="mb-4">{{ $errors->first() }}</x-ui.alert>@endif
<p class="mb-4"><a href="{{ route('campus.operations.index', [$school,$campus]) }}">← Campus operations</a></p>
<x-ui.card title="Assign existing teacher to subject and class">
<form method="POST" action="{{ route('campus.teaching.store', [$school,$campus]) }}" class="grid gap-3 sm:grid-cols-3">
@csrf
<label>Teacher<select name="teacher_profile_id" required class="mt-1 w-full"><option value="">Select teacher</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->user?->name ?? $teacher->employee_code }}</option>@endforeach</select></label>
<label>Class<select name="class_section_id" required class="mt-1 w-full"><option value="">Select class</option>@foreach($sections as $section)<option value="{{ $section->id }}">{{ $section->name }}</option>@endforeach</select></label>
<label>Subject<select name="subject_id" required class="mt-1 w-full"><option value="">Select subject</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select></label>
<div class="sm:col-span-3"><button class="rounded-xl bg-indigo-600 px-4 py-2 text-white">Save assignment</button></div>
</form>
</x-ui.card>
<x-ui.card class="mt-5" title="Campus teaching assignments">
@forelse($assignments as $assignment)
<div class="border-b border-slate-200 py-3 text-sm">{{ $assignment->teacherProfile->user?->name ?? $assignment->teacherProfile->employee_code }} · {{ $assignment->subject->name }} · {{ $assignment->classSection->name }}</div>
@empty<p>No verified assignments.</p>@endforelse
<div class="mt-4">{{ $assignments->links() }}</div>
</x-ui.card>
@endsection