@props(['title', 'description'=>null])
<div {{ $attributes->class(['mb-6 flex flex-wrap items-start justify-between gap-4']) }}>
<div><h2 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-slate-900">{{ $title }}</h2>
@if($description)<p class="mt-1 text-sm text-slate-600">{{ $description }}</p>@endif</div>
@if(isset($actions))<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endif
</div>
