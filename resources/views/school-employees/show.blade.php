@extends('layouts.app')
@section('title', 'Employee Profile')
@section('heading', 'Employee Profile')
@section('content')
<a href="{{ route('school-employees.index', $school) }}" class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-indigo-700"><x-ui.icon name="arrow-left"/> Back to employee tree</a>
<x-ui.page-heading :title="$employee->display_name" :description="$employee->job_title.' · '.$employee->employee_number"/>
<div class="grid gap-5 lg:grid-cols-2">
    <x-ui.card title="Employment">
        <dl class="grid grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">Status</dt><dd class="mt-1 font-semibold">{{ str_replace('_', ' ', ucfirst($employee->employment_status)) }}</dd></div>
            <div><dt class="text-slate-500">Type</dt><dd class="mt-1 font-semibold">{{ $employee->employment_type ? str_replace('_', ' ', ucfirst($employee->employment_type)) : 'Not specified' }}</dd></div>
            <div><dt class="text-slate-500">Joining date</dt><dd class="mt-1 font-semibold">{{ $employee->joined_on?->format('d M Y') ?? 'Not specified' }}</dd></div>
            <div><dt class="text-slate-500">Work email</dt><dd class="mt-1 font-semibold break-all">{{ $employee->work_email ?? 'Not specified' }}</dd></div>
        </dl>
    </x-ui.card>
    <x-ui.card title="Organization">
        <dl class="grid grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">Department</dt><dd class="mt-1 font-semibold">{{ $employee->department?->name ?? ($employee->department_label ?: 'Unassigned') }}</dd></div>
            <div><dt class="text-slate-500">Location</dt><dd class="mt-1 font-semibold">{{ $employee->location ?: 'Not specified' }}</dd></div>
            <div class="col-span-2"><dt class="text-slate-500">Reports to</dt><dd class="mt-1 font-semibold">{{ $employee->manager?->display_name ?? 'Manager not confirmed' }}</dd></div>
        </dl>
    </x-ui.card>
</div>
@if($canManage)
<x-ui.card class="mt-6" title="Employee change history" description="The last 25 recorded actions for this employee; no passwords or full email values are stored in the audit log.">
    <div class="space-y-3">
    @forelse($history as $event)
        <div class="flex flex-wrap justify-between gap-2 border-b border-slate-100 pb-3 text-sm">
            <div><strong class="font-semibold">{{ str_replace('_', ' ', $event->action) }}</strong><p class="text-xs text-slate-500">Changed fields: {{ implode(', ', $event->context['fields'] ?? []) ?: 'Record action' }}</p></div>
            <time class="text-xs text-slate-500">{{ $event->created_at?->format('d M Y H:i') }}</time>
        </div>
    @empty
        <p class="text-sm text-slate-500">No recorded changes yet.</p>
    @endforelse
    </div>
</x-ui.card>
@endif
@endsection
