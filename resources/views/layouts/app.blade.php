<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · SchoolApp</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
<a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-3 focus:shadow-lg">Skip to main content</a>
<div class="min-h-screen lg:flex">
    <aside class="border-b border-slate-200 bg-white lg:sticky lg:top-0 lg:h-screen lg:w-64 lg:shrink-0 lg:overflow-y-auto lg:border-r lg:border-b-0">
        <div class="flex items-center justify-between px-5 py-5 lg:px-6">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 font-bold tracking-tight text-slate-900">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-lg text-white">S</span>
                <span>SchoolApp <span class="block text-xs font-medium tracking-normal text-slate-500">Management system</span></span>
            </a>
            <button type="button" id="mobile-nav-toggle" data-nav-toggle aria-controls="schoolapp-navigation" aria-expanded="false" aria-label="Open navigation" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 lg:hidden">
                <x-ui.icon name="menu" size="size-5"/>
            </button>
        </div>
        <div id="schoolapp-navigation" class="hidden lg:block">
        <nav class="flex flex-col gap-1 px-4 pb-4" aria-label="Main navigation">
            <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif
               class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"><x-ui.icon name="layout-dashboard"/> Dashboard</a>
            <a href="{{ route('my-schools.index') }}" @if(request()->routeIs('my-schools.*') || request()->routeIs('school-admin.*')) aria-current="page" @endif
               class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('my-schools.*') || request()->routeIs('school-admin.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"><x-ui.icon name="school"/> My Schools</a>
            @if(config('school_security.legacy_global_routes_enabled', false))
            <a href="{{ route('announcements.index') }}" @if(request()->routeIs('announcements.*')) aria-current="page" @endif
               class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('announcements.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"><x-ui.icon name="megaphone"/> Announcements</a>
            @endif
            @if(auth()->user()->role === 'student')
                <a href="{{ route('assignments.student') }}" class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100"><x-ui.icon name="clipboard-list"/> My assignments</a>
            @endif
            @if(auth()->user()->isStaff() && config('school_security.legacy_global_routes_enabled', false))
                <a href="{{ route('students.index') }}" @if(request()->routeIs('students.*')) aria-current="page" @endif
                   class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('students.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"><x-ui.icon name="users"/> Students</a>
            @endif
            @if(auth()->user()->role === 'teacher')
                <a href="{{ route('assignments.teacher') }}" class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100"><x-ui.icon name="clipboard-list"/> My assignments</a>
                <a href="{{ route('curriculum.mine') }}" @if(request()->routeIs('curriculum.mine')) aria-current="page" @endif
                   class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('curriculum.mine') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"><x-ui.icon name="book-open"/> My lesson progress</a>
                <a href="{{ route('timetables.mine') }}" @if(request()->routeIs('timetables.mine')) aria-current="page" @endif
                   class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('timetables.mine') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"><x-ui.icon name="calendar"/> My timetable</a>
                <a href="{{ route('my-teaching') }}" @if(request()->routeIs('my-teaching')) aria-current="page" @endif
                   class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('my-teaching') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"><x-ui.icon name="graduation-cap"/> My teaching</a>
            @endif
            @if(auth()->user()->role === 'admin' && config('school_security.legacy_global_routes_enabled', false))
                <a href="{{ route('reports.index') }}" @if(request()->routeIs('reports.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('reports.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"><x-ui.icon name="chart-column"/> Reports &amp; Analytics</a>
                <a href="{{ route('fees.index') }}" @if(request()->routeIs('fees.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('fees.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"><x-ui.icon name="wallet"/> Fees</a>
                <a href="{{ route('library.index') }}" @if(request()->routeIs('library.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('library.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"><x-ui.icon name="library"/> Library</a>
                <a href="{{ route('student-links.index') }}" class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-100"><x-ui.icon name="link"/> Student login links</a>
                <a href="{{ route('exams.index') }}" @if(request()->routeIs('exams.*')) aria-current="page" @endif
                   class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('exams.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"><x-ui.icon name="clipboard-check"/> Examinations</a>
                <a href="{{ route('curriculum.index') }}" @if(request()->routeIs('curriculum.index') || request()->routeIs('curriculum.units.store') || request()->routeIs('curriculum.lessons.store')) aria-current="page" @endif
                   class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('curriculum.index') || request()->routeIs('curriculum.units.store') || request()->routeIs('curriculum.lessons.store') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"><x-ui.icon name="book-open"/> Curriculum</a>
                <a href="{{ route('attendance.index') }}" @if(request()->routeIs('attendance.*')) aria-current="page" @endif
                   class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('attendance.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"><x-ui.icon name="calendar-check"/> Attendance</a>
                <a href="{{ route('timetables.index') }}" @if(request()->routeIs('timetables.index') || request()->routeIs('timetables.store') || request()->routeIs('timetables.destroy')) aria-current="page" @endif
                   class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('timetables.index') || request()->routeIs('timetables.store') || request()->routeIs('timetables.destroy') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"><x-ui.icon name="calendar-days"/> Timetables</a>
                <a href="{{ route('teachers.index') }}" @if(request()->routeIs('teachers.*')) aria-current="page" @endif
                   class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('teachers.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"><x-ui.icon name="users"/> Teachers</a>
                <a href="{{ route('teacher-assignments.index') }}" @if(request()->routeIs('teacher-assignments.*')) aria-current="page" @endif
                   class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('teacher-assignments.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"><x-ui.icon name="graduation-cap"/> Teacher assignments</a>
                <div class="px-4 pt-4 pb-1 text-xs font-semibold uppercase tracking-wide text-slate-400 lg:block">Institution setup</div>
                @foreach (['schools' => 'Schools', 'campuses' => 'Campuses', 'academic-years' => 'Academic years', 'subjects' => 'Subjects', 'class-sections' => 'Class sections'] as $key => $label)
                    <a href="{{ route('setup.index', $key) }}" @if(request()->routeIs('setup.*') && request()->route('module') === $key) aria-current="page" @endif
                       class="flex items-center gap-3 rounded-lg px-4 py-2.5 text-sm font-semibold transition {{ request()->routeIs('setup.*') && request()->route('module') === $key ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"><x-ui.icon name="settings"/> {{ $label }}</a>
                @endforeach
                <a href="{{ route('users.index') }}" @if(request()->routeIs('users.*')) aria-current="page" @endif
                   class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('users.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"><x-ui.icon name="user-cog"/> User accounts</a>
                <a href="{{ route('modules.index') }}" @if(request()->routeIs('modules.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold transition {{ request()->routeIs('modules.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"><x-ui.icon name="layout-grid"/> All modules</a>
            @endif
        </nav>
        <div class="border-t border-slate-100 px-6 py-5 ">
            <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
            <p class="mb-3 text-xs text-slate-500">{{ ucfirst(auth()->user()->role) }} account</p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg px-2 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50 hover:text-rose-900"><x-ui.icon name="log-out"/> Sign out</button>
            </form>
        </div>
        </div>
    </aside>
    <div class="min-w-0 flex-1">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-5 py-5 sm:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">School Management System</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight">@yield('heading', 'Dashboard')</h1>
                </div>
                <span class="rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600">Academic workspace</span>
            </div>
        </header>
        <main id="main-content" class="mx-auto max-w-7xl px-5 py-8 sm:px-8">
            @if(session('success'))
                <x-ui.alert type="success" class="mb-6">{{ session('success') }}</x-ui.alert>
            @endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
