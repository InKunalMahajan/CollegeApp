@extends('layouts.app')
@section('title', 'My timetable')
@section('heading', 'My timetable')
@section('content')
<section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-100 p-5"><h2 class="font-semibold">My weekly teaching schedule</h2><p class="mt-1 text-sm text-slate-500">Only your scheduled periods are shown.</p></div>
<div class="divide-y divide-slate-100">@forelse($entries as $entry)<div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 text-sm"><div><p class="font-semibold">{{ $entry->subject->name }} · {{ $entry->classSection->name }}</p><p class="mt-1 text-slate-600">{{ ['','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'][$entry->day_of_week] }}</p></div><span class="font-medium">{{ substr($entry->starts_at,0,5) }}–{{ substr($entry->ends_at,0,5) }} · {{ $entry->room ?? 'No room assigned' }}</span></div>@empty<div class="p-8 text-sm text-slate-500">No timetable periods assigned yet.</div>@endforelse</div></section>
@endsection
