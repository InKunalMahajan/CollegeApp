@extends('layouts.app')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
<div class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-sm text-slate-500">Welcome back, {{ auth()->user()->name }}.</p>
        <h2 class="mt-1 text-xl font-semibold text-slate-900">School overview</h2>
        <p class="mt-1 text-sm text-slate-500">Statistics are calculated from your current database records.</p>
    </div>
    @if($canViewStudents)
        <a href="{{ route('students.create') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">+ Add student</a>
    @endif
</div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @if($canViewStudents)
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Total students</p>
            <p class="mt-3 text-3xl font-bold tracking-tight">{{ number_format($students) }}</p>
            <p class="mt-2 text-xs text-slate-400">All admissions</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Active students</p>
            <p class="mt-3 text-3xl font-bold tracking-tight">{{ number_format($active) }}</p>
            <p class="mt-2 text-xs text-slate-400">Currently active</p>
        </div>
    @endif
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-slate-500">Courses</p>
        <p class="mt-3 text-3xl font-bold tracking-tight">{{ number_format($courses) }}</p>
        <p class="mt-2 text-xs text-slate-400">Registered programs</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-slate-500">Departments</p>
        <p class="mt-3 text-3xl font-bold tracking-tight">{{ number_format($departments) }}</p>
        <p class="mt-2 text-xs text-slate-400">Academic departments</p>
    </div>
</div>

@if($canViewStudents)
<div class="mt-8 grid gap-6 xl:grid-cols-3">
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm xl:col-span-2" aria-labelledby="recent-students-heading">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-5 sm:px-6">
            <div>
                <h3 id="recent-students-heading" class="text-base font-semibold">Recent students</h3>
                <p class="mt-1 text-xs text-slate-500">The five most recently added records</p>
            </div>
            <a href="{{ route('students.index') }}" class="text-sm font-semibold text-indigo-700 hover:underline">View all students</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th scope="col" class="px-5 py-3">Admission</th><th scope="col" class="px-5 py-3">Student</th><th scope="col" class="px-5 py-3">Course</th><th scope="col" class="px-5 py-3">Status</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recent as $student)
                    <tr>
                        <td class="whitespace-nowrap px-5 py-4 font-mono text-xs text-slate-500">{{ $student->admission_number }}</td>
                        <td class="px-5 py-4 font-medium">{{ $student->first_name }} {{ $student->last_name }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $student->course?->name ?? 'Unassigned' }}</td>
                        <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold capitalize text-slate-700">{{ $student->status }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">No student records yet. Add your first student to get started.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="quick-actions-heading">
        <h3 id="quick-actions-heading" class="text-base font-semibold">Quick actions</h3>
        <p class="mt-1 text-sm text-slate-500">Common administration tasks.</p>
        <div class="mt-5 space-y-3">
            <a href="{{ route('students.create') }}" class="block rounded-lg border border-slate-200 p-4 text-sm font-semibold hover:border-indigo-300 hover:bg-indigo-50">Register a student <span class="block pt-1 text-xs font-normal text-slate-500">Create a new admission record</span></a>
            <a href="{{ route('students.index') }}" class="block rounded-lg border border-slate-200 p-4 text-sm font-semibold hover:border-indigo-300 hover:bg-indigo-50">Manage students <span class="block pt-1 text-xs font-normal text-slate-500">Search and update records</span></a>
        </div>
    </section>
</div>
@else
<section class="mt-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h3 class="text-base font-semibold">Your workspace</h3>
    <p class="mt-2 text-sm text-slate-600">You are signed in as {{ ucfirst(auth()->user()->role) }}. Student administration is restricted to authorized staff.</p>
</section>
@endif
@endsection
