@extends('layouts.app')
@section('title','Request Organization')
@section('heading','Request Organization')
@section('content')
<x-ui.page-heading title="Request a school organization" description="Submit a school request. A Website Owner must review and approve it before your organization is created."/>
@if(session('success'))<x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>@endif
@if($errors->any())<x-ui.alert type="error" class="mb-4">{{ $errors->first() }}</x-ui.alert>@endif
<x-ui.card title="New organization request">
<form method="POST" action="{{ route('saas.onboarding.store') }}" class="grid gap-3 sm:grid-cols-2">@csrf
<label>Organization name<input name="organization_name" required maxlength="255" value="{{ old('organization_name') }}" class="w-full"></label>
<label>Unique code<input name="organization_code" required maxlength="30" value="{{ old('organization_code') }}" class="w-full"></label>
<label class="sm:col-span-2">Contact email<input type="email" name="contact_email" required maxlength="255" value="{{ old('contact_email') }}" class="w-full"></label>
<div class="sm:col-span-2"><button class="rounded-xl bg-indigo-600 px-4 py-2 text-white">Submit for owner review</button></div>
</form></x-ui.card>
<x-ui.card title="My requests" class="mt-5">
@forelse($requests as $record)<p class="border-b py-3 text-sm">{{ $record->organization_name }} · {{ $record->organization_code }} · {{ $record->status }}</p>@empty<p class="text-sm text-slate-500">No requests submitted.</p>@endforelse
{{ $requests->links() }}
</x-ui.card>
@endsection