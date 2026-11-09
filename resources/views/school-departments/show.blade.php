@extends('layouts.app')
@section('title', 'Department Dashboard')
@section('heading', 'Department Dashboard')
@section('content')
<a href="{{ route('school-departments.index', $school) }}" class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-indigo-700"><x-ui.icon name="arrow-left"/> Back to departments</a>
<x-ui.page-heading :title="$department->name" :description="$school->name.' · '.($department->description ?: 'Department overview')">
    <x-slot:actions><x-ui.badge tone="info">{{ str_replace('_', ' ', ucwords($role, '_')) }}</x-ui.badge></x-slot:actions>
</x-ui.page-heading>
@if($errors->any())<x-ui.alert type="error" class="mb-5">{{ $errors->first() }}</x-ui.alert>@endif
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    <x-ui.card title="Department"><p class="text-2xl font-bold">{{ $department->code }}</p><p class="mt-2 text-sm text-slate-500">School-owned department</p></x-ui.card>
    <x-ui.card title="Active access role"><p class="text-2xl font-bold capitalize">{{ str_replace('_', ' ', $role) }}</p><p class="mt-2 text-sm text-slate-500">Access determined on the server</p></x-ui.card>
    <x-ui.card title="Assigned users"><p class="text-2xl font-bold">{{ $department->memberships_count }}</p><p class="mt-2 text-sm text-slate-500">Department role assignments</p></x-ui.card>
</div>
<x-ui.card class="mt-6" title="Role access list" description="Department permissions are independent of your global application role.">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm" aria-label="Department role permission matrix">
            <thead><tr><th>Role</th><th>View department</th><th>Manage roles</th><th>School-wide access</th></tr></thead>
            <tbody>
            <tr><td>School administrator</td><td>Yes</td><td>Yes</td><td>Own school</td></tr>
            <tr><td>Department head</td><td>Assigned only</td><td>No</td><td>No</td></tr>
            <tr><td>Department staff</td><td>Assigned only</td><td>No</td><td>No</td></tr>
            <tr><td>Department viewer</td><td>Assigned only</td><td>No</td><td>No</td></tr>
            <tr><td>School auditor / unassigned user</td><td>No</td><td>No</td><td>No</td></tr>
            </tbody>
        </table>
    </div>
    <p class="mt-4 text-xs text-slate-500">Department head, staff and viewer roles are currently read-only. Future department actions must have their own server-side policies.</p>
</x-ui.card>
@if($canManage)
<div class="mt-6 grid gap-5 lg:grid-cols-2">
    <x-ui.card title="Assign department role" description="Enter an existing SchoolApp account email. A school membership is provisioned for this department when needed.">
        <form method="POST" action="{{ route('school-departments.assign', [$school, $department]) }}" class="mt-4 grid gap-4">
            @csrf
            <div><label for="department-email" class="mb-1 block text-sm">User email address</label><input id="department-email" type="email" name="email" value="{{ old('email') }}" required class="w-full"></div>
            <div><label for="department-role" class="mb-1 block text-sm">Department role</label><select id="department-role" name="role" required class="w-full">
                @foreach(['department_head' => 'Department head', 'department_staff' => 'Department staff', 'department_viewer' => 'Department viewer'] as $value => $label)
                <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                @endforeach
            </select></div>
            <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white">Save role access</button>
        </form>
    </x-ui.card>
    <x-ui.card title="Assigned members" description="Only this school's department role assignments are shown.">
        <div class="space-y-3">
            @forelse($members as $membership)
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
                    <div class="min-w-0"><p class="text-sm font-semibold">{{ $membership->user->name }}</p><p class="break-all text-xs text-slate-500">{{ $membership->user->email }}</p><p class="mt-1 text-xs capitalize text-indigo-700">{{ str_replace('_', ' ', $membership->role) }}</p></div>
                    <form method="POST" action="{{ route('school-departments.revoke', [$school, $department, $membership]) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="rounded-xl border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50">Revoke</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-slate-500">No assigned department members.</p>
            @endforelse
        </div>
    </x-ui.card>
</div>
@endif
@endsection
