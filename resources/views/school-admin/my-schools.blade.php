@extends('layouts.app')
@section('title','My Schools')
@section('heading','My Schools')
@section('content')
<p class="mb-6 text-sm text-slate-600">Choose a school that your account is explicitly authorized to access. Changing schools does not change your permissions.</p>
<div class="grid gap-4 md:grid-cols-2">
@forelse($memberships as $membership)
<x-ui.card>
<h2 class="text-lg font-semibold">{{ $membership->school->name }}</h2>
<p class="mt-1 text-xs text-slate-500">Role: <x-ui.badge tone="info">{{ str_replace('_',' ',ucwords($membership->role,'_')) }}</x-ui.badge></p>
<div class="mt-4 flex flex-wrap gap-3">
<a href="{{ route('school-dashboard.index',$membership->school) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white"><x-ui.icon name="layout-dashboard"/> Dashboard</a>
<a href="{{ route('school-portal.index',$membership->school) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold"><x-ui.icon name="eye"/> Overview</a>
<a href="{{ route('school-security.report',$membership->school) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold"><x-ui.icon name="chart-column"/> Reports</a>
@if($membership->role==='school_admin')
<a href="{{ route('school-admin.index',$membership->school) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white"><x-ui.icon name="settings"/> Manage school</a>
@endif
</div></x-ui.card>
@empty
<p class="schoolapp-panel rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-600">Your account has no school membership yet. Ask a trusted system administrator to provision access.</p>
@endforelse
</div>
@endsection
