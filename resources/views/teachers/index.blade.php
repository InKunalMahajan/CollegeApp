@extends('layouts.app')
@section('title', 'Teachers')
@section('heading', 'Teacher Management')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-slate-600">Profiles connect teacher login accounts to schools and campuses.</p>
    <div class="flex gap-3">
        <a href="{{ route('teacher-assignments.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold hover:bg-slate-100">Assignments</a>
        <a href="{{ route('teachers.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">+ Add teacher</a>
    </div>
</div>
<div class="schoolapp-table-wrap overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
<table class="w-full min-w-[650px] text-left text-sm">
    <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-4">Teacher</th><th class="px-5 py-4">Employee code</th><th class="px-5 py-4">School</th><th class="px-5 py-4">Campus</th><th class="px-5 py-4">Designation</th><th class="px-5 py-4">Action</th></tr></thead>
    <tbody class="divide-y divide-slate-100">
    @forelse($teachers as $teacher)
        <tr><td class="px-5 py-4 font-semibold">{{ $teacher->user->name }}</td><td class="px-5 py-4">{{ $teacher->employee_code }}</td><td class="px-5 py-4">{{ $teacher->school->name }}</td><td class="px-5 py-4">{{ $teacher->campus->name }}</td><td class="px-5 py-4">{{ $teacher->designation ?? '—' }}</td><td class="px-5 py-4"><a class="font-medium text-indigo-700 hover:underline" href="{{ route('teachers.edit', $teacher) }}">Edit</a></td></tr>
    @empty
        <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">No teacher profiles. Create a teacher login account first, then add a profile.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="mt-5">{{ $teachers->links() }}</div>
@endsection
