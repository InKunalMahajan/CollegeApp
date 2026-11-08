@props(['type' => 'info'])
@php
    $style = match ($type) {
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
        'error' => 'border-rose-200 bg-rose-50 text-rose-900',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
        default => 'border-slate-200 bg-slate-50 text-slate-800',
    };
@endphp
<div {{ $attributes->class(['flex items-start gap-3 rounded-lg border px-4 py-3 text-sm', $style]) }} role="{{ $type === 'error' ? 'alert' : 'status' }}">
    <x-ui.icon :name="$type === 'success' ? 'check-circle' : 'alert-circle'" class="mt-0.5"/>
    <div class="min-w-0 flex-1">{{ $slot }}</div>
</div>
