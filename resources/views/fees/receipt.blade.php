@extends('layouts.app')
@section('title','Payment receipt')
@section('heading','Internal Payment Receipt')
@section('content')
<section class="max-w-xl rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
<h2 class="text-xl font-semibold">Payment acknowledgment #{{ $payment->id }}</h2>
<p class="mt-2 text-sm text-slate-500">This receipt documents a manually recorded payment and does not verify bank settlement or constitute a GST invoice.</p>
<dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
<dt class="font-semibold">Student</dt><dd>{{ $payment->charge->enrollment->student->first_name }} {{ $payment->charge->enrollment->student->last_name }}</dd>
<dt class="font-semibold">Fee</dt><dd>{{ $payment->charge->structure->name }}</dd>
<dt class="font-semibold">Amount</dt><dd>₹{{ number_format($payment->amount_paise / 100,2) }}</dd>
<dt class="font-semibold">Payment date</dt><dd>{{ $payment->paid_on->format('d M Y') }}</dd>
<dt class="font-semibold">Method</dt><dd>{{ ucfirst(str_replace('_',' ',$payment->method)) }}</dd>
<dt class="font-semibold">Reference</dt><dd>{{ $payment->reference ?? 'Not provided' }}</dd>
<dt class="font-semibold">Recorded by</dt><dd>{{ $payment->recorder->name }}</dd>
</dl><div class="mt-7"><a href="{{ route('fees.index') }}" class="text-sm font-semibold text-indigo-700">Back to fee management</a></div>
</section>
@endsection
