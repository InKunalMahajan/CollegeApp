@extends('layouts.app')
@section('title', 'User accounts')
@section('heading', 'User accounts')
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <p class="text-sm text-slate-600">Manage account access for administrators, staff, teachers and students.</p>
    <a href="{{ route('users.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">+ Create account</a>
</div>
<div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
            <tr><th scope="col" class="px-5 py-4">Name</th><th scope="col" class="px-5 py-4">Email</th><th scope="col" class="px-5 py-4">Role</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($users as $user)
            <tr>
                <td class="px-5 py-4 font-medium">{{ $user->name }}</td>
                <td class="px-5 py-4 text-slate-600">{{ $user->email }}</td>
                <td class="px-5 py-4"><span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold capitalize text-indigo-700">{{ $user->role }}</span></td>
            </tr>
            @empty
            <tr><td colspan="3" class="px-5 py-10 text-center text-slate-500">No user accounts found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $users->links() }}</div>
@endsection
