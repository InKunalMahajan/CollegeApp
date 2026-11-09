@extends('layouts.app')
@section('title','Student Login Linking')
@section('heading','Student Login Linking')
@section('content')
<p class="mb-6 text-sm text-slate-600">Connect one student-role login to one student admission record. This is required for student submissions.</p>
@if($errors->any())<div role="alert" class="mb-5 rounded-lg bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('student-links.store') }}" class="mb-8 grid max-w-3xl gap-4 rounded-xl border border-slate-200 bg-white p-6 sm:grid-cols-2">@csrf
<div><label for="student_id" class="mb-1 block text-sm font-semibold">Student record</label><select id="student_id" name="student_id" required class="w-full rounded-lg border border-slate-300 p-2.5"><option value="">Select student</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->admission_number }} · {{ $student->first_name }} {{ $student->last_name }}</option>@endforeach</select></div>
<div><label for="user_id" class="mb-1 block text-sm font-semibold">Login account (Student role)</label><select id="user_id" name="user_id" required class="w-full rounded-lg border border-slate-300 p-2.5"><option value="">Select login</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }} · {{ $user->email }}</option>@endforeach</select></div><div><button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Link account</button></div></form>
<section class="schoolapp-panel rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><h2 class="mb-3 font-semibold">Linked accounts</h2>@forelse($links as $link)<p class="border-b border-slate-100 py-3 text-sm">{{ $link->student->first_name }} {{ $link->student->last_name }} — {{ $link->user->email }}</p>@empty<p class="text-sm text-slate-500">No linked accounts yet.</p>@endforelse</section>
@endsection
