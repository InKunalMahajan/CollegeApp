@extends('layouts.app')
@section('title', 'TEACH Campus Administration')
@section('heading', 'Campus Administration')
@section('content')
<x-ui.page-heading :title="$school->name.' — Campuses'" description="Organization administrators can create campuses and grant access to one campus without granting school-wide permissions."/>
@if(session('success'))<x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>@endif
@if($errors->any())<x-ui.alert type="error" class="mb-4">{{ $errors->first() }}</x-ui.alert>@endif
<x-ui.card title="Create campus" description="For example: TEACH Mumbai (Mumbai), TEACH Pune (Pune), and TEACH Delhi (Delhi).">
<form method="POST" action="{{ route('school-campuses.store', $school) }}" class="grid gap-4 sm:grid-cols-2">
    @csrf
    <div><label class="block text-sm" for="campus-name">Campus name</label><input id="campus-name" name="name" required maxlength="255" value="{{ old('name') }}" class="w-full" placeholder="TEACH Mumbai"></div>
    <div><label class="block text-sm" for="campus-code">Campus code</label><input id="campus-code" name="code" required maxlength="30" pattern="[A-Za-z0-9_-]+" value="{{ old('code') }}" class="w-full" placeholder="MUMBAI"></div>
    <div><label class="block text-sm" for="campus-city">City</label><input id="campus-city" name="city" maxlength="255" value="{{ old('city') }}" class="w-full" placeholder="Mumbai"></div>
    <div class="flex items-end"><button class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Create campus</button></div>
</form>
</x-ui.card>
<div class="mt-6 grid gap-5 xl:grid-cols-2">
@forelse($campuses as $campus)
<x-ui.card>
    <div class="flex items-start justify-between gap-2">
        <div><h2 class="text-lg font-semibold">{{ $campus->name }}</h2><p class="text-xs text-slate-500">{{ $campus->code }} · {{ $campus->city ?: 'City not specified' }}</p></div>
        <a href="{{ route('campus-workspace.index', [$school, $campus]) }}" class="rounded-lg border border-indigo-200 px-3 py-2 text-sm font-semibold text-indigo-700">Open campus</a>
    </div>
    <details class="mt-4 rounded-xl border border-slate-200 p-3">
        <summary class="cursor-pointer text-sm font-semibold text-indigo-700">Edit campus or delete empty campus</summary>
        <form method="POST" action="{{ route('school-campuses.update', [$school, $campus]) }}" class="mt-4 grid gap-3">
            @csrf @method('PUT')
            <label class="text-sm">Campus name<input name="name" class="mt-1 w-full" required maxlength="255" value="{{ $campus->name }}"></label>
            <label class="text-sm">Code<input name="code" class="mt-1 w-full" required maxlength="30" pattern="[A-Za-z0-9_-]+" value="{{ $campus->code }}"></label>
            <label class="text-sm">City<input name="city" class="mt-1 w-full" maxlength="255" value="{{ $campus->city }}"></label>
            <button class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Save campus</button>
        </form>
        <form method="POST" action="{{ route('school-campuses.destroy', [$school, $campus]) }}" class="mt-4" onsubmit="return confirm('Delete this EMPTY campus permanently? Deletion is blocked if it has any linked records.');">
            @csrf @method('DELETE')
            <button class="rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-700">Delete empty campus</button>
        </form>
    </details>
    <h3 class="mt-5 text-sm font-semibold">Campus administrators</h3>
    <div class="mt-2 space-y-2">
        @forelse($memberships->where('campus_id', $campus->id) as $membership)
            <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-slate-50 p-3 text-sm">
                <div><strong>{{ $membership->user->name }}</strong><p class="text-xs text-slate-500">{{ $membership->user->email }}</p></div>
                <form method="POST" action="{{ route('school-campuses.revoke', [$school, $campus, $membership]) }}" onsubmit="return confirm('Revoke this campus administrator?');">
                    @csrf @method('DELETE')
                    <button class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700">Revoke</button>
                </form>
            </div>
        @empty
            <p class="text-sm text-slate-500">No campus administrator assigned.</p>
        @endforelse
    </div>
    <form method="POST" action="{{ route('school-campuses.grant', [$school, $campus]) }}" class="mt-4 flex flex-wrap items-end gap-2">
        @csrf
        <div class="min-w-0 flex-1"><label for="campus-email-{{ $campus->id }}" class="block text-sm">Existing account email</label><input id="campus-email-{{ $campus->id }}" type="email" name="email" required class="w-full"></div>
        <button class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Assign campus admin</button>
    </form>
    <h3 class="mt-5 text-sm font-semibold">Assign existing unassigned employees</h3>
    <div class="max-h-52 space-y-2 overflow-y-auto">
        @forelse($employees as $employee)
            <form method="POST" action="{{ route('school-campuses.assign-employee', [$school, $campus, $employee]) }}" class="flex items-center justify-between gap-2 text-sm">
                @csrf <span>{{ $employee->display_name }} ({{ $employee->employee_number }})</span>
                <button class="rounded-lg border border-slate-200 px-3 py-1.5 font-semibold">Assign</button>
            </form>
        @empty
            <p class="text-sm text-slate-500">No unassigned employees.</p>
        @endforelse
    </div>
    <h3 class="mt-5 text-sm font-semibold">Assign existing unassigned students</h3>
    <div class="max-h-52 space-y-2 overflow-y-auto">
        @forelse($students as $student)
            <form method="POST" action="{{ route('school-campuses.assign-student', [$school, $campus, $student]) }}" class="flex items-center justify-between gap-2 text-sm">
                @csrf <span>{{ $student->first_name }} {{ $student->last_name }} ({{ $student->admission_number }})</span>
                <button class="rounded-lg border border-slate-200 px-3 py-1.5 font-semibold">Assign</button>
            </form>
        @empty
            <p class="text-sm text-slate-500">No unassigned students.</p>
        @endforelse
    </div>
    <p class="mt-4 text-xs text-slate-500">Assignment lists show the first 100 unassigned records. Validate campus ownership before assigning; school-wide data is never auto-mapped.</p>
</x-ui.card>
@empty
<div class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-500">No campuses have been created under this organization.</div>
@endforelse
</div>
@endsection
