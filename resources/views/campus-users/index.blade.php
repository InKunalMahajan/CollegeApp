@extends('layouts.app')
@section('title','Campus · Users')
@section('heading','Campus User Management')
@section('content')
<x-ui.page-heading :title="$campus->name.' · User Access'" :description="$school->name.' · This campus only. Organization admin controls campus administrator appointments.'"/>
@if(session('success'))<x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>@endif
@if($errors->any())<x-ui.alert type="error" class="mb-4">{{ $errors->first() }}</x-ui.alert>@endif
<p class="mb-4"><a href="{{ route('campus-workspace.index',[$school,$campus]) }}">← Campus workspace</a></p>
<x-ui.card title="Add an existing account to this campus">
<form method="POST" action="{{ route('campus-users.store',[$school,$campus]) }}" class="grid gap-3 sm:grid-cols-3">@csrf
<label class="sm:col-span-2">Existing account email<input type="email" name="email" required maxlength="255" class="w-full"></label>
<label>Campus role<select name="role" class="w-full"><option value="campus_viewer">Campus viewer</option><option value="campus_member">Campus member (read-only)</option></select></label>
<div class="sm:col-span-3"><button class="rounded-xl bg-indigo-600 px-4 py-2 text-white">Add campus user</button></div>
</form></x-ui.card>
<x-ui.card class="mt-5" title="Campus memberships">
@forelse($memberships as $membership)
<div class="border-b py-3">
<p class="text-sm font-semibold">{{ $membership->user->name }} · {{ $membership->user->email }} · {{ str_replace('_',' ',ucwords($membership->role,'_')) }}</p>
@if(in_array($membership->role,['campus_member','campus_viewer']))
<div class="mt-2 flex flex-wrap gap-2">
<form method="POST" action="{{ route('campus-users.update',[$school,$campus,$membership]) }}" class="flex gap-2">@csrf @method('PUT')
<select name="role" class="rounded-lg border px-2 py-1 text-sm"><option value="campus_viewer" @selected($membership->role==='campus_viewer')>Viewer</option><option value="campus_member" @selected($membership->role==='campus_member')>Member</option></select><button class="rounded-lg border px-3 py-1 text-sm">Update</button>
</form>
<form method="POST" action="{{ route('campus-users.destroy',[$school,$campus,$membership]) }}" onsubmit="return confirm('Revoke this campus membership?');">@csrf @method('DELETE')<button class="rounded-lg border border-rose-200 px-3 py-1 text-sm text-rose-700">Revoke</button></form>
</div>
@else<p class="mt-2 text-xs text-slate-500">Protected administrator role. Organization admin controls this assignment.</p>@endif
</div>
@empty<p class="text-sm text-slate-500">No campus users.</p>@endforelse
{{ $memberships->links() }}
</x-ui.card>
@endsection