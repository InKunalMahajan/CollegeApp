@extends('layouts.app')
@section('title', 'Examination results')
@section('heading', 'Examination Results')
@section('content')
<div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-semibold">{{ $exam->name }} · {{ $exam->subject->name }}</h2><p class="mt-2 text-sm text-slate-600">Class: {{ $exam->classSection->name }} · Maximum: {{ $exam->max_marks }} · Pass: {{ $exam->pass_marks }}</p><p class="mt-1 text-xs text-slate-500">Draft administrative grade bands: A+ ≥90%, A ≥75%, B ≥60%, C ≥40%, D below 40% when passing. F is below the exam's passing marks. These are not board-certified grades.</p></div>
@if($errors->any())<div role="alert" class="mb-5 rounded-lg bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="schoolapp-table-wrap overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm"><table class="w-full min-w-[730px] text-left text-sm"><thead class="bg-slate-50 text-slate-600"><tr><th class="px-5 py-4">Student</th><th class="px-5 py-4">Status</th><th class="px-5 py-4">Marks</th><th class="px-5 py-4">Grade</th><th class="px-5 py-4">Save result</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($enrollments as $enrollment)
@php($result = $results->get($enrollment->id))
<tr><td class="px-5 py-4 font-medium">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}<div class="mt-1 text-xs text-slate-500">{{ $enrollment->student->admission_number }}</div></td>
<td colspan="4" class="px-5 py-3"><form method="POST" action="{{ route('exams.results.store', $exam) }}" class="grid items-center gap-3 sm:grid-cols-[1fr_1fr_auto_auto]">@csrf
<input type="hidden" name="class_enrollment_id" value="{{ $enrollment->id }}">
<label class="sr-only" for="status-{{ $enrollment->id }}">Result status</label><select id="status-{{ $enrollment->id }}" name="status" class="rounded-lg border border-slate-300 p-2"><option value="graded" @selected(($result?->status ?? 'graded') === 'graded')>Graded</option><option value="absent" @selected($result?->status === 'absent')>Absent</option><option value="exempt" @selected($result?->status === 'exempt')>Exempt</option></select>
<label class="sr-only" for="marks-{{ $enrollment->id }}">Marks obtained</label><input id="marks-{{ $enrollment->id }}" name="marks_obtained" type="number" min="0" max="{{ $exam->max_marks }}" value="{{ $result?->marks_obtained }}" placeholder="Marks" class="w-full rounded-lg border border-slate-300 p-2">
<span class="text-xs text-slate-600">{{ $result?->grade ?? '—' }} @if($result?->percentage !== null) ({{ $result->percentage }}%) @endif</span><button class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white">Save</button></form></td>
</tr>@empty<tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">No students enrolled in this class.</td></tr>@endforelse</tbody></table></div>
<div class="mt-6"><a href="{{ route('exams.index') }}" class="text-sm font-semibold text-indigo-700 hover:underline">Back to examinations</a></div>
@endsection
