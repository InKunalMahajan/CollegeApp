@extends('layouts.app')
@section('title','Organization Branding')
@section('heading','Organization Branding')
@section('content')
<x-ui.page-heading :title="$school->name.' · Branding & Domains'" description="Organization-specific appearance and custom domain requests. Pending domains are never routed automatically."/>
@if(session('success'))<x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>@endif
@if($errors->any())<x-ui.alert type="error" class="mb-4">{{ $errors->first() }}</x-ui.alert>@endif
<div class="grid gap-5 lg:grid-cols-2">
<x-ui.card title="School brand">
<form method="POST" action="{{ route('saas.branding.update',$school) }}" class="grid gap-3">@csrf @method('PUT')
<label>Display name<input required name="display_name" maxlength="120" value="{{ old('display_name',$branding?->display_name ?? $school->name) }}" class="w-full"></label>
<label>Primary hex color<input required name="primary_color" pattern="#[0-9a-fA-F]{6}" maxlength="7" value="{{ old('primary_color',$branding?->primary_color ?? '#4F46E5') }}" class="w-full"></label>
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