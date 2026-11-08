@extends('layouts.app')
@section('title','Student Profile')
@section('heading','Student Profile')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
<a href="{{ route('school-students.index',$school) }}" class="text-sm font-semibold text-indigo-700" class="inline-flex items-center gap-2"><x-ui.icon name="arrow-left"/> Back to {{ $school->name }} students</a>
<a href="{{ route('school-students.edit',[$school,$student]) }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white" class="inline-flex items-center gap-2"><x-ui.icon name="edit"/> Edit profile</a></div>
@if(session('success'))<p role="status" class="mb-5 rounded-lg bg-slate-100 p-4 text-sm">{{ session('success') }}</p>@endif
<div class="max-w-2xl rounded-xl border border-slate-200 bg-white p-6">
<h2 class="text-xl font-semibold" class="flex items-center gap-2"><x-ui.icon name="user" size="size-5"/> {{ $student->first_name }} {{ $student->last_name }}</h2>
<dl class="mt-5 grid gap-4 sm:grid-cols-2">
<div><dt class="text-xs text-slate-500">Admission number</dt><dd class="font-medium">{{ $student->admission_number }}</dd></div>
<div><dt class="text-xs text-slate-500">School</dt><dd class="font-medium">{{ $school->name }}</dd></div>
<div><dt class="text-xs text-slate-500">Status</dt><dd class="font-medium">{{ ucfirst($student->status ?? 'active') }}</dd></div>
<div><dt class="text-xs text-slate-500">Admission date</dt><dd class="font-medium">{{ $student->admission_date?->format('d M Y') }}</dd></div>
<div class="sm:col-span-2"><dt class="text-xs text-slate-500">Email</dt><dd class="font-medium">{{ $student->email ?: 'Not provided' }}</dd></div>
</dl></div>
@endsection
