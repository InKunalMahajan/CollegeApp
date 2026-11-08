@extends('layouts.app')
@section('title', 'School Security Summary')
@section('heading', 'School Security Summary')
@section('content')
<section class="mb-6 rounded-xl border border-slate-200 bg-white p-6">
<h2 class="text-xl font-semibold">{{ $school->name }}</h2>
<p class="mt-1 text-sm text-slate-600">Only verified school administrators and auditors can access this school-scoped summary. Values are lifetime development records.</p>
</section>
<div class="mb-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
<div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Recorded attendance</p><p class="mt-2 text-2xl font-bold">{{ (int)$attendance->recorded }}</p><p class="text-xs text-slate-500">{{ (int)$attendance->attended }} present or late</p></div>
<div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Graded results</p><p class="mt-2 text-2xl font-bold">{{ $gradedResults }}</p></div>
<div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Manual fee balance</p><p class="mt-2 text-2xl font-bold">₹{{ number_format(max(0, $chargedPaise - $paidPaise)/100, 2) }}</p></div>
<div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Active loans</p><p class="mt-2 text-2xl font-bold">{{ $activeLoans }}</p></div>
</div>
<section class="rounded-xl border border-slate-200 bg-white p-6"><h2 class="mb-4 font-semibold">Recent security audit events</h2>
<div class="overflow-x-auto"><table class="w-full min-w-[530px] text-left text-sm"><thead><tr class="bg-slate-50"><th class="p-3">Date</th><th class="p-3">Action</th><th class="p-3">Object</th><th class="p-3">Actor ID</th></tr></thead><tbody class="divide-y divide-slate-100">
@forelse($events as $event)<tr><td class="p-3">{{ $event->created_at?->format('d M Y H:i') }}</td><td class="p-3">{{ $event->action }}</td><td class="p-3">{{ $event->subject_type }} #{{ $event->subject_id }}</td><td class="p-3">{{ $event->actor_user_id ?? 'Deleted user' }}</td></tr>
@empty<tr><td colspan="4" class="p-4 text-slate-500">No audit events have been recorded yet.</td></tr>@endforelse
</tbody></table></div></section>
@endsection
