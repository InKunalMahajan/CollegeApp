@props(['variant'=>'primary', 'type'=>'button', 'size'=>'md'])
@php
    $styles=match($variant) {
        'secondary'=>'border border-slate-200 bg-white text-slate-800 hover:bg-slate-50',
        'danger'=>'border border-rose-200 bg-white text-rose-700 hover:bg-rose-50',
        'ghost'=>'text-slate-700 hover:bg-slate-100',
        default=>'bg-indigo-600 text-white hover:bg-indigo-700',
    };
    $spacing=$size==='sm'?'px-3 py-2 text-xs':'px-4 py-2.5 text-sm';
@endphp
<button type="{{ $type }}" {{ $attributes->class(['inline-flex min-h-10 items-center justify-center gap-2 rounded-lg font-semibold transition disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600',$styles,$spacing]) }}>
    {{ $slot }}
</button>
