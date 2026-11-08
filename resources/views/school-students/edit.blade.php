@extends('layouts.app')
@section('title','Edit Student')
@section('heading','Edit Student Profile')
@section('content')
<a href="{{ route('school-students.show',[$school,$student]) }}" class="mb-5 inline-block text-sm font-semibold text-indigo-700 inline-flex items-center gap-2"><x-ui.icon name="arrow-left"/> Back to student profile</a>
@if($errors->any())<div role="alert" class="mb-5 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('school-students.update',[$school,$student]) }}" class="grid max-w-3xl gap-4 rounded-xl border border-slate-200 bg-white p-6 sm:grid-cols-2">@csrf @method('PUT')
<div><label for="admission_number" class="block text-sm font-medium">Admission number</label><input id="admission_number" name="admission_number" required maxlength="40" value="{{ old('admission_number',$student->admission_number) }}" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5"></div>
<div><label for="email" class="block text-sm font-medium">Email (optional)</label><input id="email" name="email" type="email" maxlength="255" value="{{ old('email',$student->email) }}" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5"></div>
<div><label for="first_name" class="block text-sm font-medium">First name</label><input id="first_name" name="first_name" required maxlength="255" value="{{ old('first_name',$student->first_name) }}" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5"></div>
<div><label for="last_name" class="block text-sm font-medium">Last name</label><input id="last_name" name="last_name" required maxlength="255" value="{{ old('last_name',$student->last_name) }}" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5"></div>
<div><label for="admission_date" class="block text-sm font-medium">Admission date</label><input id="admission_date" type="date" name="admission_date" required value="{{ old('admission_date',$student->admission_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5"></div>
<div><label for="status" class="block text-sm font-medium">Status</label><select id="status" name="status" required class="mt-1 w-full rounded-lg border border-slate-300 p-2.5">@foreach(['active'=>'Active','inactive'=>'Inactive','graduated'=>'Graduated'] as $value=>$label)<option value="{{ $value }}" @selected(old('status',$student->status)===$value)>{{ $label }}</option>@endforeach</select></div>
<div class="sm:col-span-2"><button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white inline-flex items-center gap-2"><x-ui.icon name="save"/> Save changes</button></div>
</form>
<p class="mt-3 text-xs text-slate-500">School ownership cannot be changed here. Profile updates are recorded in the school's audit history.</p>
@endsection
