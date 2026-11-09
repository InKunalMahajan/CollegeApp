@extends('layouts.app')
@section('title', 'My lesson progress')
@section('heading', 'My Lesson Progress')
@section('content')
<p class="mb-6 text-sm text-slate-600">Track lessons for your assigned subjects and classes. Your entries do not modify another teacher's progress.</p>
@if($errors->any())<div role="alert" class="mb-6 rounded-lg bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="space-y-6">
@forelse($assignments as $assignment)
<section class="schoolapp-panel rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="text-lg font-semibold">{{ $assignment->subject->name }} — {{ $assignment->classSection->name }}</h2>
@php($matchingUnits = $units->where('subject_id', $assignment->subject_id))
@forelse($matchingUnits as $unit)<div class="mt-5"><h3 class="border-b border-slate-100 pb-2 text-sm font-semibold">Unit {{ $unit->sequence }}: {{ $unit->title }}</h3>
<div class="mt-3 space-y-3">@forelse($unit->lessons as $lesson)
@php($row = $progress->get($assignment->id.':'.$lesson->id))
<form method="POST" action="{{ route('curriculum.progress.store') }}" class="grid items-end gap-3 rounded-lg bg-slate-50 p-4 lg:grid-cols-[minmax(150px,1fr)_160px_160px_minmax(180px,1fr)_auto]">@csrf
<input type="hidden" name="teacher_subject_assignment_id" value="{{ $assignment->id }}"><input type="hidden" name="curriculum_lesson_id" value="{{ $lesson->id }}">
<div><p class="text-sm font-medium">{{ $lesson->sequence }}. {{ $lesson->title }}</p><p class="text-xs text-slate-500">{{ $lesson->estimated_minutes }} minutes</p></div>
<div><label for="status-{{ $assignment->id }}-{{ $lesson->id }}" class="mb-1 block text-xs font-semibold">Status</label><select name="status" id="status-{{ $assignment->id }}-{{ $lesson->id }}" class="w-full rounded-lg border border-slate-300 p-2 text-sm">@foreach(['not_started' => 'Not started','in_progress' => 'In progress','completed' => 'Completed'] as $key => $label)<option value="{{ $key }}" @selected(($row?->status ?? 'not_started') === $key)>{{ $label }}</option>@endforeach</select></div>
<div><label for="date-{{ $assignment->id }}-{{ $lesson->id }}" class="mb-1 block text-xs font-semibold">Completed on</label><input type="date" name="completed_on" id="date-{{ $assignment->id }}-{{ $lesson->id }}" max="{{ now()->toDateString() }}" value="{{ $row?->completed_on?->format('Y-m-d') }}" class="w-full rounded-lg border border-slate-300 p-2 text-sm"></div>
<div><label for="remarks-{{ $assignment->id }}-{{ $lesson->id }}" class="mb-1 block text-xs font-semibold">Remarks</label><input name="remarks" id="remarks-{{ $assignment->id }}-{{ $lesson->id }}" value="{{ $row?->remarks }}" maxlength="2000" class="w-full rounded-lg border border-slate-300 p-2 text-sm"></div>
<button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Save</button></form>
@empty<p class="text-sm text-slate-500">No lessons for this unit.</p>@endforelse</div></div>
@empty<p class="mt-4 text-sm text-slate-500">No curriculum units yet.</p>@endforelse</section>
@empty<div class="schoolapp-panel rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-600">You have no assigned subjects. Contact your administrator.</div>@endforelse
</div>
@endsection
