@props(['label'=>null])
<div {{ $attributes->class(['overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm']) }}>
<table class="w-full text-left text-sm" @if($label) aria-label="{{ $label }}" @endif>{{ $slot }}</table>
</div>
