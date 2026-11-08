@extends('layouts.app')
@section('title', 'Create account')
@section('heading', 'Create user account')
@section('content')
<form method="POST" action="{{ route('users.store') }}" class="max-w-2xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm" autocomplete="off">
    @csrf
    <p class="mb-6 text-sm text-slate-600">Only administrators can create accounts. Share credentials privately with the intended user.</p>
    @if($errors->any())
        <div role="alert" class="mb-5 rounded-lg bg-rose-50 p-4 text-sm text-rose-700">
            <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <div class="space-y-5">
        <div><label for="name" class="mb-2 block text-sm font-semibold">Full name</label>
            <input id="name" name="name" required maxlength="255" value="{{ old('name') }}" autocomplete="name" class="w-full rounded-lg border border-slate-300 px-4 py-2.5"></div>
        <div><label for="email" class="mb-2 block text-sm font-semibold">Email address</label>
            <input id="email" type="email" name="email" required maxlength="255" value="{{ old('email') }}" autocomplete="off" class="w-full rounded-lg border border-slate-300 px-4 py-2.5"></div>
        <div><label for="role" class="mb-2 block text-sm font-semibold">Account role</label>
            <select id="role" name="role" required class="w-full rounded-lg border border-slate-300 px-4 py-2.5">
                @foreach (['student' => 'Student', 'teacher' => 'Teacher', 'staff' => 'Staff', 'admin' => 'Administrator'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('role', 'student') === $value)>{{ $label }}</option>
                @endforeach
            </select></div>
        <div><label for="password" class="mb-2 block text-sm font-semibold">Temporary password (minimum 12 characters)</label>
            <input id="password" type="password" name="password" required minlength="12" autocomplete="new-password" class="w-full rounded-lg border border-slate-300 px-4 py-2.5"></div>
        <div><label for="password_confirmation" class="mb-2 block text-sm font-semibold">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required minlength="12" autocomplete="new-password" class="w-full rounded-lg border border-slate-300 px-4 py-2.5"></div>
    </div>
    <div class="mt-8 flex items-center gap-4">
        <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Create account</button>
        <a href="{{ route('users.index') }}" class="text-sm font-medium text-slate-600 hover:underline">Cancel</a>
    </div>
</form>
@endsection
