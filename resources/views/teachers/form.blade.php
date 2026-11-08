@extends('layouts.app')
@section('title', $teacher ? 'Edit teacher' : 'Add teacher')
@section('heading', $teacher ? 'Edit teacher profile' : 'Add teacher profile')
@section('content')
<form method="POST" action="{{ $teacher ? route('teachers.update', $teacher) : route('teachers.store') }}" class="max-w-2xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf
    @if($teacher) @method('PUT') @endif
    <p class="mb-5 text-sm text-slate-600">Create a login with the Teacher role under User accounts before adding a teacher profile.</p>
    @if($errors->any())<div role="alert" class="mb-5 rounded-lg bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="space-y-5">
        <div><label for="user_id" class="mb-2 block text-sm font-semibold">Teacher account</label>
            <select required id="user_id" name="user_id" class="w-full rounded-lg border border-slate-300 px-4 py-2.5"><option value="">Select teacher</option>
                @foreach($users as $user)<option value="{{ $user->id }}" @selected((string) old('user_id', $teacher?->user_id) === (string) $user->id)>{{ $user->name }} — {{ $user->email }}</option>@endforeach
            </select></div>
        <div><label for="school_id" class="mb-2 block text-sm font-semibold">School</label>
            <select required id="school_id" name="school_id" class="w-full rounded-lg border border-slate-300 px-4 py-2.5"><option value="">Select school</option>
                @foreach($schools as $school)<option value="{{ $school->id }}" @selected((string) old('school_id', $teacher?->school_id) === (string) $school->id)>{{ $school->name }}</option>@endforeach
            </select></div>
        <div><label for="campus_id" class="mb-2 block text-sm font-semibold">Campus</label>
            <select required id="campus_id" name="campus_id" class="w-full rounded-lg border border-slate-300 px-4 py-2.5"><option value="">Select campus</option>
                @foreach($campuses as $campus)<option value="{{ $campus->id }}" @selected((string) old('campus_id', $teacher?->campus_id) === (string) $campus->id)>{{ $campus->school->name }} — {{ $campus->name }}</option>@endforeach
            </select><p class="mt-1 text-xs text-slate-500">Campus must belong to the selected school.</p></div>
        <div><label for="employee_code" class="mb-2 block text-sm font-semibold">Employee code</label><input required maxlength="40" name="employee_code" id="employee_code" value="{{ old('employee_code', $teacher?->employee_code) }}" class="w-full rounded-lg border border-slate-300 px-4 py-2.5"></div>
        <div><label for="designation" class="mb-2 block text-sm font-semibold">Designation</label><input maxlength="255" name="designation" id="designation" value="{{ old('designation', $teacher?->designation) }}" class="w-full rounded-lg border border-slate-300 px-4 py-2.5"></div>
        <div><label for="joined_on" class="mb-2 block text-sm font-semibold">Joining date</label><input type="date" name="joined_on" id="joined_on" value="{{ old('joined_on', $teacher?->joined_on?->format('Y-m-d')) }}" class="w-full rounded-lg border border-slate-300 px-4 py-2.5"></div>
    </div>
    <div class="mt-8 flex gap-4"><button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white">Save teacher</button><a href="{{ route('teachers.index') }}" class="px-3 py-2.5 text-sm font-semibold text-slate-600">Cancel</a></div>
</form>
@endsection
