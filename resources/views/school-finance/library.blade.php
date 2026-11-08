@extends('layouts.app')
@section('title','School Library')
@section('heading','School Library')
@section('content')
<h2 class="mb-2 text-xl font-semibold">{{ $school->name }} — Library</h2>
<p class="mb-5 text-sm text-slate-600">School-specific inventory and borrowing. Student enrollment must match the selected school.</p>
@if($errors->any())<div role="alert" class="mb-4 rounded-lg bg-rose-50 p-4 text-sm text-rose-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<section class="mb-6 rounded-xl border border-slate-200 bg-white p-6">
<h3 class="mb-3 font-semibold">Issue a copy</h3>
<form method="POST" action="{{ route('school-finance.issue',$school) }}" class="grid gap-4 md:grid-cols-3">@csrf
<div><label for="copy" class="block text-sm font-medium">Available book copy</label><select id="copy" name="library_copy_id" required class="w-full rounded-lg border border-slate-300 p-2"><option value="">Select copy</option>@foreach($copies->where('status','available') as $copy)<option value="{{ $copy->id }}">{{ $copy->barcode }} — {{ $copy->book->title }}</option>@endforeach</select></div>
<div><label for="student" class="block text-sm font-medium">Student</label><select id="student" name="class_enrollment_id" required class="w-full rounded-lg border border-slate-300 p-2"><option value="">Select student</option>@foreach($enrollments as $enrollment)<option value="{{ $enrollment->id }}">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</option>@endforeach</select></div>
<div><label for="due" class="block text-sm font-medium">Due date</label><input id="due" name="due_on" type="date" min="{{ now()->addDay()->toDateString() }}" class="w-full rounded-lg border border-slate-300 p-2" required></div>
<div><button class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white">Issue book</button></div></form>
</section>
<section class="mb-6 rounded-xl border border-slate-200 bg-white p-6"><h3 class="mb-3 font-semibold">Catalog</h3>
<ul class="space-y-2 text-sm">@forelse($books as $book)<li>{{ $book->title }} — {{ $book->copies->count() }} copies</li>@empty<li>No catalog books.</li>@endforelse</ul></section>
<section class="rounded-xl border border-slate-200 bg-white p-6"><h3 class="mb-3 font-semibold">Loans</h3>
<div class="space-y-3">@forelse($loans as $loan)<div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3"><div><p>{{ $loan->copy->book->title }} ({{ $loan->copy->barcode }})</p><p class="text-sm text-slate-600">{{ $loan->enrollment->student->first_name }} {{ $loan->enrollment->student->last_name }} · {{ $loan->returned_on ? 'Returned' : 'Due '.$loan->due_on->format('Y-m-d') }}</p></div>@unless($loan->returned_on)<form method="POST" action="{{ route('school-finance.return',[$school,$loan]) }}">@csrf<button class="rounded-lg border border-indigo-200 px-3 py-2 font-semibold text-indigo-700">Return book</button></form>@endunless</div>@empty<p>No loans yet.</p>@endforelse</div><div class="mt-4">{{ $loans->links() }}</div></section>
@endsection
