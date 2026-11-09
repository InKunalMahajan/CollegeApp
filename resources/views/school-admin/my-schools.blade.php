@extends('layouts.app')
@section('title','My Schools')
@section('heading','My Schools')
@section('content')
<p class="mb-6 text-sm text-slate-600">Choose a school that your account is explicitly authorized to access. Changing schools does not change your permissions.</p>
<div class="grid gap-4 md:grid-cols-2">
@forelse($memberships as $membership)
<x-ui.card>
<h2 class="text-lg font-semibold">{{ $membership->school->name }}</h2>
<p class="mt-1 text-xs text-slate-500">Role: <x-ui.badge tone="info">{{ $membership->role === 'school_admin' ? 'Organization Administrator' : str_replace('_',' ',ucwords($membership->role,'_')) }}</x-ui.badge></p>
<div class="mt-4 flex flex-wrap gap-3">
@if($membership->role !== 'department_member')
<a href="{{ route('school-dashboard.index',$membership->school) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white"><x-ui.icon name="layout-dashboard"/> Dashboard</a>
<a href="{{ route('school-portal.index',$membership->school) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold"><x-ui.icon name="eye"/> Overview</a>
@if($membership->role !== 'organization_viewer')<a href="{{ route('school-security.report',$membership->school) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold"><x-ui.icon name="chart-column"/> Reports</a>@endif
@endif
@if(in_array($membership->role, ['school_admin','department_member']))
<a href="{{ route('school-departments.index', $membership->school) }}" class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700"><x-ui.icon name="layout-grid"/> Departments</a>
@endif
@if($membership->role !== 'organization_viewer')<a href="{{ route('school-employees.index', $membership->school) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold"><x-ui.icon name="users"/> Employee tree</a>@endif
@if($membership->role==='school_admin')
<a href="{{ route('organization-users.index', $membership->school) }}" class="rounded-lg border border-indigo-200 px-3 py-2 text-sm font-semibold text-indigo-700">Organization users</a>
<a href="{{ route('school-campuses.index', $membership->school) }}" class="rounded-lg border border-indigo-200 px-3 py-2 text-sm font-semibold text-indigo-700">Manage campuses</a>
<a href="{{ route('school-admin.index',$membership->school) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white"><x-ui.icon name="settings"/> Manage school</a>
@endif
</div></x-ui.card>
@empty
@if($campusMemberships->isEmpty())
<p class="schoolapp-panel rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-600">Your account has no school or campus membership yet. Ask a trusted administrator to provision access.</p>
@endif
@endforelse
</div>
@if($campusMemberships->isNotEmpty())
<h2 class="mt-8 text-lg font-semibold">My campuses</h2>
<div class="mt-4 grid gap-4 md:grid-cols-2">
@foreach($campusMemberships as $assignment)
<x-ui.card>
<h3 class="text-lg font-semibold">{{ $assignment->campus->name }}</h3>
<p class="mt-1 text-sm text-slate-600">Organization: {{ $assignment->campus->school->name }}</p>
<p class="mt-1 text-xs text-slate-500">Role: {{ str_replace('_',' ',ucwords($assignment->role,'_')) }} · {{ $assignment->campus->city ?: 'Campus' }} only</p>
<a href="{{ route('campus-workspace.index', [$assignment->campus->school, $assignment->campus]) }}" class="mt-4 inline-flex rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Open campus workspace</a>
@if($assignment->role === 'campus_admin')<a href="{{ route('campus-users.index', [$assignment->campus->school, $assignment->campus]) }}" class="mt-4 ml-2 inline-flex rounded-xl border border-indigo-200 px-4 py-2.5 text-sm font-semibold text-indigo-700">Manage campus users</a>@endif
</x-ui.card>
@endforeach
</div>
@endif
@endsection
