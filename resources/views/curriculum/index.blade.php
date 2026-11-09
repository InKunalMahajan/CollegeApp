@extends('layouts.app')
@section('title', 'Curriculum')
@section('heading', 'Curriculum Management')
@section('content')
<p class="mb-6 text-sm text-slate-600">Create subject-specific syllabus units, then add ordered lessons with estimated teaching time and objectives.</p>
@if($errors->any())<div role="alert" class="mb-6 rounded-lg bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="mb-8 grid gap-6 xl:grid-cols-2">
<section class="schoolapp-panel rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><h2 class="mb-4 text-base font-semibold">Add syllabus unit</h2><form method="POST" action="{{ route('curriculum.units.store') }}" class="space-y-4">@csrf
<div><label for="subject_id" class="mb-2 block text-sm font-medium">Subject</label><select required id="subject_id" name="subject_id" class="w-full rounded-lg border border-slate-300 p-2.5"><option value="">Select subject</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->school->name }} · {{ $subject->name }}</option>@endforeach</select></div>
<div><label for="unit_title" class="mb-2 block text-sm font-medium">Unit title</label><input required id="unit_title" name="title" maxlength="200" class="w-full rounded-lg border border-slate-300 p-2.5"></div>
<div><label for="unit_sequence" class="mb-2 block text-sm font-medium">Unit order</label><input required id="unit_sequence" type="number" min="1" name="sequence" class="w-full rounded-lg border border-slate-300 p-2.5"></div>
<div><label for="outcome" class="mb-2 block text-sm font-medium">Learning outcome (optional)</label><textarea id="outcome" name="learning_outcome" rows="2" class="w-full rounded-lg border border-slate-300 p-2.5"></textarea></div>
<button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Create unit</button></form></section>
<section class="schoolapp-panel rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><h2 class="mb-4 text-base font-semibold">Add lesson</h2><form method="POST" action="{{ route('curriculum.lessons.store') }}" class="space-y-4">@csrf
<div><label for="curriculum_unit_id" class="mb-2 block text-sm font-medium">Unit</label><select required id="curriculum_unit_id" name="curriculum_unit_id" class="w-full rounded-lg border border-slate-300 p-2.5"><option value="">Select unit</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->subject->name }} · {{ $unit->title }}</option>@endforeach</select></div>
<div><label for="lesson_title" class="mb-2 block text-sm font-medium">Lesson title</label><input required id="lesson_title" name="title" maxlength="200" class="w-full rounded-lg border border-slate-300 p-2.5"></div>
<div><label for="lesson_sequence" class="mb-2 block text-sm font-medium">Lesson order</label><input required id="lesson_sequence" type="number" min="1" name="sequence" class="w-full rounded-lg border border-slate-300 p-2.5"></div>
<div><label for="estimated_minutes" class="mb-2 block text-sm font-medium">Estimated minutes</label><input required id="estimated_minutes" type="number" min="1" name="estimated_minutes" value="45" class="w-full rounded-lg border border-slate-300 p-2.5"></div>
<div><label for="objective" class="mb-2 block text-sm font-medium">Objective (optional)</label><textarea id="objective" name="objective" rows="2" class="w-full rounded-lg border border-slate-300 p-2.5"></textarea></div>
<button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Create lesson</button></form></section>
</div>
<section class="space-y-4"><h2 class="font-semibold">Syllabus structure</h2>@forelse($units as $unit)<div class="schoolapp-panel rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h3 class="font-semibold">{{ $unit->subject->name }} — Unit {{ $unit->sequence }}: {{ $unit->title }}</h3><p class="mt-1 text-sm text-slate-600">{{ $unit->learning_outcome }}</p><div class="mt-4 space-y-2">@forelse($unit->lessons->sortBy('sequence') as $lesson)<div class="rounded-lg bg-slate-50 p-3 text-sm">{{ $lesson->sequence }}. {{ $lesson->title }} <span class="text-slate-500">({{ $lesson->estimated_minutes }} minutes)</span></div>@empty<p class="text-sm text-slate-500">No lessons yet.</p>@endforelse</div></div>@empty<p class="schoolapp-panel rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-500">No syllabus units yet.</p>@endforelse</section>
@endsection
