@props(['title'=>null,'description'=>null])
<section {{ $attributes->class(['rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6']) }}>
@if($title)<h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>@endif
@if($description)<p class="mt-1 text-sm text-slate-600">{{ $description }}</p>@endif
<div @class(['mt-4'=>$title || $description])>{{ $slot }}</div>
</section>
