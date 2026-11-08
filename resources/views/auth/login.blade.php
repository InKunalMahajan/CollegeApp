<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in — SchoolApp</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="flex min-h-screen items-center justify-center bg-slate-50 px-4 text-slate-900">
<main class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
<div class="mb-6 text-center"><h1 class="text-2xl font-bold text-indigo-700">SchoolApp</h1><p class="mt-2 text-sm text-slate-500">Sign in to your school account</p></div>
@if($errors->any())<x-ui.alert type="error" class="mb-5">{{ $errors->first() }}</x-ui.alert>@endif
<form method="POST" action="{{ route('login.store') }}">@csrf
<div class="mb-4"><label for="email" class="mb-2 block text-sm font-medium">Email address</label><input id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required autofocus class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"></div>
<div class="mb-6"><label for="password" class="mb-2 block text-sm font-medium">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"></div>
<x-ui.button type="submit" class="w-full">Sign in</x-ui.button></form>
<p class="mt-5 text-center text-xs text-slate-500">Contact your school administrator for account access.</p>
</main></body></html>
