@props(['tone'=>'neutral'])
@php $color=match($tone) {
    'success'=>'bg-emerald-50 text-emerald-800 ring-emerald-200',
    'warning'=>'bg-amber-50 text-amber-800 ring-amber-200',
    'danger'=>'bg-rose-50 text-rose-800 ring-rose-200',
    'info'=>'bg-indigo-50 text-indigo-800 ring-indigo-200',
    default=>'bg-slate-100 text-slate-700 ring-slate-200',
}; @endphp
<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset',$color]) }}>{{ $slot }}</span>
