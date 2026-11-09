@extends('layouts.app')
@section('title','School Portal')
@section('heading','School Portal')
@section('content')
<section class="mb-6 rounded-xl border border-slate-200 bg-white p-6">
<h2 class="text-xl font-semibold">{{ $school->name }}</h2>
<p class="mt-2 text-sm text-slate-600">Read-only school-scoped records. Membership is required. Each list shows up to 100 entries.</p>
<a class="mt-3 mr-4 inline-flex items-center gap-2 text-sm font-semibold text-indigo-700" href="{{ route('school-dashboard.index',$school) }}"><x-ui.icon name="layout-dashboard"/> School dashboard</a>
<a class="mt-3 inline-block text-sm font-semibold text-indigo-700" href="{{ route('school-security.report',$school) }}">School security summary</a>
</section>
<section class="grid gap-6 lg:grid-cols-2">
<div class="schoolapp-panel rounded-xl border border-slate-200 bg-white p-6"><h3 class="mb-4 font-semibold">Enrolled students</h3><ul class="space-y-2 text-sm">@forelse($students as $student)<li>{{ $student->admission_number }} · {{ $student->first_name }} {{ $student->last_name }}</li>@empty<li class="text-slate-500">No enrolled students.</li>@endforelse</ul></div>
<div class="schoolapp-panel rounded-xl border border-slate-200 bg-white p-6"><h3 class="mb-4 font-semibold">Teachers</h3><ul class="space-y-2 text-sm">@forelse($teachers as $teacher)<li>{{ $teacher->employee_code }} · {{ $teacher->name }}</li>@empty<li class="text-slate-500">No teachers.</li>@endforelse</ul></div>
<div class="schoolapp-panel rounded-xl border border-slate-200 bg-white p-6"><h3 class="mb-4 font-semibold">Fee charges</h3><ul class="space-y-2 text-sm">@forelse($charges as $charge)<li>{{ $charge->name }} · ₹{{ number_format($charge->amount_paise/100,2) }}</li>@empty<li class="text-slate-500">No charges.</li>@endforelse</ul></div>
<div class="schoolapp-panel rounded-xl border border-slate-200 bg-white p-6"><h3 class="mb-4 font-semibold">Library books</h3><ul class="space-y-2 text-sm">@forelse($books as $book)<li>{{ $book->title }} · {{ $book->copies }} copies</li>@empty<li class="text-slate-500">No catalog books.</li>@endforelse</ul></div>
</section>
@endsection
