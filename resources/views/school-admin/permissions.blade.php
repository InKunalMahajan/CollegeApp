@extends('layouts.app')
@section('title', 'Role Access Matrix')
@section('heading', 'Role Access Matrix')
@section('content')
<x-ui.page-heading :title="$school->name.' — Access Control'" description="Authoritative, read-only permission registry. Permissions apply only within their assigned school or department."/>
<div class="mb-5 flex flex-wrap items-center gap-3">
    <x-ui.badge tone="info">Default deny</x-ui.badge>
    <x-ui.badge tone="neutral">Server-enforced</x-ui.badge>
    <x-ui.badge tone="neutral">School-scoped</x-ui.badge>
</div>
<x-ui.card title="School-level permissions" description="These permissions are enforced through school.can middleware. A department member only sees records permitted by their department membership.">
    <div class="overflow-x-auto">
        <table class="min-w-[740px] w-full text-left text-sm">
            <thead>
                <tr><th>Permission</th><th>School admin</th><th>Auditor</th><th>Department member</th></tr>
            </thead>
            <tbody>
                @foreach($abilities as $ability => $description)
                    <tr>
                        <td><div class="font-semibold">{{ $description }}</div><code class="text-xs text-slate-500">{{ $ability }}</code></td>
                        @foreach(['school_admin', 'auditor', 'department_member'] as $role)
                            @php $granted = in_array('*', $schoolRoles[$role] ?? [], true) || in_array($ability, $schoolRoles[$role] ?? [], true); @endphp
                            <td>
                                <span class="font-semibold {{ $granted ? 'text-emerald-700' : 'text-slate-400' }}">
                                    {{ $granted ? 'Allowed' : 'Denied' }}
                                </span>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-ui.card>
<x-ui.card class="mt-6" title="Department-level permissions" description="These only apply to departments where a user has an explicit role assignment. They do not authorize school-wide finance, HR or academic writes.">
    <div class="overflow-x-auto">
        <table class="min-w-[700px] w-full text-left text-sm">
            <thead>
                <tr><th>Permission</th><th>Department head</th><th>Department staff</th><th>Department viewer</th></tr>
            </thead>
            <tbody>
                @foreach($departmentAbilities as $ability => $description)
                    <tr>
                        <td><div class="font-semibold">{{ $description }}</div><code class="text-xs text-slate-500">{{ $ability }}</code></td>
                        @foreach(['department_head','department_staff','department_viewer'] as $role)
                            @php $granted = in_array($ability, $departmentRoles[$role] ?? [], true); @endphp
                            <td class="{{ $granted ? 'font-semibold text-emerald-700' : 'text-slate-400' }}">{{ $granted ? 'Allowed' : 'Denied' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-ui.card>
<p class="mt-5 text-xs text-slate-500">Global teacher and student accounts retain their separate assignment and coursework routes. This page does not grant or modify roles; only authorized school administration workflows can assign department roles.</p>
@endsection
