@extends('layouts.app')
@section('title','Students')
@section('heading','Student Management')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
<div><h2 class="text-xl font-semibold flex items-center gap-2"><x-ui.icon name="users" size="size-5" /> {{ $school->name }} — Students</h2>
<p class="mt-1 text-sm text-slate-600">View student profiles belonging to this school only.</p></div>
<a class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold inline-flex items-center gap-2" href="{{ route('school-academics.index',$school) }}"><x-ui.icon name="school"/> Academics & admission</a>
</div>
<form method="GET" action="{{ route('school-students.index',$school) }}" class="mb-5 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-4">
<div class="sm:col-span-2"><label for="search" class="block text-sm font-medium">Search name or admission number</label>
<input id="search" name="search" value="{{ $search }}" maxlength="100" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5"></div>
<div><label for="status" class="block text-sm font-medium">Admission status</label>
<select name="status" id="status" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5">
<option value="">All statuses</option>@foreach(['active'=>'Active','inactive'=>'Inactive','graduated'=>'Graduated'] as $value=>$label)
<option value="{{ $value }}" @selected($status===$value)>{{ $label }}</option>@endforeach
</select></div>
<div class="flex items-end gap-2"><x-ui.button type="submit"><x-ui.icon name="search"/> Search</x-ui.button>
<a href="{{ route('school-students.index',$school) }}" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm inline-flex items-center gap-2"><x-ui.icon name="refresh"/> Reset</a></div>
</form>
<x-ui.table label="School students"><thead class="bg-slate-50 text-slate-600"><tr>
<th scope="col" class="px-4 py-3">Admission no.</th><th scope="col" class="px-4 py-3">Student</th><th scope="col" class="px-4 py-3">Status</th><th scope="col" class="px-4 py-3">Admission date</th><th scope="col" class="px-4 py-3">Action</th>
</tr></thead><tbody>
@forelse($students as $student)<tr class="border-t border-slate-100">
<td class="px-4 py-3">{{ $student->admission_number }}</td>
<td class="px-4 py-3 font-medium">{{ $student->first_name }} {{ $student->last_name }}</td>
<td class="px-4 py-3"><x-ui.badge :tone="$student->status === 'active' ? 'success' : ($student->status === 'graduated' ? 'info' : 'neutral')">{{ ucfirst($student->status ?? 'active') }}</x-ui.badge></td>
<td class="px-4 py-3">{{ $student->admission_date?->format('d M Y') }}</td>
<td class="px-4 py-3"><a class="font-semibold text-indigo-700 inline-flex items-center gap-1" href="{{ route('school-students.show',[$school,$student]) }}"><x-ui.icon name="eye"/> View</a></td>
</tr>@empty
<tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No students match your search.</td></tr>
@endforelse</tbody></x-ui.table>
<div class="mt-4">{{ $students->links() }}</div>
@endsection
