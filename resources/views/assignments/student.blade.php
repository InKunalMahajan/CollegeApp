@extends('layouts.app')
@section('title','My Assignments')
@section('heading','My Assignments')
@section('content')
@if(!$link)<div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">Your student login is not linked to an enrollment record. Ask your administrator to link your account.</div>@endif
@if($errors->any())<div role="alert" class="my-5 rounded-lg bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="space-y-5">@forelse($assignments as $assignment)
@php($submission = $submissions->get($assignment->id))
<section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><div class="flex flex-wrap justify-between gap-3"><h2 class="font-semibold">{{ $assignment->title }}</h2><p class="text-sm text-slate-500">Due {{ $assignment->due_at->format('d M Y H:i') }}</p></div><p class="mt-2 text-sm text-slate-600">{{ $assignment->teachingAssignment->subject->name }} · Maximum {{ $assignment->max_marks }} marks</p><p class="mt-3 whitespace-pre-line text-sm">{{ $assignment->instructions }}</p>
@if($submission?->graded_at)<p class="mt-4 rounded-lg bg-indigo-50 p-3 text-sm font-semibold">Grade: {{ $submission->marks }} / {{ $assignment->max_marks }}. {{ $submission->feedback }}</p>@else
@if($assignment->due_at->isFuture())<form method="POST" action="{{ route('assignments.submit',$assignment) }}" class="mt-5">@csrf<label for="answer-{{ $assignment->id }}" class="mb-2 block text-sm font-semibold">Your answer</label><textarea id="answer-{{ $assignment->id }}" name="response" required maxlength="20000" rows="5" class="w-full rounded-lg border border-slate-300 p-3">{{ $submission?->response }}</textarea><button class="mt-3 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">{{ $submission ? 'Update submission' : 'Submit answer' }}</button></form>@else<p class="mt-4 text-sm font-semibold text-rose-700">Submission deadline has passed.</p>@endif
@endif</section>
@empty<div class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-500">No published assignments for your enrolled classes.</div>@endforelse</div>
@endsection
