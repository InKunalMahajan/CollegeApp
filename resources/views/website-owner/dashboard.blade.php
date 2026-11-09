@extends('layouts.app')
@section('title', 'Website Owner Dashboard')
@section('heading', 'Website Owner Dashboard')
@section('content')
<div class="mb-7 flex flex-wrap items-start justify-between gap-4">
    <div>
        <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">Platform administration</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Website Owner Dashboard</h1>
        <p class="mt-2 max-w-2xl text-sm text-slate-600">Manage the SchoolApp platform, organization onboarding and account security. TEACH academic and campus records stay in their own authorized workspaces.</p>
    </div>
    <a href="{{ route('website-owner.users.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"><x-ui.icon name="users" /> Manage platform users</a>
</div>
<div class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Platform overview">
    @foreach([
        ['Organizations', 'organizations', 'school'],
        ['Campuses', 'campuses', 'building-2'],
        ['Total accounts', 'accounts', 'users'],
        ['Active accounts', 'enabled_accounts', 'user-check'],
        ['Disabled accounts', 'disabled_accounts', 'user-x'],
        ['Website owners', 'website_owners', 'shield-check'],
        ['Organization admins', 'organization_admin_assignments', 'user-cog'],
        ['Campus admins', 'campus_admin_assignments', 'users'],
    ] as [$label, $metric, $icon])
    <x-ui.card>
        <p class="flex items-center gap-2 text-sm font-medium text-slate-600"><x-ui.icon :name="$icon" /> {{ $label }}</p>
        <p class="mt-3 text-3xl font-bold tabular-nums text-slate-900">{{ number_format($metrics[$metric]) }}</p>
    </x-ui.card>
    @endforeach
</div>
<div class="grid gap-5 xl:grid-cols-[1.5fr_1fr]">
    <x-ui.card title="Organizations" description="Platform directory. An organization dashboard requires a separate organization membership.">
        <div class="space-y-3">
            @forelse($organizations as $organization)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-4">
                <div>
                    <h2 class="font-semibold text-slate-900">{{ $organization->name }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ $organization->code }} · {{ $organization->campuses_count }} {{ \Illuminate\Support\Str::plural('campus', $organization->campuses_count) }}</p>
                </div>
                @if(in_array($organization->id, $myAdminSchoolIds))
                <a class="text-sm font-semibold text-indigo-700 hover:underline" href="{{ route('school-dashboard.index', $organization) }}">Open organization dashboard</a>
                @else
                <span class="text-xs text-slate-500">Organization access not assigned</span>
                @endif
            </div>
            @empty
            <p class="text-sm text-slate-500">No organizations have been created yet.</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $organizations->links() }}</div>
    </x-ui.card>
    <x-ui.card title="Platform management" description="Website-level administration only.">
        <div class="space-y-3 text-sm">
            <a href="{{ route('users.index') }}" class="block rounded-xl border border-slate-200 p-4 hover:border-indigo-300 hover:bg-indigo-50">
                <span class="font-semibold">User accounts and roles</span>
                <span class="mt-1 block text-xs text-slate-500">Create, edit, disable or re-enable platform accounts</span>
            </a>
            <a href="{{ route('website-owner.users.index') }}" class="block rounded-xl border border-slate-200 p-4 hover:border-indigo-300 hover:bg-indigo-50">
                <span class="font-semibold">Organization administrator access</span>
                <span class="mt-1 block text-xs text-slate-500">Appoint or revoke TEACH and other organization administrators</span>
            </a>
            <a href="{{ route('website-owner.appearance.index') }}" class="block rounded-xl border border-slate-200 p-4 hover:border-indigo-300 hover:bg-indigo-50">
                <span class="font-semibold">Colors, fonts & text size</span>
                <span class="mt-1 block text-xs text-slate-500">Set platform colors, reading size and layout spacing</span>
            </a>
            <a href="{{ route('my-schools.index') }}" class="block rounded-xl border border-slate-200 p-4 hover:border-indigo-300 hover:bg-indigo-50">
                <span class="font-semibold">My authorized workspaces</span>
                <span class="mt-1 block text-xs text-slate-500">Open only the organizations and campuses assigned to you</span>
            </a>
        </div>
    </x-ui.card>
</div>
@endsection