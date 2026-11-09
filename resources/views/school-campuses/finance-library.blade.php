@extends('layouts.app')
@section('title','Campus Fees and Library')
@section('heading','Campus Fees and Library')
@section('content')
<x-ui.page-heading :title="$campus->name.' · Fees and Library'" :description="$school->name.' · Read-only verified campus records'"/>
<x-ui.alert type="info" class="mb-5">Viewing only. Payment recording, book issuance and returns remain organization-level operations until campus inventory and finance reconciliation are fully secured.</x-ui.alert>
<p class="mb-4"><a href="{{ route('campus.operations.index',[$school,$campus]) }}">← Campus operations</a></p>
<x-ui.card title="Fee charges">
@forelse($charges as $charge)
<div class="border-b py-2 text-sm">{{ $charge->enrollment->student->first_name }} {{ $charge->enrollment->student->last_name }} · {{ $charge->structure->name }} · ₹{{ number_format($charge->amount_paise / 100, 2) }}</div>
@empty<p>No verified campus charges.</p>@endforelse
{{ $charges->links() }}</x-ui.card>
<x-ui.card class="mt-5" title="Recorded fee payments">
@forelse($payments as $payment)
<div class="border-b py-2 text-sm">{{ $payment->charge->enrollment->student->first_name }} · ₹{{ number_format($payment->amount_paise / 100,2) }} · {{ $payment->paid_on?->format('Y-m-d') }} · {{ $payment->method }}</div>
@empty<p>No campus payments.</p>@endforelse
{{ $payments->links() }}</x-ui.card>
<x-ui.card class="mt-5" title="Student library loans">
@forelse($loans as $loan)
<div class="border-b py-2 text-sm">{{ $loan->enrollment->student->first_name }} · {{ $loan->copy->book->title }} · Due {{ $loan->due_on?->format('Y-m-d') }} · {{ $loan->returned_on ? 'Returned' : 'On loan' }}</div>
@empty<p>No verified campus loans.</p>@endforelse
{{ $loans->links() }}</x-ui.card>
@endsection