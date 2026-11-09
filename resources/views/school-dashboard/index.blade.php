@extends('layouts.app')
@section('title','School Dashboard')
@section('heading','School Dashboard')
@section('content')
<x-ui.page-heading :title="$school->name.' — Dashboard'" description="Verified school-owned records. Attendance and exam figures use your selected dates; fee balances are lifetime totals.">
    <x-slot:actions>
        <a href="{{ route('school-portal.index',$school) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"><x-ui.icon name="school"/> School portal</a>
        <a href="{{ route('school-dashboard.export', array_merge(['school'=>$school],request()->only('start_date','end_date'))) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"><x-ui.icon name="chart-column"/> Export CSV</a>
    </x-slot:actions>
</x-ui.page-heading>
@if($errors->any())
<x-ui.alert type="error" class="mb-5">{{ $errors->first() }}</x-ui.alert>
@endif
<form action="{{ route('school-dashboard.index',$school) }}" method="GET" class="mb-6 flex flex-wrap items-end gap-4 rounded-xl border border-slate-200 bg-white p-5" aria-label="Dashboard date filters">
    <div><label for="start_date" class="mb-1 block text-sm font-medium">Start date</label>
    <input id="start_date" type="date" name="start_date" value="{{ $metrics['start'] }}" required class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
    <div><label for="end_date" class="mb-1 block text-sm font-medium">End date</label>
    <input id="end_date" type="date" name="end_date" value="{{ $metrics['end'] }}" required class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
    <x-ui.button type="submit"><x-ui.icon name="filter"/> Apply dates</x-ui.button>
    <a href="{{ route('school-dashboard.index',$school) }}" class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium">Last 30 days</a>
    <p class="w-full text-xs text-slate-500">Maximum 366 days. Dates use the application timezone. No records are changed by these filters.</p>
</form>

<div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="School overview">
    <x-ui.card><p class="flex items-center gap-2 text-sm text-slate-600"><x-ui.icon name="users"/> Students</p>
        <p class="mt-2 text-3xl font-bold tabular-nums">{{ number_format($metrics['students']['total']) }}</p>
        <p class="mt-2 text-xs text-slate-500">{{ $metrics['students']['active'] }} active · {{ $metrics['students']['enrolled'] }} enrolled</p>
    </x-ui.card>
    <x-ui.card><p class="flex items-center gap-2 text-sm text-slate-600"><x-ui.icon name="graduation-cap"/> Teachers</p>
        <p class="mt-2 text-3xl font-bold tabular-nums">{{ number_format($metrics['teachers']) }}</p>
        <p class="mt-2 text-xs text-slate-500">Verified school and campus</p>
    </x-ui.card>
    <x-ui.card><p class="flex items-center gap-2 text-sm text-slate-600"><x-ui.icon name="calendar-check"/> Attendance rate</p>
        <p class="mt-2 text-3xl font-bold tabular-nums">{{ number_format($metrics['attendance']['rate'],1) }}%</p>
        <p class="mt-2 text-xs text-slate-500">{{ $metrics['attendance']['attended'] }} present or late / {{ $metrics['attendance']['recorded'] }} records in period</p>
    </x-ui.card>
    <x-ui.card><p class="flex items-center gap-2 text-sm text-slate-600"><x-ui.icon name="clipboard-check"/> Exam pass rate</p>
        <p class="mt-2 text-3xl font-bold tabular-nums">{{ number_format($metrics['exams']['pass_rate'],1) }}%</p>
        <p class="mt-2 text-xs text-slate-500">{{ $metrics['exams']['passed'] }} passed / {{ $metrics['exams']['graded'] }} graded results in period</p>
    </x-ui.card>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <x-ui.card title="Attendance activity" description="Each bar shows present or late records divided by all recorded attendance on that date.">
        @if(count($metrics['attendance']['daily']))
        <div class="max-h-96 space-y-3 overflow-y-auto pr-2" role="list" aria-label="Daily attendance rates">
            @foreach($metrics['attendance']['daily'] as $day)
                <div role="listitem" class="grid grid-cols-[6rem_1fr_3.5rem] items-center gap-3 text-xs">
                    <span class="tabular-nums text-slate-600">{{ $day['date'] }}</span>
                    <div class="h-3 overflow-hidden rounded-full bg-slate-100" aria-hidden="true">
                        <div class="h-full rounded-full bg-indigo-600" style="width: {{ min(100,max(0,$day['rate'])) }}%"></div>
                    </div>
                    <span class="text-right font-semibold tabular-nums" aria-label="{{ $day['date'] }}: {{ $day['rate'] }} percent, {{ $day['attended'] }} of {{ $day['recorded'] }} records">{{ number_format($day['rate'],1) }}%</span>
                </div>
            @endforeach
        </div>
        @else
        <p class="text-sm text-slate-500">No recorded attendance in this period.</p>
        @endif
    </x-ui.card>

    <x-ui.card title="Examination performance" description="Graded results for exams dated within the selected period.">
        <div class="space-y-4">
            <div class="flex items-center justify-between"><span class="text-sm text-slate-600">Average marks (percentage)</span>
                <strong class="tabular-nums">{{ number_format($metrics['exams']['average_percent'],1) }}%</strong></div>
            <div class="h-3 overflow-hidden rounded-full bg-slate-100" aria-hidden="true">
                <div class="h-full rounded-full bg-indigo-600" style="width: {{ min(100,max(0,$metrics['exams']['average_percent'])) }}%"></div>
            </div>
            <div class="flex items-center justify-between"><span class="text-sm text-slate-600">Passed / graded</span>
                <strong class="tabular-nums">{{ $metrics['exams']['passed'] }} / {{ $metrics['exams']['graded'] }}</strong></div>
            @if($metrics['exams']['graded']===0)
                <p class="text-sm text-slate-500">No graded exam results in this period.</p>
            @endif
        </div>
    </x-ui.card>

    <x-ui.card title="Fee collection" description="Balances are lifetime totals; collections in period use payment dates.">
        <dl class="space-y-3 text-sm">
            <div class="flex items-center justify-between gap-3"><dt class="text-slate-600">Total charges</dt><dd class="font-semibold tabular-nums">₹{{ number_format($metrics['fees']['charged_paise']/100,2) }}</dd></div>
            <div class="flex items-center justify-between gap-3"><dt class="text-slate-600">Total payments</dt><dd class="font-semibold tabular-nums">₹{{ number_format($metrics['fees']['paid_paise']/100,2) }}</dd></div>
            <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-3"><dt class="font-semibold">Outstanding balance</dt><dd class="font-bold tabular-nums">₹{{ number_format($metrics['fees']['outstanding_paise']/100,2) }}</dd></div>
            <div class="flex items-center justify-between gap-3"><dt class="text-slate-600">Collected in period</dt><dd class="font-semibold tabular-nums">₹{{ number_format($metrics['fees']['period_paid_paise']/100,2) }}</dd></div>
        </dl>
    </x-ui.card>

    <x-ui.card title="Library overview" description="School catalog and verified active loans.">
        <dl class="space-y-3 text-sm">
            <div class="flex items-center justify-between"><dt class="text-slate-600">Book titles</dt><dd class="font-semibold tabular-nums">{{ $metrics['library']['titles'] }}</dd></div>
            <div class="flex items-center justify-between"><dt class="text-slate-600">Book copies</dt><dd class="font-semibold tabular-nums">{{ $metrics['library']['copies'] }}</dd></div>
            <div class="flex items-center justify-between"><dt class="text-slate-600">Active loans</dt><dd class="font-semibold tabular-nums">{{ $metrics['library']['active_loans'] }}</dd></div>
        </dl>
    </x-ui.card>
</div>
<div class="mt-6 flex flex-wrap gap-3 text-sm">
    <a class="font-semibold text-indigo-700 hover:underline" href="{{ route('school-security.report',$school) }}">Security and audit report</a>
    @if(\App\Models\SchoolMembership::where('school_id',$school->id)->where('user_id',auth()->id())->where('role','school_admin')->exists())
        <a class="font-semibold text-indigo-700 hover:underline" href="{{ route('school-students.index',$school) }}">Manage school students</a>
    @endif
</div>
@endsection
