@extends('layouts.app')
@section('title', 'Employee Dashboard')
@section('heading', 'Employee Dashboard')
@section('content')
<x-ui.page-heading :title="$school->name.' — Employee Dashboard'" description="Explore verified employee records, department assignments and reporting relationships. All counts follow your school access.">
    <x-slot:actions>
        @if($canViewDepartments)
            <a href="{{ route('school-departments.index', $school) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"><x-ui.icon name="layout-grid"/> Departments</a>
        @endif
    </x-slot:actions>
</x-ui.page-heading>
@if($errors->any())
    <x-ui.alert type="error" class="mb-5">{{ $errors->first() }}</x-ui.alert>
@endif

<section aria-label="Employee overview" class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-slate-600">Total employees visible</p>
        <p class="mt-2 text-3xl font-bold tabular-nums text-slate-900">{{ $totalVisible }}</p>
        <p class="mt-1 text-xs text-slate-500">Your authorized school scope</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-slate-600">Active employees</p>
        <p class="mt-2 text-3xl font-bold tabular-nums text-slate-900">{{ $statusCounts->get('active', 0) }}</p>
        <p class="mt-1 text-xs text-slate-500">On leave: {{ $statusCounts->get('on_leave', 0) }}</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-slate-600">Assigned to departments</p>
        <p class="mt-2 text-3xl font-bold tabular-nums text-slate-900">{{ $assignedCount }}</p>
        <p class="mt-1 text-xs text-slate-500">Of {{ $totalVisible }} visible employees</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-slate-600">Linked to a manager</p>
        <p class="mt-2 text-3xl font-bold tabular-nums text-slate-900">{{ $managerLinkedCount }}</p>
        <p class="mt-1 text-xs text-slate-500">Verified manager assignment recorded</p>
    </div>
</section>

<x-ui.card title="Find employees" description="Search by name, employee number or job title. Filters and results are limited to your authorized employees.">
    <form method="GET" action="{{ route('school-employees.index', $school) }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="sm:col-span-2 xl:col-span-2">
            <label for="employee-search" class="mb-1 block text-sm font-semibold text-slate-700">Search employees</label>
            <input id="employee-search" name="search" type="search" value="{{ $search }}" maxlength="100" class="w-full" placeholder="Name, employee number or job title">
        </div>
        <div>
            <label for="employee-status-filter" class="mb-1 block text-sm font-semibold text-slate-700">Employment status</label>
            <select id="employee-status-filter" name="employment_status" class="w-full">
                <option value="">All statuses</option>
                @foreach(\App\Models\SchoolEmployee::STATUSES as $status)
                    <option value="{{ $status }}" @selected($statusFilter === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="employee-department-filter" class="mb-1 block text-sm font-semibold text-slate-700">Department</label>
            <select id="employee-department-filter" name="school_department_id" class="w-full">
                <option value="">All accessible departments</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) $departmentFilter === (string) $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="employee-location-filter" class="mb-1 block text-sm font-semibold text-slate-700">Location</label>
            <select id="employee-location-filter" name="location" class="w-full">
                <option value="">All locations</option>
                @foreach($locations as $location)
                    <option value="{{ $location }}" @selected($locationFilter === $location)>{{ $location }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-wrap items-end gap-2 sm:col-span-2 xl:col-span-3">
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"><x-ui.icon name="search"/> Apply filters</button>
            <a href="{{ route('school-employees.index', $school) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear all</a>
        </div>
    </form>
    <p class="mt-4 text-sm text-slate-600" role="status">{{ $filteredCount }} matching employee{{ $filteredCount === 1 ? '' : 's' }} out of {{ $totalVisible }} visible.</p>
</x-ui.card>

<div class="mt-6 grid items-start gap-5 xl:grid-cols-3">
    <div class="xl:col-span-2">
        <x-ui.card title="Reporting structure" description="Direct reportees are derived only from confirmed manager links within the filtered, visible employees.">
            @if(! $treeAvailable)
                <p class="text-sm text-slate-600">The tree is hidden for more than 250 matching employees to keep this page responsive. Use the searchable, paginated directory below.</p>
            @elseif($items)
                @php
                    $groups = collect($items)->groupBy(fn ($item) => (string) ($item['manager_id'] ?? 0));
                    $roots = $groups->get('0', collect());
                @endphp
                <div class="max-h-[34rem] space-y-3 overflow-y-auto pr-1" aria-label="Employee reporting hierarchy">
                    @foreach($roots as $node)
                        @include('school-employees.node', ['node' => $node, 'groups' => $groups, 'level' => 0])
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-500">No reporting relationships match your filters.</p>
            @endif
        </x-ui.card>
    </div>
    <div>
        <x-ui.card title="Department overview" description="Counts reflect only employees visible to your account.">
            <div class="space-y-3">
                @forelse($departments as $department)
                    @php $count = (int) $departmentCounts->get($department->id, 0); @endphp
                    <div class="rounded-lg border border-slate-100 p-3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="truncate text-sm font-semibold text-slate-800">{{ $department->name }}</span>
                            <span class="text-sm font-semibold tabular-nums text-slate-700">{{ $count }}</span>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-indigo-500" style="width: {{ $totalVisible ? round($count / $totalVisible * 100, 1) : 0 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No departments are available to your account.</p>
                @endforelse
                <p class="text-xs text-slate-500">Employees without a SchoolApp department assignment: {{ $totalVisible - $assignedCount }}.</p>
            </div>
        </x-ui.card>
    </div>
</div>

<x-ui.card class="mt-6" title="Employee directory" description="Browse employees and open individual profiles. 20 rows per page.">
    <div class="overflow-x-auto">
        <table class="min-w-[680px] w-full text-left text-sm" aria-label="Employee directory">
            <thead><tr><th scope="col">Employee</th><th scope="col">Job title</th><th scope="col">Department</th><th scope="col">Status</th><th scope="col">Profile</th></tr></thead>
            <tbody>
                @forelse($directory as $person)
                    <tr>
                        <td><span class="font-semibold text-slate-900">{{ $person->display_name }}</span><div class="text-xs text-slate-500">{{ $person->employee_number }}</div></td>
                        <td>{{ $person->job_title }}</td>
                        <td>{{ $person->department_label ?: ($person->department?->name ?? 'Unassigned') }}</td>
                        <td><x-ui.badge>{{ ucwords(str_replace('_', ' ', $person->employment_status)) }}</x-ui.badge></td>
                        <td><a class="font-semibold text-indigo-700 underline-offset-2 hover:underline focus-visible:outline-2 focus-visible:outline-indigo-600" href="{{ route('school-employees.show', [$school, $person]) }}">View profile</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-slate-500">No employees match these filters. Clear the filters to see more results.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($directory->hasPages())
        <div class="mt-5">{{ $directory->links() }}</div>
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
            <div><label for="employee-status" class="mb-1 block text-sm">Employment status</label><select id="employee-status" name="employment_status" class="w-full">@foreach(\App\Models\SchoolEmployee::STATUSES as $status)<option value="{{ $status }}" @selected(old('employment_status', 'active') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>@endforeach</select></div>
            <div><label for="employee-type" class="mb-1 block text-sm">Employment type</label><select id="employee-type" name="employment_type" class="w-full"><option value="">Not specified</option>@foreach(\App\Models\SchoolEmployee::TYPES as $type)<option value="{{ $type }}" @selected(old('employment_type') === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>@endforeach</select></div>
            <div><label for="employee-joined" class="mb-1 block text-sm">Joining date</label><input id="employee-joined" type="date" name="joined_on" max="{{ now()->toDateString() }}" value="{{ old('joined_on') }}" class="w-full"></div>
            <div><label for="employee-work-email" class="mb-1 block text-sm">Work email</label><input id="employee-work-email" type="email" name="work_email" maxlength="255" value="{{ old('work_email') }}" class="w-full"></div>
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
                    <div><label class="block text-sm" for="edit-status-{{ $employee->id }}">Employment status</label><select id="edit-status-{{ $employee->id }}" name="employment_status" class="w-full">@foreach(\App\Models\SchoolEmployee::STATUSES as $status)<option value="{{ $status }}" @selected($employee->employment_status === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>@endforeach</select></div>
                    <div><label class="block text-sm" for="edit-type-{{ $employee->id }}">Employment type</label><select id="edit-type-{{ $employee->id }}" name="employment_type" class="w-full"><option value="">Not specified</option>@foreach(\App\Models\SchoolEmployee::TYPES as $type)<option value="{{ $type }}" @selected($employee->employment_type === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>@endforeach</select></div>
                    <div><label class="block text-sm" for="edit-joined-{{ $employee->id }}">Joining date</label><input id="edit-joined-{{ $employee->id }}" type="date" name="joined_on" max="{{ now()->toDateString() }}" value="{{ $employee->joined_on?->format('Y-m-d') }}" class="w-full"></div>
                    <div><label class="block text-sm" for="edit-email-{{ $employee->id }}">Work email</label><input id="edit-email-{{ $employee->id }}" type="email" name="work_email" maxlength="255" value="{{ $employee->work_email }}" class="w-full"></div>
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
