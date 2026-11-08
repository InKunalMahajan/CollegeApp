@extends('layouts.app')
@section('title', ($record ? 'Edit ' : 'Add ').$definition['title'])
@section('heading', ($record ? 'Edit ' : 'Add ').$definition['title'])
@section('content')
<form method="POST" action="{{ $record ? route('setup.update', [$module, $record->id]) : route('setup.store', $module) }}" class="max-w-2xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf
    @if($record) @method('PUT') @endif
    @if($errors->any())
        <div class="mb-5 rounded-lg bg-rose-50 p-4 text-sm text-rose-700" role="alert">
            <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <div class="space-y-5">
    @foreach($definition['fields'] as $field)
    <div>
        <label class="mb-2 block text-sm font-medium" for="{{ $field }}">{{ ucwords(str_replace('_', ' ', $field)) }}</label>
        @if(isset($options[$field]))
            <select id="{{ $field }}" name="{{ $field }}" required class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5">
                <option value="">Select an option</option>
                @foreach($options[$field] as $value => $label)
                    <option value="{{ $value }}" @selected((string) old($field, $record?->$field) === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        @else
            <input id="{{ $field }}" name="{{ $field }}" type="{{ in_array($field, ['starts_on', 'ends_on']) ? 'date' : ($field === 'email' ? 'email' : 'text') }}"
                   value="{{ old($field, $record?->$field instanceof \DateTimeInterface ? $record->$field->format('Y-m-d') : $record?->$field) }}"
                   @required($field !== 'email' && $field !== 'city')
                   maxlength="{{ $field === 'code' || $field === 'name' && $module === 'academic-years' ? 30 : 255 }}"
                   class="w-full rounded-lg border border-slate-300 px-4 py-2.5">
        @endif
    </div>
    @endforeach
    </div>
    <div class="mt-8 flex items-center gap-4 border-t border-slate-100 pt-5">
        <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white">Save</button>
        <a href="{{ route('setup.index', $module) }}" class="text-sm font-medium text-slate-600 hover:underline">Cancel</a>
    </div>
</form>
@endsection
