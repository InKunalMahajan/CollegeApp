<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>@yield('title', 'CollegeApp') — CollegeApp</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
<div class="min-h-screen lg:flex">
<aside class="border-b border-slate-200 bg-white p-6 lg:w-64 lg:border-r lg:border-b-0">
  <div class="mb-8 text-xl font-bold text-indigo-700">CollegeApp <span class="text-xs text-slate-500">CMS</span></div>
  <nav class="flex gap-3 lg:flex-col" aria-label="Main">
    <a class="rounded-lg px-4 py-3 text-sm {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}" href="{{ route('dashboard') }}">Dashboard</a>
    @if(auth()->user()->isStaff())<a class="rounded-lg px-4 py-3 text-sm {{ request()->routeIs('students.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}" href="{{ route('students.index') }}">Students</a>@endif
  </nav>
  <div class="mt-8 border-t border-slate-100 pt-4"><p class="mb-3 text-xs text-slate-500">{{ auth()->user()->name }} · {{ ucfirst(auth()->user()->role) }}</p>
  <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm font-medium text-rose-700 hover:underline">Sign out</button></form></div>
</aside>
<main class="min-w-0 flex-1"><header class="border-b border-slate-200 bg-white px-6 py-6"><h1 class="mx-auto max-w-6xl text-2xl font-semibold">@yield('heading', 'Dashboard')</h1></header>
<div class="mx-auto max-w-6xl px-6 py-8">
@if(session('success'))<p role="status" class="mb-6 rounded-lg bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</p>@endif
@yield('content')
</div></main>
</div></body></html>
