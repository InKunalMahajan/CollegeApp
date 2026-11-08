@extends('layouts.app')
@section('title','Fee Management')
@section('heading','Fee Management')
@section('content')
<p class="mb-6 text-sm text-slate-600">Create class fee schedules, issue charges to enrolled learners, and record manual payments in INR. Payment records are not payment-gateway confirmations.</p>
@if($errors->any())<div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<section class="mb-7 rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><h2 class="mb-4 font-semibold">Create fee structure</h2>
<form method="POST" action="{{ route('fees.structures.store') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">@csrf
<div><label for="class_section_id" class="mb-1 block text-sm font-semibold">Class</label><select id="class_section_id" name="class_section_id" required class="w-full rounded-lg border border-slate-300 p-2.5"><option value="">Select class</option>@foreach($sections as $section)<option value="{{ $section->id }}">{{ $section->campus->name }} · {{ $section->name }}</option>@endforeach</select></div>
<div><label for="fee_name" class="mb-1 block text-sm font-semibold">Fee title</label><input id="fee_name" name="name" required maxlength="150" placeholder="Term 1 fees" class="w-full rounded-lg border border-slate-300 p-2.5"></div>
<div><label for="fee_amount" class="mb-1 block text-sm font-semibold">Amount (INR)</label><input id="fee_amount" name="amount" required inputmode="decimal" placeholder="1500.00" class="w-full rounded-lg border border-slate-300 p-2.5"></div>
<div><label for="due_date" class="mb-1 block text-sm font-semibold">Due date</label><input id="due_date" name="due_date" type="date" required class="w-full rounded-lg border border-slate-300 p-2.5"></div>
<div><button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white">Create structure</button></div></form></section>
<section class="mb-7 rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><h2 class="mb-4 font-semibold">Issue charges</h2><div class="space-y-3">@forelse($structures as $structure)<div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 py-3"><div><p class="font-semibold">{{ $structure->name }}</p><p class="text-sm text-slate-600">{{ $structure->classSection->name }} · ₹{{ number_format($structure->amount_paise / 100, 2) }} · Due {{ $structure->due_date->format('d M Y') }}</p></div><form method="POST" action="{{ route('fees.structures.issue',$structure) }}">@csrf<button class="rounded-lg border border-indigo-200 px-3 py-2 text-sm font-semibold text-indigo-700">Issue to enrolled students</button></form></div>@empty<p class="text-sm text-slate-500">No fee structures yet.</p>@endforelse</div></section>
<section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><h2 class="mb-4 font-semibold">Student charges and payments</h2><div class="space-y-5">@forelse($charges as $charge)
@php($paid = (int) ($charge->payments_sum_amount_paise ?? 0))
@php($balance = max(0, $charge->amount_paise - $paid))
<div class="rounded-lg border border-slate-200 p-4"><h3 class="font-semibold">{{ $charge->enrollment->student->first_name }} {{ $charge->enrollment->student->last_name }} — {{ $charge->structure->name }}</h3>
<p class="mt-2 text-sm text-slate-600">Charged: ₹{{ number_format($charge->amount_paise / 100,2) }} · Paid: ₹{{ number_format($paid / 100,2) }} · Outstanding: ₹{{ number_format($balance / 100,2) }}</p>
@if($balance > 0)<form method="POST" action="{{ route('fees.payments.store',$charge) }}" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">@csrf
<div><label for="amount-{{ $charge->id }}" class="mb-1 block text-xs font-semibold">Amount INR</label><input id="amount-{{ $charge->id }}" name="amount" placeholder="500.00" required inputmode="decimal" class="w-full rounded-lg border border-slate-300 p-2"></div>
<div><label for="date-{{ $charge->id }}" class="mb-1 block text-xs font-semibold">Payment date</label><input id="date-{{ $charge->id }}" name="paid_on" type="date" required max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" class="w-full rounded-lg border border-slate-300 p-2"></div>
<div><label for="method-{{ $charge->id }}" class="mb-1 block text-xs font-semibold">Method</label><select id="method-{{ $charge->id }}" name="method" class="w-full rounded-lg border border-slate-300 p-2">@foreach(['cash'=>'Cash','upi'=>'UPI','bank_transfer'=>'Bank transfer','card'=>'Card','other'=>'Other'] as $key=>$value)<option value="{{ $key }}">{{ $value }}</option>@endforeach</select></div>
<div><label for="reference-{{ $charge->id }}" class="mb-1 block text-xs font-semibold">Reference (optional)</label><input id="reference-{{ $charge->id }}" name="reference" maxlength="100" class="w-full rounded-lg border border-slate-300 p-2"></div><div class="flex items-end"><button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Record payment</button></div></form>@else<p class="mt-3 text-sm font-semibold text-emerald-700">Paid in full</p>@endif
</div>
@empty<p class="text-sm text-slate-500">Issue a fee structure to create student charges.</p>@endforelse</div><div class="mt-4">{{ $charges->links() }}</div>
<p class="mt-5 text-xs text-slate-500">To view receipts, use the recorded payment ID at /fees/receipts/{id}. This is an internal payment acknowledgment, not a tax invoice.</p>
</section>
@endsection
