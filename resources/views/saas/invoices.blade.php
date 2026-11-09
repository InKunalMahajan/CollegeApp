@extends('layouts.app')
@section('title','SaaS Invoices')
@section('heading','SaaS Invoices')
@section('content')
<x-ui.page-heading title="SaaS Invoice Drafts" description="Internal records only — no live payment provider, final tax invoice, paid marking or refunds."/>
@if(session('success'))<x-ui.alert type="success" class="mb-4">{{ session('success') }}</x-ui.alert>@endif
@if($errors->any())<x-ui.alert type="error" class="mb-4">{{ $errors->first() }}</x-ui.alert>@endif
<x-ui.card title="Create draft invoice">
<form method="POST" action="{{ route('saas.invoices.store') }}" class="grid gap-3 sm:grid-cols-2">@csrf
<label>Subscription<select name="saas_subscription_id" required class="w-full"><option value="">Select subscription</option>@foreach($subscriptions as $subscription)<option value="{{ $subscription->id }}">{{ $subscription->school->name }} · {{ $subscription->plan->name }}</option>@endforeach</select></label>
<label>Amount in paise<input type="number" name="amount_paise" required min="0" class="w-full"></label>
<label class="sm:col-span-2">Description<input name="description" maxlength="2000" class="w-full"></label>
<div class="sm:col-span-2"><button class="rounded-xl bg-indigo-600 px-4 py-2 text-white">Save draft invoice</button></div>
</form></x-ui.card>
<x-ui.card class="mt-5" title="Draft ledger">
@forelse($invoices as $invoice)<p class="border-b py-3 text-sm">{{ $invoice->invoice_number }} · {{ $invoice->school->name }} · ₹{{ number_format($invoice->amount_paise/100,2) }} · {{ $invoice->status }}</p>
@empty<p>No draft invoices.</p>@endforelse
{{ $invoices->links() }}
</x-ui.card>
@endsection