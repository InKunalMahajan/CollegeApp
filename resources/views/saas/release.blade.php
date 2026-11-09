@extends('layouts.app')
@section('title','SaaS Release Checks')
@section('heading','SaaS Release Checks')
@section('content')
<x-ui.page-heading title="Website Owner · SaaS Release Checks" description="Read-only platform preflight. Passing checks do not mean the SaaS is production-ready."/>
<x-ui.card title="Application configuration">
@foreach($checks as $label=>$passed)
<div class="flex items-center justify-between gap-3 border-b py-3 text-sm">
<span>{{ $label }}</span><span class="{{ $passed ? 'text-emerald-700' : 'text-rose-700' }} font-semibold">{{ $passed ? 'PASS' : 'REVIEW' }}</span>
</div>
@endforeach
</x-ui.card>
<x-ui.card class="mt-5" title="SaaS database tables">
@foreach($schema as $table=>$exists)
<div class="flex items-center justify-between gap-3 border-b py-3 text-sm">
<code>{{ $table }}</code><span class="{{ $exists ? 'text-emerald-700' : 'text-rose-700' }} font-semibold">{{ $exists ? 'FOUND' : 'MISSING' }}</span>
</div>
@endforeach
</x-ui.card>
<x-ui.card class="mt-5" title="Manual release blockers">
<div class="space-y-3 text-sm text-slate-600">
<p>Review and run the complete MySQL and SQLite test suites, check access as Owner, TEACH organization admin, and separate Mumbai/Pune admins.</p>
<p>Test database restore and disaster recovery on staging, monitoring alerts, queue failures, rate limiting and mail delivery.</p>
<p><strong>Real money is disabled:</strong> no verified payment gateway or statutory tax invoice system is connected.</p>
<p><strong>Custom domains are pending only:</strong> DNS proof, TLS provisioning and safe hostname routing have not been enabled.</p>
<p><strong>Subscriptions are informational:</strong> entitlement limits and billing enforcement are not production-ready.</p>
</div>
</x-ui.card>
@endsection