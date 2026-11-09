@extends('layouts.app')
@section('title','Organization Branding')
@section('heading','Organization Branding')
@section('content')
<x-ui.page-heading :title="$school->name.' · Branding & Domains'" description="Choose your organization's name, colors, font and text size. These settings affect only this school and its campuses."/>
@if(session('success'))<x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>@endif
@if($errors->any())<x-ui.alert type="error" class="mb-4">{{ $errors->first() }}</x-ui.alert>@endif
<div class="grid gap-5 lg:grid-cols-2">
<x-ui.card title="Organization design preferences">
<form method="POST" action="{{ route('saas.branding.update',$school) }}" class="grid gap-3">@csrf @method('PUT')
<label>Display name<input required name="display_name" maxlength="120" value="{{ old('display_name',$branding?->display_name ?? $school->name) }}" class="w-full"></label>
<label>Primary color <input type="color" required name="primary_color" value="{{ old('primary_color',$branding?->primary_color ?? '#4F46E5') }}" class="mt-2 block h-12 w-24"></label>
<p class="text-xs text-slate-500">Choose a dark enough color for white button labels. Your organization color does not change other schools.</p>
<label>Font family
<select name="font_family" class="mt-1 w-full">
@foreach(config('appearance.fonts') as $key=>$label)
<option value="{{ $key }}" @selected(old('font_family',$branding?->font_family ?? 'system') === $key)>{{ $label }}</option>
@endforeach
</select></label>
<label>Base font size
<select name="font_size" class="mt-1 w-full">
@foreach(config('appearance.sizes') as $size)
<option value="{{ $size }}" @selected((int) old('font_size',$branding?->font_size ?? 16) === $size)>{{ $size }} px</option>
@endforeach
</select></label>
<label>Layout spacing
<select name="density" class="mt-1 w-full">
<option value="comfortable" @selected(old('density',$branding?->density ?? 'comfortable') === 'comfortable')>Comfortable</option>
<option value="compact" @selected(old('density',$branding?->density ?? 'comfortable') === 'compact')>Compact</option>
</select></label>
<button class="rounded-xl bg-indigo-600 px-4 py-2 text-white">Save branding</button>
</form>
</x-ui.card>
<x-ui.card title="Request custom domain">
<form method="POST" action="{{ route('saas.branding.domain',$school) }}" class="grid gap-3">@csrf
<label>Hostname (e.g. portal.school.example)<input type="text" name="hostname" required maxlength="253" class="w-full"></label>
<button class="rounded-xl bg-indigo-600 px-4 py-2 text-white">Request domain</button>
</form>
<p class="mt-3 text-xs text-amber-700">Custom domains remain pending until DNS proof, TLS certificates and hostname-routing safeguards are implemented and approved.</p>
</x-ui.card>
</div>
<x-ui.card title="Domain request register" class="mt-5">
@forelse($domains as $domain)<p class="border-b py-3 text-sm">{{ $domain->hostname }} · {{ $domain->status }}</p>@empty<p>No domains requested.</p>@endforelse
</x-ui.card>
@endsection