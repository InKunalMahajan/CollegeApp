@props(['label', 'name', 'id'=>null, 'type'=>'text', 'hint'=>null])
@php $fieldId=$id ?? $name; @endphp
<div>
<label for="{{ $fieldId }}" class="mb-1 block text-sm font-medium text-slate-800">{{ $label }}</label>
<input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}" aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
@if($hint || $errors->has($name)) aria-describedby="{{ $fieldId }}-help" @endif
{{ $attributes->class(['w-full rounded-lg border px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400', $errors->has($name) ? 'border-rose-500' : 'border-slate-300']) }}>
@if($hint || $errors->has($name))<p id="{{ $fieldId }}-help" class="mt-1 text-xs {{ $errors->has($name) ? 'text-rose-700' : 'text-slate-500' }}">{{ $errors->first($name) ?: $hint }}</p>@endif
</div>
