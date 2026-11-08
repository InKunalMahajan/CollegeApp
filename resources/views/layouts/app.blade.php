<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · CollegeApp</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
<div class="min-h-screen lg:flex">
    <aside class="border-b border-slate-200 bg-white lg:sticky lg:top-0 lg:h-screen lg:w-64 lg:shrink-0 lg:border-r lg:border-b-0">
        <div class="flex items-center justify-between px-5 py-5 lg:px-6">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 font-bold tracking-tight text-slate-900">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-lg text-white">C</span>
                <span>CollegeApp <span class="block text-xs font-medium tracking-normal text-slate-500">Management system</span></span>
            </a>
            <span class="rounded-full bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 lg:hidden">CMS</span>
        </div>
        <nav class="flex gap-2 overflow-x-auto px-4 pb-4 lg:flex-col lg:overflow-visible" aria-label="Main navigation">
            <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif
               class="whitespace-nowrap rounded-lg px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">Dashboard</a>
            @if(auth()->user()->isStaff())
                <a href="{{ route('students.index') }}" @if(request()->routeIs('students.*')) aria-current="page" @endif
                   class="whitespace-nowrap rounded-lg px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('students.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">Students</a>
            @endif
            @if(auth()->user()->role === 'admin')
                <a href="{{ route('users.index') }}" @if(request()->routeIs('users.*')) aria-current="page" @endif
                   class="whitespace-nowrap rounded-lg px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('users.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">User accounts</a>
            @endif
        </nav>
        <div class="border-t border-slate-100 px-6 py-5 lg:absolute lg:right-0 lg:bottom-0 lg:left-0">
            <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
            <p class="mb-3 text-xs text-slate-500">{{ ucfirst(auth()->user()->role) }} account</p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm font-medium text-rose-700 hover:text-rose-900">Sign out</button>
            </form>
        </div>
    </aside>
    <div class="min-w-0 flex-1">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-5 py-5 sm:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">College Management System</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight">@yield('heading', 'Dashboard')</h1>
                </div>
                <span class="rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600">Academic workspace</span>
            </div>
        </header>
        <main id="main-content" class="mx-auto max-w-7xl px-5 py-8 sm:px-8">
            @if(session('success'))
                <div role="status" class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
