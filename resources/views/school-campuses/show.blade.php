@extends('layouts.app')
@section('title', 'Campus Workspace')
@section('heading', 'Campus Workspace')
@section('content')
<x-ui.page-heading :title="$campus->name" :description="$school->name.' · '.($campus->city ?: 'Campus'). ' · Campus-only employee and student management'"/>
@if(session('success'))<x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>@endif
@if($errors->any())<x-ui.alert type="error" class="mb-4">{{ $errors->first() }}</x-ui.alert>@endif
<p class="mb-5"><a class="text-sm font-semibold text-indigo-700 hover:underline" href="{{ route('campus.operations.index', [$school, $campus]) }}">View campus operations overview →</a></p>
@if(app(\App\Services\CampusAccess::class)->can(auth()->user(),$school,$campus,'campus.users.manage'))
<p class="mb-5"><a href="{{ route('campus-users.index',[$school,$campus]) }}" class="text-sm font-semibold text-indigo-700">Manage campus user access →</a></p>
@endif
<div class="mb-6 grid gap-4 sm:grid-cols-2">
    <x-ui.card title="Employees in campus"><p class="text-3xl font-semibold tabular-nums">{{ $employees->total() }}</p></x-ui.card>
    <x-ui.card title="Students in campus"><p class="text-3xl font-semibold tabular-nums">{{ $students->total() }}</p></x-ui.card>
</div>
<div class="grid gap-5 xl:grid-cols-2">
    <x-ui.card title="Create campus employee" description="Creates only in this campus. Manager and department assignments require organization-level verification.">
        <form method="POST" action="{{ route('campus.employees.store', [$school, $campus]) }}" class="grid gap-3">
            @csrf
            <label class="text-sm">Employee number<input name="employee_number" required maxlength="40" class="mt-1 w-full"></label>
            <label class="text-sm">Display name<input name="display_name" required maxlength="160" class="mt-1 w-full"></label>
            <label class="text-sm">Job title<input name="job_title" required maxlength="160" class="mt-1 w-full"></label>
            <label class="text-sm">Location<input name="location" maxlength="100" class="mt-1 w-full"></label>
            <label class="text-sm">Work email<input type="email" name="work_email" maxlength="255" class="mt-1 w-full"></label>
            <label class="text-sm">Employment status<select name="employment_status" class="mt-1 w-full">@foreach(\App\Models\SchoolEmployee::STATUSES as $status)<option value="{{ $status }}">{{ ucwords(str_replace('_', ' ', $status)) }}</option>@endforeach</select></label>
            <button class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Create employee</button>
        </form>
    </x-ui.card>
    <x-ui.card title="Admit student" description="Admission numbers remain unique across SchoolApp. The campus cannot be changed through this form.">
        <form method="POST" action="{{ route('campus.students.store', [$school, $campus]) }}" class="grid gap-3">
            @csrf
            <label class="text-sm">Admission number<input name="admission_number" required maxlength="40" class="mt-1 w-full"></label>
            <label class="text-sm">First name<input name="first_name" required maxlength="255" class="mt-1 w-full"></label>
            <label class="text-sm">Last name<input name="last_name" required maxlength="255" class="mt-1 w-full"></label>
            <label class="text-sm">Email<input name="email" type="email" maxlength="255" class="mt-1 w-full"></label>
            <label class="text-sm">Admission date<input type="date" name="admission_date" required class="mt-1 w-full"></label>
            <label class="text-sm">Status<select name="status" class="mt-1 w-full"><option value="active">Active</option><option value="inactive">Inactive</option><option value="graduated">Graduated</option></select></label>
            <button class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Admit student</button>
        </form>
    </x-ui.card>
</div>
<x-ui.card class="mt-6" title="Campus employees" description="Employees from other campuses are excluded from these records and operations.">
    <div class="space-y-3">
        @forelse($employees as $employee)
        <details class="rounded-xl border border-slate-200 p-3">
            <summary class="cursor-pointer text-sm font-semibold">{{ $employee->display_name }} · {{ $employee->employee_number }}</summary>
            <form method="POST" action="{{ route('campus.employees.update', [$school, $campus, $employee]) }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                @csrf @method('PUT')
                <label class="text-sm">Number<input class="w-full" name="employee_number" value="{{ $employee->employee_number }}" required></label>
                <label class="text-sm">Name<input class="w-full" name="display_name" value="{{ $employee->display_name }}" required></label>
                <label class="text-sm">Job title<input class="w-full" name="job_title" value="{{ $employee->job_title }}" required></label>
                <label class="text-sm">Location<input class="w-full" name="location" value="{{ $employee->location }}"></label>
                <label class="text-sm">Work email<input class="w-full" type="email" name="work_email" value="{{ $employee->work_email }}"></label>
                <label class="text-sm">Status<select class="w-full" name="employment_status">@foreach(\App\Models\SchoolEmployee::STATUSES as $status)<option value="{{ $status }}" @selected($employee->employment_status === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>@endforeach</select></label>
                <div class="sm:col-span-2"><button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Save employee</button></div>
            </form>
            <form method="POST" action="{{ route('campus.employees.archive', [$school, $campus, $employee]) }}" class="mt-3" onsubmit="return confirm('Archive this employee?');">
                @csrf @method('DELETE')
                <button class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-700">Archive employee</button>
            </form>
        </details>
        @empty <p class="text-sm text-slate-500">No employees assigned to this campus.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $employees->links() }}</div>
</x-ui.card>
<x-ui.card class="mt-6" title="Archived campus employees" description="Reversible archive, retaining employment and audit history.">
    <div class="space-y-2">
        @forelse($archived as $employee)
            <form method="POST" action="{{ route('campus.employees.restore', [$school, $campus, $employee->id]) }}" class="flex flex-wrap items-center justify-between gap-2">
                @csrf <span class="text-sm">{{ $employee->display_name }} ({{ $employee->employee_number }})</span>
                <button class="rounded-lg border border-indigo-200 px-3 py-2 text-sm font-semibold text-indigo-700">Restore</button>
            </form>
        @empty <p class="text-sm text-slate-500">No archived employees.</p>
        @endforelse
    </div>
</x-ui.card>
<x-ui.card class="mt-6" title="Campus students" description="Deactivate students instead of permanently deleting attendance, results or admission history.">
    <div class="space-y-3">
    @forelse($students as $student)
        <details class="rounded-xl border border-slate-200 p-3">
            <summary class="cursor-pointer text-sm font-semibold">{{ $student->first_name }} {{ $student->last_name }} · {{ $student->admission_number }}</summary>
            <form method="POST" action="{{ route('campus.students.update', [$school, $campus, $student]) }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                @csrf @method('PUT')
                <label class="text-sm">Admission number<input class="w-full" name="admission_number" required value="{{ $student->admission_number }}"></label>
                <label class="text-sm">First name<input class="w-full" name="first_name" required value="{{ $student->first_name }}"></label>
                <label class="text-sm">Last name<input class="w-full" name="last_name" required value="{{ $student->last_name }}"></label>
                <label class="text-sm">Email<input class="w-full" type="email" name="email" value="{{ $student->email }}"></label>
                <label class="text-sm">Admission date<input class="w-full" type="date" name="admission_date" required value="{{ $student->admission_date?->format('Y-m-d') }}"></label>
                <label class="text-sm">Status<select class="w-full" name="status">@foreach(['active', 'inactive', 'graduated'] as $status)<option value="{{ $status }}" @selected($student->status === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
                <div class="sm:col-span-2"><button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Save student</button></div>
            </form>
            <form method="POST" action="{{ route('campus.students.deactivate', [$school, $campus, $student]) }}" class="mt-3" onsubmit="return confirm('Deactivate student? Their history is retained.');">
                @csrf @method('DELETE')
                <button class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-700">Deactivate student</button>
            </form>
        </details>
    @empty <p class="text-sm text-slate-500">No students assigned to this campus.</p>
    @endforelse
    </div>
    <div class="mt-4">{{ $students->links() }}</div>
</x-ui.card>
@endsection
