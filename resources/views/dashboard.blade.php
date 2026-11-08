@extends('layouts.app')
@section('title','Dashboard')
@section('heading','Dashboard')
@section('content')
<p class="mb-7 text-slate-500">Welcome to CollegeApp, {{ auth()->user()->name }}.</p>
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
@foreach(array_filter([ $canViewStudents ? ['Total Students',$students] : null, $canViewStudents ? ['Active Students',$active] : null, ['Courses',$courses], ['Departments',$departments] ]) as [$label,$count])
<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-sm text-slate-500">{{ $label }}</p><p class="mt-3 text-3xl font-bold">{{ number_format($count) }}</p></div>
@endforeach
</div>
@if($canViewStudents)
<section class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
<div class="flex items-center justify-between gap-4 border-b border-slate-100 p-6"><h2 class="text-lg font-semibold">Recent students</h2><a class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white" href="{{ route('students.create') }}">Add student</a></div>
<div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50"><tr><th class="p-4">Admission No.</th><th class="p-4">Name</th><th class="p-4">Course</th><th class="p-4">Status</th></tr></thead><tbody class="divide-y divide-slate-100">
@forelse($recent as $student)<tr><td class="p-4">{{ $student->admission_number }}</td><td class="p-4">{{ $student->first_name }} {{ $student->last_name }}</td><td class="p-4">{{ $student->course?->name ?? 'Unassigned' }}</td><td class="p-4 capitalize">{{ $student->status }}</td></tr>
@empty<tr><td colspan="4" class="p-8 text-center text-slate-500">No students yet.</td></tr>@endforelse
</tbody></table></div></section>
@endif
@endsection
