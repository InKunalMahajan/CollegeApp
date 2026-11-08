@extends('layouts.app')
@section('title','School Fees')
@section('heading','School Fees')
@section('content')
<h2 class="mb-2 text-xl font-semibold">{{ $school->name }} — Fee Payments</h2>
<p class="mb-5 text-sm text-slate-600">Only a school administrator can record payments here. All amounts are manually entered, not bank-verified.</p>
@if($errors->any())<div role="alert" class="mb-4 rounded-lg bg-rose-50 p-4 text-sm text-rose-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<section class="space-y-4">
@forelse($charges as $charge)
@php($paid = (int)($charge->payments_sum_amount_paise ?? 0))
@php($balance = max(0,$charge->amount_paise-$paid))
<div class="rounded-xl border border-slate-200 bg-white p-5">
<h3 class="font-semibold">{{ $charge->enrollment->student->first_name }} {{ $charge->enrollment->student->last_name }} — {{ $charge->structure->name }}</h3>
<p class="mt-1 text-sm text-slate-600">Charged ₹{{ number_format($charge->amount_paise/100,2) }} · Outstanding ₹{{ number_format($balance/100,2) }}</p>
@if($balance>0)<form method="POST" action="{{ route('school-finance.payment',[$school,$charge]) }}" class="mt-3 grid gap-3 sm:grid-cols-4">@csrf
<div><label class="block text-sm" for="amount-{{ $charge->id }}">Amount INR</label><input class="w-full rounded-lg border border-slate-300 p-2" id="amount-{{ $charge->id }}" name="amount" required placeholder="100.00"></div>
<div><label class="block text-sm" for="paid-{{ $charge->id }}">Paid on</label><input class="w-full rounded-lg border border-slate-300 p-2" id="paid-{{ $charge->id }}" name="paid_on" type="date" max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" required></div>
<div><label class="block text-sm" for="method-{{ $charge->id }}">Method</label><select class="w-full rounded-lg border border-slate-300 p-2" name="method" id="method-{{ $charge->id }}"><option value="cash">Cash</option><option value="upi">UPI</option><option value="bank_transfer">Bank transfer</option><option value="card">Card</option><option value="other">Other</option></select></div>
<div class="flex items-end"><button class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white">Record payment</button></div>
</form>@else<p class="mt-2 text-sm font-semibold text-emerald-700">Paid in full</p>@endif
</div>
@empty<p>No school fee charges yet.</p>@endforelse
</section><div class="mt-4">{{ $charges->links() }}</div>
@endsection
