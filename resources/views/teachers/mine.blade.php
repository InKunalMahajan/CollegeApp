@extends('layouts.app')
@section('title', 'My teaching')
@section('heading', 'My teaching')
@section('content')
@if(!$profile)
<section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><h2 class="text-lg font-semibold">Teacher profile pending</h2><p class="mt-2 text-sm text-slate-600">An administrator must link your teacher account to a school and campus before your assignments appear.</p></section>
@else
<div class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-lg font-semibold">{{ auth()->user()->name }}</h2>
    <p class="mt-2 text-sm text-slate-600">{{ $profile->school->name }} · {{ $profile->campus->name }} · {{ $profile->designation ?? 'Teacher' }}</p>
</div>
<div class="grid gap-6 lg:grid-cols-2">
    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 font-semibold">My subjects</h3><div class="space-y-3">
        @forelse($profile->subjectAssignments as $assignment)
            <div class="rounded-lg bg-slate-50 px-4 py-3"><p class="text-sm font-semibold">{{ $assignment->subject->name }}</p><p class="mt-1 text-xs text-slate-600">{{ $assignment->classSection->name }}</p></div>
        @empty<p class="text-sm text-slate-500">No subjects assigned yet.</p>@endforelse
        </div>
    </section>
    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 font-semibold">My class teacher responsibilities</h3><div class="space-y-3">
        @forelse($profile->classTeacherAssignments as $assignment)
            <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm font-semibold">{{ $assignment->classSection->name }}</div>
        @empty<p class="text-sm text-slate-500">No class teacher allocation yet.</p>@endforelse
        </div>
    </section>
</div>
@endif
@endsection
