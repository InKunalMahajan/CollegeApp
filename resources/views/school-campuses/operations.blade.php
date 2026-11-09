@extends('layouts.app')
@section('title', 'Campus Operations')
@section('heading', 'Campus Operations')
@section('content')
<x-ui.page-heading :title="$campus->name.' · Operations'" :description="$school->name.' · Read-only, campus-scoped metrics'"/>
<div class="mb-5">
    <a class="text-sm font-semibold text-indigo-700 hover:underline" href="{{ route('campus-workspace.index', [$school, $campus]) }}">← Back to campus workspace</a>
</div>
<x-ui.alert type="info" class="mb-5">This page shows counts only. Fees, exams, attendance, library and academic editing remain organization-admin-only until dedicated campus-safe workflows are implemented and tested. Unassigned or mismatched student/class records are excluded.</x-ui.alert>
@php
    $labels = [
        'teachers' => 'Teacher profiles',
        'classes' => 'Class sections',
        'enrollments' => 'Verified student enrollments',
        'student_attendance' => 'Student attendance entries',
        'staff_attendance' => 'Staff attendance entries',
        'exams' => 'Examinations',
        'results' => 'Exam results',
        'fee_charges' => 'Fee charges',
        'fee_payments' => 'Fee payment entries',
        'library_loans' => 'Library loans',
        'timetable_entries' => 'Timetable entries',
    ];
@endphp
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach($labels as $key => $label)
        <x-ui.card :title="$label">
            <p class="text-3xl font-semibold tabular-nums">{{ number_format($numbers[$key]) }}</p>
        </x-ui.card>
    @endforeach
</div>
@endsection
