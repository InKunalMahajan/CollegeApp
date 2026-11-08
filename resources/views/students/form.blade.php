@extends('layouts.app')
@section('title',$student->exists ? 'Edit Student' : 'New Student')
@section('heading',$student->exists ? 'Edit Student' : 'New Student')
@section('content')
<form method="POST" action="{{ $student->exists ? route('students.update',$student) : route('students.store') }}" class="max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
@csrf @if($student->exists) @method('PUT') @endif
@if($errors->any())<div role="alert" class="mb-5 rounded-lg bg-rose-50 p-4 text-rose-700"><p>Please correct:</p><ul class="list-disc pl-6">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="grid gap-5 sm:grid-cols-2">
@foreach([['admission_number','Admission number'],['first_name','First name'],['last_name','Last name'],['email','Email'],['admission_date','Admission date']] as [$field,$label])
<div><label for="{{ $field }}" class="mb-2 block text-sm font-medium">{{ $label }}</label>
<input id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'admission_date' ? 'date' : ($field === 'email' ? 'email' : 'text') }}" value="{{ old($field, $field === 'admission_date' ? $student->admission_date?->format('Y-m-d') : $student->$field) }}" @required($field !== 'email') class="w-full rounded-lg border border-slate-300 px-3 py-2">
@error($field)<span class="text-xs text-rose-700">{{ $message }}</span>@enderror</div>
@endforeach
<div><label for="course_id" class="mb-2 block text-sm font-medium">Course</label><select id="course_id" name="course_id" class="w-full rounded-lg border border-slate-300 px-3 py-2"><option value="">Not assigned</option>@foreach($courses as $course)<option value="{{ $course->id }}" @selected(old('course_id',$student->course_id) == $course->id)>{{ $course->name }}</option>@endforeach</select></div>
<div><label for="status" class="mb-2 block text-sm font-medium">Status</label><select id="status" name="status" required class="w-full rounded-lg border border-slate-300 px-3 py-2">@foreach(['active','inactive','graduated'] as $status)<option value="{{ $status }}" @selected(old('status',$student->status ?? 'active') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
</div><div class="mt-8 flex gap-4 border-t border-slate-100 pt-5"><button class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white">Save Student</button><a class="py-2 text-sm text-slate-600" href="{{ route('students.index') }}">Cancel</a></div></form>
@endsection
