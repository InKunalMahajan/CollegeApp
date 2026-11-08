@extends('layouts.app')
@section('title', $definition['title'])
@section('heading', $definition['title'])
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <p class="text-sm text-slate-600">Manage {{ strtolower($definition['title']) }} for your institution.</p>
    <a class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700" href="{{ route('setup.create', $module) }}">+ Add record</a>
</div>
@if ($errors->has('delete'))
    <div role="alert" class="mb-5 rounded-lg bg-rose-50 p-4 text-sm text-rose-700">{{ $errors->first('delete') }}</div>
@endif
<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
<table class="w-full min-w-[600px] text-left text-sm">
<thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
<tr><th class="px-5 py-4">ID</th>
@foreach ($definition['fields'] as $field)<th class="px-5 py-4">{{ ucwords(str_replace('_', ' ', $field)) }}</th>@endforeach
<th class="px-5 py-4 text-right">Actions</th></tr>
</thead>
<tbody class="divide-y divide-slate-100">
@forelse($records as $record)
<tr>
    <td class="px-5 py-4 text-slate-500">{{ $record->id }}</td>
    @foreach($definition['fields'] as $field)
    <td class="px-5 py-4">{{ $record->$field instanceof \DateTimeInterface ? $record->$field->format('Y-m-d') : ($record->$field ?? '—') }}</td>
    @endforeach
    <td class="px-5 py-4"><div class="flex justify-end gap-3">
        <a class="font-medium text-indigo-700 hover:underline" href="{{ route('setup.edit', [$module, $record->id]) }}">Edit</a>
        <form method="POST" action="{{ route('setup.destroy', [$module, $record->id]) }}" onsubmit="return confirm('Delete this record?');">
            @csrf @method('DELETE')
            <button class="font-medium text-rose-700 hover:underline">Delete</button>
        </form>
    </div></td>
</tr>
@empty
<tr><td colspan="{{ count($definition['fields']) + 2 }}" class="px-5 py-12 text-center text-slate-500">No records yet.</td></tr>
@endforelse
</tbody></table>
</div>
<div class="mt-5">{{ $records->links() }}</div>
@endsection
