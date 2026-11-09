@extends('layouts.app')
@section('title','Teaching Assignments')
@section('heading','Assignment Management')
@section('content')
<p class="mb-5 text-sm text-slate-600">Create a draft and publish it for students enrolled in your assigned class. Submissions are text-only in this first version.</p>
@if($errors->any())<div role="alert" class="mb-5 rounded-lg bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('assignments.store') }}" class="mb-8 grid gap-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm md:grid-cols-2">@csrf
<div><label for="assignment" class="mb-1 block text-sm font-semibold">Teaching allocation</label><select id="assignment" name="teacher_subject_assignment_id" required class="w-full rounded-lg border border-slate-300 p-2.5"><option value="">Select class and subject</option>@foreach($teaching as $row)<option value="{{ $row->id }}">{{ $row->classSection->name }} · {{ $row->subject->name }}</option>@endforeach</select></div>
<div><label for="title" class="mb-1 block text-sm font-semibold">Title</label><input id="title" name="title" required maxlength="200" value="{{ old('title') }}" class="w-full rounded-lg border border-slate-300 p-2.5"></div>
<div class="md:col-span-2"><label for="instructions" class="mb-1 block text-sm font-semibold">Instructions</label><textarea id="instructions" name="instructions" required rows="3" maxlength="10000" class="w-full rounded-lg border border-slate-300 p-2.5">{{ old('instructions') }}</textarea></div>
<div><label for="due_at" class="mb-1 block text-sm font-semibold">Due date and time</label><input id="due_at" name="due_at" required type="datetime-local" class="w-full rounded-lg border border-slate-300 p-2.5"></div>
<div><label for="max_marks" class="mb-1 block text-sm font-semibold">Maximum marks</label><input id="max_marks" name="max_marks" required type="number" min="1" max="10000" value="100" class="w-full rounded-lg border border-slate-300 p-2.5"></div>
<div><button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white">Create draft</button></div>
</form>
<section class="schoolapp-panel rounded-xl border border-slate-200 bg-white shadow-sm"><h2 class="border-b border-slate-100 p-5 font-semibold">My assignments</h2>
<div class="divide-y divide-slate-100">@forelse($assignments as $assignment)<div class="flex flex-wrap items-center justify-between gap-4 p-5"><div><p class="font-semibold">{{ $assignment->title }}</p><p class="mt-1 text-sm text-slate-600">{{ $assignment->teachingAssignment->classSection->name }} · {{ $assignment->teachingAssignment->subject->name }} · {{ ucfirst($assignment->status) }} · Due {{ $assignment->due_at->format('d M Y H:i') }}</p></div><div class="flex gap-3"><a href="{{ route('assignments.submissions',$assignment) }}" class="text-sm font-semibold text-indigo-700">Submissions</a>@if($assignment->status === 'draft')<form method="POST" action="{{ route('assignments.publish',$assignment) }}">@csrf<button class="text-sm font-semibold text-emerald-700">Publish</button></form>@endif</div></div>@empty<div class="p-6 text-sm text-slate-500">No assignments yet.</div>@endforelse</div><div class="p-4">{{ $assignments->links() }}</div></section>
@endsection
