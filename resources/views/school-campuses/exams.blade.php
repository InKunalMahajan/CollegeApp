@extends('layouts.app')
@section('title','Campus Exams')
@section('heading','Campus Exams')
@section('content')
<x-ui.page-heading :title="$campus->name.' · Exams'" :description="$school->name.' · Verified examinations and results'"/>
@if(session('success'))<x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>@endif
@if($errors->any())<x-ui.alert type="error" class="mb-4">{{ $errors->first() }}</x-ui.alert>@endif
<p class="mb-4"><a href="{{ route('campus.operations.index',[$school,$campus]) }}">← Campus operations</a></p>
<x-ui.card title="Create examination">
<form action="{{ route('campus.exams.store',[$school,$campus]) }}" method="POST" class="grid gap-3 sm:grid-cols-3">@csrf
<label>Class<select name="class_section_id" required class="w-full"><option value="">Select class</option>@foreach($sections as $section)<option value="{{ $section->id }}">{{ $section->name }}</option>@endforeach</select></label>
<label>Subject<select name="subject_id" required class="w-full"><option value="">Select subject</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select></label>
<label>Exam name<input name="name" maxlength="150" required class="w-full"></label>
<label>Exam date<input type="date" name="exam_date" required class="w-full"></label>
<label>Maximum marks<input type="number" name="max_marks" min="1" max="10000" required class="w-full"></label>
<label>Pass marks<input type="number" name="pass_marks" min="0" required class="w-full"></label>
<div class="sm:col-span-3"><button class="rounded-xl bg-indigo-600 px-4 py-2 text-white">Create exam</button></div>
</form></x-ui.card>
<x-ui.card class="mt-5" title="Examinations and result entry">
@forelse($exams as $exam)
<details class="border-b py-3">
<summary class="cursor-pointer text-sm font-semibold">{{ $exam->name }} · {{ $exam->classSection->name }} · {{ $exam->subject->name }} · Max {{ $exam->max_marks }}</summary>
<form method="POST" action="{{ route('campus.exams.result',[$school,$campus,$exam]) }}" class="mt-3 grid gap-2 sm:grid-cols-2">@csrf
<label>Student<select name="class_enrollment_id" required class="w-full"><option value="">Select student</option>@foreach($enrollments->where('class_section_id',$exam->class_section_id) as $enrollment)<option value="{{ $enrollment->id }}">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</option>@endforeach</select></label>
<label>Status<select name="status" class="w-full" required><option value="graded">Graded</option><option value="absent">Absent</option><option value="exempt">Exempt</option></select></label>
<label>Marks (graded only)<input type="number" name="marks_obtained" min="0" max="{{ $exam->max_marks }}" class="w-full"></label>
<label>Remarks<input name="remarks" maxlength="2000" class="w-full"></label>
<div class="sm:col-span-2"><button class="rounded-xl bg-indigo-600 px-4 py-2 text-white">Save result</button></div>
</form></details>
@empty<p>No exams for this campus.</p>@endforelse
{{ $exams->links() }}
</x-ui.card>
<x-ui.card class="mt-5" title="Verified results">
@forelse($results as $result)<div class="border-b py-2 text-sm">{{ $result->enrollment->student->first_name }} · {{ $result->examination->name }} · {{ $result->status }} · {{ $result->marks_obtained ?? '—' }}</div>@empty<p>No results recorded.</p>@endforelse
{{ $results->links() }}
</x-ui.card>
@endsection