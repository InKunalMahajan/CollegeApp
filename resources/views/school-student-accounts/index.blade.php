@extends('layouts.app')
@section('title','Student Login Accounts')
@section('heading','Student Login Accounts')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
<div><h2 class="text-xl font-semibold">{{ $school->name }}</h2>
<p class="mt-1 text-sm text-slate-600">Link an existing student-role login to one verified student record in this school.</p></div>
<a href="{{ route('school-admin.index',$school) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold">Back to school administration</a>
</div>
@if($errors->any())<div role="alert" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
<ul class="list-disc pl-5">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
@if(session('success'))<p role="status" class="mb-4 rounded-lg bg-slate-100 p-4 text-sm">{{ session('success') }}</p>@endif

<section class="mb-6 rounded-xl border border-slate-200 bg-white p-6">
<h3 class="mb-3 font-semibold">Link student login</h3>
<form method="POST" action="{{ route('school-student-accounts.store',$school) }}" class="grid gap-4 sm:grid-cols-2">@csrf
<div><label for="student_id" class="mb-1 block text-sm font-medium">Student in this school</label>
<select id="student_id" name="student_id" required class="w-full rounded-lg border border-slate-300 p-2.5">
<option value="">Select student</option>
@foreach($students as $student)
<option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->admission_number }} · {{ $student->first_name }} {{ $student->last_name }}</option>
@endforeach
</select></div>
<div><label for="student-login-email" class="mb-1 block text-sm font-medium">Existing student login email</label>
<input id="student-login-email" name="email" type="email" maxlength="255" autocomplete="off" required value="{{ old('email') }}" class="w-full rounded-lg border border-slate-300 p-2.5">
<p class="mt-1 text-xs text-slate-500">Only existing accounts with the student role can be linked. Each login and student can have one link.</p></div>
<div class="sm:col-span-2"><button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Link account</button></div>
</form>
</section>

<section class="rounded-xl border border-slate-200 bg-white p-6">
<h3 class="mb-4 font-semibold">Linked accounts in {{ $school->name }}</h3>
<div class="space-y-3">
@forelse($links as $link)
<div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
<div class="text-sm"><strong>{{ $link->student->first_name }} {{ $link->student->last_name }}</strong>
<p class="text-slate-500">{{ $link->student->admission_number }} · {{ $link->user->email }}</p></div>
<form method="POST" action="{{ route('school-student-accounts.destroy',[$school,$link]) }}" onsubmit="return confirm('Unlink this login? Previous submissions will be retained.')">
@csrf @method('DELETE')
<button class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-700">Unlink</button></form>
</div>
@empty<p class="text-sm text-slate-500">No linked student accounts in this school.</p>@endforelse
</div>
<div class="mt-4">{{ $links->links() }}</div>
</section>
@endsection
