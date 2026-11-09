@extends('layouts.app')
@section('title','Organization · Users')
@section('heading','Organization User Management')
@section('content')
<x-ui.page-heading :title="$school->name.' · User Access'" description="Manage organization viewers and auditors. Website Owner controls organization administrator appointments."/>
@if(session('success'))<x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>@endif
@if($errors->any())<x-ui.alert type="error" class="mb-4">{{ $errors->first() }}</x-ui.alert>@endif
<x-ui.card title="Add an existing account to this organization">
<form method="POST" action="{{ route('organization-users.store',$school) }}" class="grid gap-3 sm:grid-cols-3">@csrf
<label class="sm:col-span-2">Existing account email<input type="email" name="email" required maxlength="255" class="w-full"></label>
<label>Organization role<select name="role" class="w-full"><option value="organization_viewer">Organization viewer</option><option value="auditor">Auditor</option></select></label>
<div class="sm:col-span-3"><button class="rounded-xl bg-indigo-600 px-4 py-2 text-white">Add organization user</button></div>
</form></x-ui.card>
<x-ui.card class="mt-5" title="Organization memberships">
@forelse($memberships as $membership)
<div class="border-b py-3">
<p class="text-sm font-semibold">{{ $membership->user->name }} · {{ $membership->user->email }} · {{ str_replace('_',' ',ucwords($membership->role,'_')) }}</p>
@if(in_array($membership->role,['auditor','organization_viewer']))
<div class="mt-2 flex flex-wrap gap-2">
<form method="POST" action="{{ route('organization-users.update',[$school,$membership]) }}" class="flex gap-2">@csrf @method('PUT')
<select name="role" class="rounded-lg border px-2 py-1 text-sm"><option value="organization_viewer" @selected($membership->role==='organization_viewer')>Viewer</option><option value="auditor" @selected($membership->role==='auditor')>Auditor</option></select><button class="rounded-lg border px-3 py-1 text-sm">Update</button>
</form>
<form method="POST" action="{{ route('organization-users.destroy',[$school,$membership]) }}" onsubmit="return confirm('Revoke this organization membership?');">@csrf @method('DELETE')<button class="rounded-lg border border-rose-200 px-3 py-1 text-sm text-rose-700">Revoke</button></form>
</div>
@else<p class="mt-2 text-xs text-slate-500">Protected role. Website Owner controls organization administrator access.</p>@endif
</div>
@empty<p class="text-sm text-slate-500">No organization users.</p>@endforelse
{{ $memberships->links() }}
</x-ui.card>
@endsection