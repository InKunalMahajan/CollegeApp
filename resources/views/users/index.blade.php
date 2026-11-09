@extends('layouts.app')
@section('title', 'User accounts')
@section('heading', 'User accounts')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <p class="text-sm text-slate-600">Manage platform accounts, change account types and disable access. Organization and campus administrator rights are assigned separately.</p>
        <a href="{{ route('website-owner.users.index') }}" class="mt-2 inline-block text-sm font-semibold text-indigo-700 hover:underline">Manage TEACH organization administrators →</a>
    </div>
    <a href="{{ route('users.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">+ Create account</a>
</div>
@if(session('success'))
<div role="status" class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>
@endif
@if($errors->any())
<div role="alert" class="mb-5 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
    <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif
<div class="schoolapp-table-wrap overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
<table class="w-full text-left text-sm">
    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
        <tr>
            <th scope="col" class="px-5 py-4">Name</th>
            <th scope="col" class="px-5 py-4">Email</th>
            <th scope="col" class="px-5 py-4">Account type</th>
            <th scope="col" class="px-5 py-4">Status</th>
            <th scope="col" class="px-5 py-4">Actions</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
    @forelse($users as $user)
    @php $isOwner = in_array($user->id, $ownerIds, true); @endphp
    <tr>
        <td class="px-5 py-4 font-medium">{{ $user->name }}</td>
        <td class="px-5 py-4 text-slate-600">{{ $user->email }}</td>
        <td class="px-5 py-4">
            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold capitalize text-indigo-700">{{ $user->role }}</span>
            @if($isOwner)<span class="ml-1 text-xs text-slate-500">Website Owner</span>@endif
        </td>
        <td class="px-5 py-4">
            @if($user->disabled_at)
            <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">Disabled</span>
            @else
            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Active</span>
            @endif
        </td>
        <td class="px-5 py-4">
            @if($isOwner)
            <span class="text-xs text-slate-500">Protected account</span>
            @else
            <a href="#edit-user-{{ $user->id }}" class="rounded-lg border border-indigo-200 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Edit role / account</a>
            @endif
        </td>
    </tr>
    @if(!$isOwner)
    <tr id="edit-user-{{ $user->id }}" class="bg-slate-50/70">
        <td colspan="5" class="px-5 py-3">
            <details>
                <summary class="cursor-pointer text-xs font-semibold text-slate-600">Edit {{ $user->name }} — name, email, role, password or status</summary>
                <form method="POST" action="{{ route('website-owner.users.update',$user) }}" class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @csrf @method('PUT')
                    <label class="text-xs font-semibold">Full name
                        <input name="name" value="{{ $user->name }}" maxlength="255" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </label>
                    <label class="text-xs font-semibold">Email address
                        <input type="email" name="email" value="{{ $user->email }}" maxlength="255" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </label>
                    <label class="text-xs font-semibold">Account type
                        <select name="role" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @if($user->role === 'admin')
                            <option value="" selected disabled>Legacy Admin — select a new type</option>
                            @endif
                            @foreach(['staff'=>'Staff','teacher'=>'Teacher','student'=>'Student'] as $value=>$label)
                            <option value="{{ $value }}" @selected($user->role === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-xs font-semibold">New password (optional)
                        <input type="password" name="password" minlength="12" autocomplete="new-password" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </label>
                    <label class="text-xs font-semibold">Confirm new password
                        <input type="password" name="password_confirmation" autocomplete="new-password" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </label>
                    <div class="flex items-end"><button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-indigo-700">Save changes</button></div>
                </form>
                <div class="mt-3">
                    @if($user->disabled_at)
                    <form method="POST" action="{{ route('website-owner.users.restore',$user) }}">
                        @csrf<button type="submit" class="rounded-lg border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700">Enable account</button>
                    </form>
                    @else
                    <form method="POST" action="{{ route('website-owner.users.destroy',$user) }}" onsubmit="return confirm('Disable this user? The account and historical records will be kept.');">
                        @csrf @method('DELETE')
                        <button type="submit" class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700">Disable account</button>
                    </form>
                    @endif
                </div>
            </details>
        </td>
    </tr>
    @endif
    @empty
    <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">No user accounts found.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="mt-5">{{ $users->links() }}</div>
<p class="mt-4 text-xs text-slate-500">Changing Staff / Teacher / Student does not grant or revoke TEACH organization or campus administration. Use the scoped membership pages for administrator access. Account deletion is implemented as reversible disable to protect historical records.</p>
@endsection