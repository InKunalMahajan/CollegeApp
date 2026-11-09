@extends('layouts.app')
@section('title','SaaS Plans')
@section('heading','SaaS Plans')
@section('content')
<x-ui.page-heading title="SaaS Plans & Trials" description="Website Owner only. Subscription records are informational. Online billing and enforcement are not enabled."/>
@if(session('success'))<x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>@endif
@if($errors->any())<x-ui.alert type="error" class="mb-4">{{ $errors->first() }}</x-ui.alert>@endif
<div class="grid gap-5 lg:grid-cols-2">
<x-ui.card title="Create plan">
<form method="POST" action="{{ route('saas.plans.store') }}" class="grid gap-3">@csrf
<label>Code<input required name="code" maxlength="60" class="w-full"></label>
<label>Name<input required name="name" maxlength="120" class="w-full"></label>
<label>Price in paise (not charged)<input required type="number" name="price_paise" value="0" min="0" max="1000000000" class="w-full"></label>
<label>Campus limit (optional)<input type="number" name="max_campuses" min="1" class="w-full"></label>
<label>Active student limit (optional)<input type="number" name="max_active_students" min="1" class="w-full"></label>
<button class="rounded-xl bg-indigo-600 px-4 py-2 text-white">Save plan</button>
</form></x-ui.card>
<x-ui.card title="Assign trial subscription">
<form method="POST" action="{{ route('saas.plans.assign') }}" class="grid gap-3">@csrf
<label>Organization<select required name="school_id" class="w-full"><option value="">Select</option>@foreach($schools as $school)<option value="{{ $school->id }}">{{ $school->name }}</option>@endforeach</select></label>
<label>Plan<select required name="saas_plan_id" class="w-full"><option value="">Select</option>@foreach($plans->where('is_active',true) as $plan)<option value="{{ $plan->id }}">{{ $plan->name }}</option>@endforeach</select></label>
<label>Start date<input required type="date" name="starts_on" class="w-full"></label>
<label>End date (optional)<input type="date" name="ends_on" class="w-full"></label>
<button class="rounded-xl bg-indigo-600 px-4 py-2 text-white">Assign trial (no charge)</button>
</form></x-ui.card>
</div>
<x-ui.card title="Plan catalog" class="mt-5">
@forelse($plans as $plan)<p class="border-b py-3 text-sm">{{ $plan->name }} ({{ $plan->code }}) · ₹{{ number_format($plan->price_paise/100,2) }} · Campus cap {{ $plan->max_campuses ?? 'Not specified' }}</p>@empty<p>No plans yet.</p>@endforelse
</x-ui.card>
<x-ui.card title="Subscription records" class="mt-5">
@forelse($subscriptions as $subscription)<p class="border-b py-3 text-sm">{{ $subscription->school->name }} · {{ $subscription->plan->name }} · {{ $subscription->status }} · Start {{ $subscription->starts_on->format('Y-m-d') }}</p>@empty<p>No subscriptions.</p>@endforelse
{{ $subscriptions->links() }}
</x-ui.card>
@endsection