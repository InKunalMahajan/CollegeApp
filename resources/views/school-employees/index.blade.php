@extends('layouts.app')
@section('title', 'Employee Organization Tree')
@section('heading', 'Employee Organization Tree')
@section('content')
<x-ui.page-heading :title="$school->name.' — Employees'" description="View verified reporting lines. Employees without an assigned manager appear as separate top-level nodes.">
    <x-slot:actions>
        <a href="{{ route('school-departments.index', $school) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700"><x-ui.icon name="layout-grid"/> Departments</a>
    </x-slot:actions>
</x-ui.page-heading>
@if($errors->any())
    <x-ui.alert type="error" class="mb-5">{{ $errors->first() }}</x-ui.alert>
@endif

<div class="mb-6 grid gap-4 sm:grid-cols-2">
    <x-ui.card title="Employees visible to you">
        <p class="text-3xl font-bold tabular-nums">{{ $employeeCount }}</p>
        <p class="mt-2 text-xs text-slate-500">School- and department-scoped visibility</p>
    </x-ui.card>
    <x-ui.card title="Reporting structure">
        <p class="text-sm text-slate-600">The chart shows only explicitly assigned managers. Reportee counts in imported CSVs do not prove who reports to whom.</p>
    </x-ui.card>
</div>

<x-ui.card title="Organization tree" description="Expand a manager to view their confirmed direct reportees.">
    @php
        $groups = collect($items)->groupBy(fn ($item) => (string) ($item['manager_id'] ?? 0));
        $roots = $groups->get('0', collect());
    @endphp
    @if($items)
        <div class="space-y-3" aria-label="Employee reporting hierarchy">
            @foreach($roots as $node)
                @include('school-employees.node', ['node' => $node, 'groups' => $groups, 'level' => 0])
            @endforeach
        </div>
    @else
        <p class="schoolapp-empty">No employee records are available to your account.</p>
    @endif
</x-ui.card>

@if($canManage)
<div class="mt-6 grid items-start gap-6 xl:grid-cols-2">
    <x-ui.card title="Add new employee" description="Only school administrators can register employees or assign reporting managers.">
        <form method="POST" action="{{ route('school-employees.store', $school) }}" class="mt-4 grid gap-4">
            @csrf
            <div><label for="employee-number" class="mb-1 block text-sm">Employee number</label><input id="employee-number" name="employee_number" value="{{ old('employee_number') }}" maxlength="40" required class="w-full" placeholder="E1001"></div>
            <div><label for="employee-name" class="mb-1 block text-sm">Display name</label><input id="employee-name" name="display_name" value="{{ old('display_name') }}" maxlength="160" required class="w-full"></div>
            <div><label for="employee-job" class="mb-1 block text-sm">Job title</label><input id="employee-job" name="job_title" value="{{ old('job_title') }}" maxlength="160" required class="w-full"></div>
            <div><label for="employee-department" class="mb-1 block text-sm">Department</label><select id="employee-department" name="school_department_id" class="w-full"><option value="">Not assigned to a SchoolApp department</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(old('school_department_id') == $department->id)>{{ $department->name }}</option>@endforeach</select></div>
            <div><label for="employee-department-label" class="mb-1 block text-sm">Department label (optional)</label><input id="employee-department-label" name="department_label" value="{{ old('department_label') }}" maxlength="180" class="w-full" placeholder="Campus Operations > Academic Foundations"></div>
            <div><label for="employee-location" class="mb-1 block text-sm">Location</label><input id="employee-location" name="location" value="{{ old('location') }}" maxlength="100" class="w-full"></div>
            <div><label for="employee-manager" class="mb-1 block text-sm">Reports to (optional)</label><select id="employee-manager" name="manager_id" class="w-full"><option value="">Manager not confirmed</option>@foreach($managers as $manager)<option value="{{ $manager->id }}" @selected(old('manager_id') == $manager->id)>{{ $manager->display_name }} ({{ $manager->employee_number }})</option>@endforeach</select></div>
            <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white">Add employee</button>
        </form>
    </x-ui.card>
    <x-ui.card title="Import OrgTree CSV" description="Upload your original six-column file. The import will add or update employee details without guessing manager relationships.">
        <form method="POST" action="{{ route('school-employees.import', $school) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf
            <div><label for="employee-csv" class="mb-1 block text-sm">CSV file</label><input id="employee-csv" name="employee_csv" type="file" accept=".csv,text/csv" required class="w-full"></div>
            <p class="text-xs text-slate-500">Required columns: Employee Number, Display Name, Job Title, Department, Location, Reportees Count. Maximum 2 MB or 1,000 rows. Import does not create login accounts.</p>
            <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white">Import employees</button>
        </form>
    </x-ui.card>
</div>
<x-ui.card class="mt-6" title="Edit employees and reporting lines" description="Use the Edit controls to update an employee or connect them to a verified manager.">
    <div class="space-y-2">
        @forelse($managers as $employee)
            <details class="rounded-xl border border-slate-200 px-4 py-3">
                <summary class="cursor-pointer text-sm font-semibold">{{ $employee->display_name }} <span class="font-normal text-slate-500">· {{ $employee->employee_number }} · {{ $employee->job_title }}</span></summary>
                <form method="POST" action="{{ route('school-employees.update', [$school, $employee]) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                    @csrf @method('PUT')
                    <div><label class="block text-sm" for="edit-number-{{ $employee->id }}">Employee number</label><input id="edit-number-{{ $employee->id }}" name="employee_number" required maxlength="40" value="{{ $employee->employee_number }}" class="w-full"></div>
                    <div><label class="block text-sm" for="edit-name-{{ $employee->id }}">Name</label><input id="edit-name-{{ $employee->id }}" name="display_name" required maxlength="160" value="{{ $employee->display_name }}" class="w-full"></div>
                    <div><label class="block text-sm" for="edit-title-{{ $employee->id }}">Job title</label><input id="edit-title-{{ $employee->id }}" name="job_title" required maxlength="160" value="{{ $employee->job_title }}" class="w-full"></div>
                    <div><label class="block text-sm" for="edit-location-{{ $employee->id }}">Location</label><input id="edit-location-{{ $employee->id }}" name="location" maxlength="100" value="{{ $employee->location }}" class="w-full"></div>
                    <div><label class="block text-sm" for="edit-dept-{{ $employee->id }}">Department</label><select id="edit-dept-{{ $employee->id }}" name="school_department_id" class="w-full"><option value="">Unassigned</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected($employee->school_department_id == $department->id)>{{ $department->name }}</option>@endforeach</select></div>
                    <div><label class="block text-sm" for="edit-dept-label-{{ $employee->id }}">Department label</label><input id="edit-dept-label-{{ $employee->id }}" name="department_label" maxlength="180" value="{{ $employee->department_label }}" class="w-full"></div>
                    <div class="sm:col-span-2"><label class="block text-sm" for="edit-manager-{{ $employee->id }}">Reports to</label><select id="edit-manager-{{ $employee->id }}" name="manager_id" class="w-full"><option value="">Manager not confirmed</option>@foreach($managers as $manager)@if($manager->id !== $employee->id)<option value="{{ $manager->id }}" @selected($employee->manager_id == $manager->id)>{{ $manager->display_name }} ({{ $manager->employee_number }})</option>@endif @endforeach</select></div>
                    <div class="sm:col-span-2"><button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white">Save employee</button></div>
                </form>
            </details>
        @empty
            <p class="text-sm text-slate-500">No employee records yet.</p>
        @endforelse
    </div>
</x-ui.card>
@endif
@endsection
