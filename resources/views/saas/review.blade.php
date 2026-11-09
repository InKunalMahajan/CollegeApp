@extends('layouts.app')
@section('title','Organization Requests')
@section('heading','Organization Requests')
@section('content')
<x-ui.page-heading title="Website Owner · Organization Approvals" description="Only approved requests create a tenant and assign an organization administrator."/>
@if(session('success'))<x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>@endif
@if($errors->any())<x-ui.alert type="error" class="mb-4">{{ $errors->first() }}</x-ui.alert>@endif
<x-ui.card title="Pending requests">
@forelse($requests as $record)
<div class="flex flex-wrap items-center justify-between gap-3 border-b py-4 text-sm">
<div><p class="font-semibold">{{ $record->organization_name }}</p><p class="text-slate-500">{{ $record->organization_code }} · {{ $record->contact_email }} · applicant #{{ $record->requested_by }}</p></div>
<div class="flex gap-2"><form method="POST" action="{{ route('saas.onboarding.approve',$record) }}" onsubmit="return confirm('Create this organization and appoint its applicant as admin?');">@csrf<button class="rounded-lg bg-indigo-600 px-3 py-2 text-white">Approve</button></form>
<form method="POST" action="{{ route('saas.onboarding.reject',$record) }}" onsubmit="return confirm('Reject this request?');">@csrf<button class="rounded-lg border px-3 py-2">Reject</button></form></div>
</div>
@empty<p>No pending requests.</p>@endforelse
{{ $requests->links() }}
</x-ui.card>
@endsection