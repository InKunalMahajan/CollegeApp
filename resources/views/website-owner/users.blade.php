@extends('layouts.app')
@section('title','Website Owner · Users')
@section('heading','Website Owner')
@section('content')
<x-ui.page-heading title="Website Owner · User Management" description="Platform accounts and organization administrator provisioning. Only explicitly provisioned owners can access this page."/>
@if(session('success'))<x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>@endif
@if($errors->any())<x-ui.alert type="error" class="mb-4">{{ $errors->first() }}</x-ui.alert>@endif
<div class="grid gap-5 xl:grid-cols-2">
<x-ui.card title="Create platform account" description="Choose a non-privileged account type. Organization and campus administrator roles are assigned separately.">
<form method="POST" action="{{ route('website-owner.users.store') }}" class="grid gap-3">@csrf
<label>Name<input name="name" required maxlength="255" value="{{ old('name') }}" class="w-full"></label>
<label>Email<input type="email" name="email" required maxlength="255" value="{{ old('email') }}" class="w-full"></label>
<label>Account type<select name="role" class="w-full"><option value="staff">Staff</option><option value="teacher">Teacher</option><option value="student">Student</option></select></label>
<label>Password (12 characters minimum)<input type="password" name="password" minlength="12" required autocomplete="new-password" class="w-full"></label>
<label>Confirm password<input type="password" name="password_confirmation" minlength="12" required autocomplete="new-password" class="w-full"></label>
<button class="rounded-xl bg-indigo-600 px-4 py-2 text-white">Create account</button>
</form></x-ui.card>
<x-ui.card title="Appoint organization administrator" description="Only Website Owner can grant or revoke organization administrator access.">
<form method="POST" action="{{ route('website-owner.admins.grant') }}" class="grid gap-3">@csrf
<label>Organization<select name="school_id" required class="w-full"><option value="">Select organization</option>@foreach($schools as $school)<option value="{{ $school->id }}">{{ $school->name }}</option>@endforeach</select></label>
<label>Existing active user's email<input type="email" required name="email" class="w-full"></label>
<button class="rounded-xl bg-indigo-600 px-4 py-2 text-white">Grant organization admin</button>
</form>
<h3 class="mt-5 text-sm font-semibold">Current organization administrators</h3>
@forelse($admins as $membership)
<div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-b py-2 text-sm">
<span>{{ $membership->school->name }} · {{ $membership->user->name }} ({{ $membership->user->email }})</span>
<form method="POST" action="{{ route('website-owner.admins.revoke',$membership) }}" onsubmit="return confirm('Revoke organization admin access?');">@csrf @method('DELETE')
<button class="rounded-lg border border-rose-200 px-3 py-2 text-rose-700">Revoke role</button></form></div>
@empty<p class="mt-2 text-sm text-slate-500">No organization administrators.</p>@endforelse
</x-ui.card>
</div>
<x-ui.card class="mt-5" title="Platform accounts" description="Disabling an account blocks login while retaining school, campus and audit history. Website Owner accounts are protected from editing and disabling.">
<form method="GET" class="mb-4 flex gap-2"><input name="search" type="search" value="{{ request('search') }}" placeholder="Search accounts" class="min-w-0 flex-1"><button class="rounded-lg border px-4 py-2">Search</button></form>
@forelse($users as $user)
<details class="border-b py-3">
<summary class="cursor-pointer text-sm font-semibold">{{ $user->name }} · {{ $user->email }} · {{ $user->disabled_at ? 'Disabled' : 'Active' }} @if(in_array($user->id,$ownerIds)) · Website Owner @endif</summary>
@if(!in_array($user->id,$ownerIds))
<form method="POST" action="{{ route('website-owner.users.update',$user) }}" class="mt-3 grid gap-2 sm:grid-cols-2">@csrf @method('PUT')
<label>Name<input name="name" value="{{ $user->name }}" maxlength="255" required class="w-full"></label>
<label>Email<input type="email" name="email" value="{{ $user->email }}" maxlength="255" required class="w-full"></label>
<label>Account type<select name="role" required class="w-full">@if($user->role === 'admin')<option value="" selected disabled>Legacy Admin — select a new type</option>@endif @foreach(['staff'=>'Staff','teacher'=>'Teacher','student'=>'Student'] as $value=>$label)<option value="{{ $value }}" @selected($user->role === $value)>{{ $label }}</option>@endforeach</select></label>
<label>New password (optional)<input type="password" name="password" minlength="12" autocomplete="new-password" class="w-full"></label>
<label>Confirm new password<input type="password" name="password_confirmation" autocomplete="new-password" class="w-full"></label>
<div class="sm:col-span-2"><button class="rounded-lg bg-indigo-600 px-3 py-2 text-white">Save account</button></div>
</form>
@if($user->disabled_at)
<form class="mt-2" method="POST" action="{{ route('website-owner.users.restore',$user) }}">@csrf<button class="rounded-lg border px-3 py-2 text-sm">Enable account</button></form>
@else
<form class="mt-2" method="POST" action="{{ route('website-owner.users.destroy',$user) }}" onsubmit="return confirm('Disable account login? Historical records remain.');">@csrf @method('DELETE')<button class="rounded-lg border border-rose-200 px-3 py-2 text-sm text-rose-700">Disable account</button></form>
@endif
@else<p class="mt-2 text-xs text-slate-500">Website Owner accounts cannot be changed here.</p>@endif
</details>
@empty<p class="text-sm text-slate-500">No matching accounts.</p>@endforelse
{{ $users->links() }}
</x-ui.card>
@endsection