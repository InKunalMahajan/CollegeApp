@extends('layouts.app')
@section('title','School Administration')
@section('heading','School Administration')
@section('content')
<section class="mb-6 rounded-xl border border-slate-200 bg-white p-6">
<h2 class="text-xl font-semibold">{{ $school->name }}</h2>
<p class="mt-1 text-sm text-slate-600">Manage read-only auditor access and verify legacy student ownership. School administrator memberships require trusted provisioning outside this page.</p>
<a href="{{ route('school-student-accounts.index',$school) }}" class="mt-3 mr-3 inline-block text-sm font-semibold text-indigo-700">Manage student login accounts</a>
<a href="{{ route('my-schools.index') }}" class="mt-3 inline-block text-sm font-semibold text-indigo-700">Back to My Schools</a>
</section>
@if($errors->any())<div role="alert" class="mb-5 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if(session('success'))<p role="status" class="mb-5 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm">{{ session('success') }}</p>@endif
<div class="grid gap-6 lg:grid-cols-2">
<section class="rounded-xl border border-slate-200 bg-white p-6">
<h3 class="mb-3 font-semibold">Add an auditor</h3>
<p class="mb-4 text-sm text-slate-600">An auditor can view the school overview and security summary. This action cannot grant school administrator access.</p>
<form method="POST" action="{{ route('school-admin.auditors.store',$school) }}" class="flex flex-wrap gap-3">@csrf
<label class="flex-1 text-sm" for="auditor-email">Existing user's email
<input id="auditor-email" name="email" type="email" autocomplete="off" maxlength="255" required class="mt-1 w-full rounded-lg border border-slate-300 p-2.5">
</label>
<button class="self-end rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Grant auditor access</button>
</form>
<h4 class="mt-6 mb-3 text-sm font-semibold">School memberships</h4>
<div class="space-y-3">
@forelse($memberships as $membership)
<div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3 text-sm">
<div><strong>{{ $membership->user->name }}</strong><p class="text-slate-500">{{ $membership->user->email }} · {{ $membership->role }}</p></div>
@if($membership->role==='auditor')
<form method="POST" action="{{ route('school-admin.auditors.destroy',[$school,$membership]) }}">@csrf @method('DELETE')
<button class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-700">Revoke</button>
</form>
@endif
</div>
@empty<p class="text-sm text-slate-500">No members.</p>@endforelse
</div>
</section>
<section class="rounded-xl border border-slate-200 bg-white p-6">
<h3 class="mb-3 font-semibold">Verify legacy student ownership</h3>
<p class="mb-4 text-sm text-slate-600">Only previously unassigned students whose existing class enrollments all belong to this school can be claimed. Admissions without enrollments require a separate manual review.</p>
<form method="POST" action="{{ route('school-admin.students.claim',$school) }}" class="space-y-3">@csrf
<label for="legacy-admission" class="block text-sm">Existing admission number</label>
<input id="legacy-admission" name="admission_number" maxlength="40" required class="w-full rounded-lg border border-slate-300 p-2.5">
<button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Verify and assign ownership</button>
</form>
<p class="mt-5 text-xs text-slate-500">Successful membership and ownership changes appear in the school's security audit history.</p>
</section>
</div>
@endsection
