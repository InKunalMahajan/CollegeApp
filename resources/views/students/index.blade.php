@extends('layouts.app')
@section('title','Students')
@section('heading','Students')
@section('content')
<div class="mb-6 flex flex-wrap justify-between gap-4">
<form class="flex gap-2" action="{{ route('students.index') }}" method="GET" role="search">
<label for="search" class="sr-only">Search students</label><input id="search" type="search" name="search" value="{{ $search }}" placeholder="Search name or admission no." class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm"><button class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Search</button>
</form><a class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white" href="{{ route('students.create') }}">+ Add Student</a></div>
<div class="schoolapp-table-wrap overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm"><table class="w-full text-left text-sm">
<thead class="bg-slate-50"><tr><th class="p-4">Admission</th><th class="p-4">Name</th><th class="p-4">Course</th><th class="p-4">Status</th><th class="p-4 text-right">Actions</th></tr></thead>
<tbody class="divide-y divide-slate-100">@forelse($students as $student)
<tr><td class="p-4">{{ $student->admission_number }}</td><td class="p-4">{{ $student->first_name }} {{ $student->last_name }}</td><td class="p-4">{{ $student->course?->name ?? 'Unassigned' }}</td><td class="p-4 capitalize">{{ $student->status }}</td><td class="p-4"><div class="flex justify-end gap-3"><a class="text-indigo-700 hover:underline" href="{{ route('students.edit', $student) }}">Edit</a><form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('Delete this student record?')">@csrf @method('DELETE')<button class="text-rose-700 hover:underline">Delete</button></form></div></td></tr>
@empty<tr><td colspan="5" class="p-10 text-center text-slate-500">No students found.</td></tr>@endforelse
</tbody></table></div><div class="mt-5">{{ $students->links() }}</div>
@endsection
