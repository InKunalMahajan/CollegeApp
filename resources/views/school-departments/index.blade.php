@extends('layouts.app')
@section('title', 'Departments')
@section('heading', 'Department Dashboards')
@section('content')
<x-ui.page-heading :title="$school->name.' — Departments'" description="Separate department workspaces with school-specific role access."/>
<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @forelse($departments as $department)
        <x-ui.card>
            <div class="flex items-center justify-between gap-3">
                <div class="flex size-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700"><x-ui.icon name="layout-grid" size="size-5"/></div>
                <x-ui.badge>{{ $department->code }}</x-ui.badge>
            </div>
            <h2 class="mt-4 text-lg font-semibold">{{ $department->name }}</h2>
            <p class="mt-2 text-sm text-slate-500">{{ $department->description ?: 'Dedicated department dashboard and access list.' }}</p>
            <p class="mt-3 text-xs font-medium text-slate-500">{{ $department->memberships_count }} assigned members</p>
            <a href="{{ route('school-departments.show', [$school, $department]) }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white"><x-ui.icon name="layout-dashboard"/> Open dashboard</a>
        </x-ui.card>
    @empty
        <div class="schoolapp-panel rounded-xl border border-slate-200 bg-white p-8 text-sm text-slate-600">No departments available to your account.</div>
    @endforelse
</div>
@if($canManage)
<x-ui.card title="Create school department" description="Only school administrators can create departments. Existing global legacy departments are not used.">
    @if($errors->any())<x-ui.alert type="error">{{ $errors->first() }}</x-ui.alert>@endif
    <form method="POST" action="{{ route('school-departments.store', $school) }}" class="mt-4 grid gap-4 md:grid-cols-2">
        @csrf
        <div><label for="department-name" class="mb-1 block text-sm">Department name</label><input id="department-name" name="name" required maxlength="120" value="{{ old('name') }}" class="w-full"></div>
        <div><label for="department-code" class="mb-1 block text-sm">Department code</label><input id="department-code" name="code" required maxlength="32" pattern="[A-Za-z0-9_-]+" value="{{ old('code') }}" placeholder="SKILLS" class="w-full"></div>
        <div class="md:col-span-2"><label for="department-description" class="mb-1 block text-sm">Description (optional)</label><textarea id="department-description" name="description" maxlength="1000" rows="2" class="w-full">{{ old('description') }}</textarea></div>
        <div class="md:col-span-2"><button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white">Create department</button></div>
    </form>
</x-ui.card>
@endif
@endsection
