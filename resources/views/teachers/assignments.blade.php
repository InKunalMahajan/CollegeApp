@extends('layouts.app')
@section('title', 'Teaching assignments')
@section('heading', 'Teaching assignments')
@section('content')
@if($errors->any())<div role="alert" class="mb-5 rounded-lg bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="grid gap-6 xl:grid-cols-2">
    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Assign a subject</h2><p class="mt-1 text-sm text-slate-500">Assign a teacher to a subject for one class section.</p>
        <form method="POST" action="{{ route('teacher-assignments.subject.store') }}" class="mt-5 space-y-4">@csrf
            <label for="subject_teacher" class="block text-sm font-semibold">Teacher</label><select required id="subject_teacher" name="teacher_profile_id" class="w-full rounded-lg border border-slate-300 p-3"><option value="">Select teacher</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->user->name }} — {{ $teacher->campus->name }}</option>@endforeach</select>
            <label for="subject_id" class="block text-sm font-semibold">Subject</label><select required id="subject_id" name="subject_id" class="w-full rounded-lg border border-slate-300 p-3"><option value="">Select subject</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }} (School #{{ $subject->school_id }})</option>@endforeach</select>
            <label for="subject_class" class="block text-sm font-semibold">Class section</label><select required id="subject_class" name="class_section_id" class="w-full rounded-lg border border-slate-300 p-3"><option value="">Select class</option>@foreach($sections as $section)<option value="{{ $section->id }}">{{ $section->name }} — {{ $section->campus->name }}</option>@endforeach</select>
            <button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Assign subject</button>
        </form>
    </section>
    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Assign class teacher</h2><p class="mt-1 text-sm text-slate-500">Each class section has at most one assigned class teacher.</p>
        <form method="POST" action="{{ route('teacher-assignments.class.store') }}" class="mt-5 space-y-4">@csrf
            <label for="class_teacher" class="block text-sm font-semibold">Teacher</label><select required id="class_teacher" name="teacher_profile_id" class="w-full rounded-lg border border-slate-300 p-3"><option value="">Select teacher</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->user->name }} — {{ $teacher->campus->name }}</option>@endforeach</select>
            <label for="teacher_class" class="block text-sm font-semibold">Class section</label><select required id="teacher_class" name="class_section_id" class="w-full rounded-lg border border-slate-300 p-3"><option value="">Select class</option>@foreach($sections as $section)<option value="{{ $section->id }}">{{ $section->name }} — {{ $section->campus->name }}</option>@endforeach</select>
            <button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Assign class teacher</button>
        </form>
    </section>
</div>
<div class="mt-8 grid gap-6 xl:grid-cols-2">
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-5"><h2 class="font-semibold">Subject assignments</h2></div>
        <div class="divide-y divide-slate-100">@forelse($subjectAssignments as $item)
        <div class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
            <div><p class="font-semibold">{{ $item->teacherProfile->user->name }}</p><p class="text-slate-600">{{ $item->subject->name }} · {{ $item->classSection->name }}</p></div>
            <form method="POST" action="{{ route('teacher-assignments.subject.destroy', $item) }}">@csrf @method('DELETE')<button class="font-semibold text-rose-700">Remove</button></form>
        </div>@empty<div class="p-5 text-sm text-slate-500">No subject assignments yet.</div>@endforelse</div><div class="p-4">{{ $subjectAssignments->links() }}</div>
    </section>
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-5"><h2 class="font-semibold">Class teachers</h2></div>
        <div class="divide-y divide-slate-100">@forelse($classAssignments as $item)
        <div class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
            <div><p class="font-semibold">{{ $item->teacherProfile->user->name }}</p><p class="text-slate-600">{{ $item->classSection->name }}</p></div>
            <form method="POST" action="{{ route('teacher-assignments.class.destroy', $item) }}">@csrf @method('DELETE')<button class="font-semibold text-rose-700">Remove</button></form>
        </div>@empty<div class="p-5 text-sm text-slate-500">No class teachers assigned.</div>@endforelse</div><div class="p-4">{{ $classAssignments->links() }}</div>
    </section>
</div>
@endsection
