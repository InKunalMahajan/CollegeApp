@php
    $person = auth()->user();
    $legacy = config('school_security.legacy_global_routes_enabled', false);
    $role = $person->role;
    $nav = [];
    $nav[] = ['title' => 'Workspace', 'icon' => 'layout-dashboard', 'items' => [
        ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'url' => route('dashboard'), 'active' => request()->routeIs('dashboard')],
        ['label' => 'My Schools', 'icon' => 'school', 'url' => route('my-schools.index'), 'active' => request()->routeIs('my-schools.*')],
    ]];

    $schoolContext = request()->route('school');
    if ($schoolContext instanceof \App\Models\School) {
        $contextRole = \App\Models\SchoolMembership::query()
            ->where('school_id', $schoolContext->id)
            ->where('user_id', $person->id)
            ->value('role');
        if (in_array($contextRole, ['school_admin', 'department_member'], true)) {
            $assignedDepartments = \App\Models\SchoolDepartment::query()
                ->where('school_id', $schoolContext->id)
                ->when($contextRole !== 'school_admin', fn ($query) => $query->whereHas(
                    'memberships',
                    fn ($members) => $members->where('school_id', $schoolContext->id)
                        ->where('user_id', $person->id)
                        ->whereIn('role', \App\Models\SchoolDepartmentMembership::ROLES)
                ))->orderBy('name')->get();
            $departmentLinks = [[
                'label' => 'All departments',
                'icon' => 'layout-grid',
                'url' => route('school-departments.index', $schoolContext),
                'active' => request()->routeIs('school-departments.index'),
            ]];
            foreach ($assignedDepartments as $assignedDepartment) {
                $departmentLinks[] = [
                    'label' => $assignedDepartment->name,
                    'icon' => 'school',
                    'url' => route('school-departments.show', [$schoolContext, $assignedDepartment]),
                    'active' => request()->routeIs('school-departments.show')
                        && (string) (request()->route('department')?->id) === (string) $assignedDepartment->id,
                ];
            }
            $nav[] = ['title' => 'Departments', 'icon' => 'layout-grid', 'items' => $departmentLinks];
        }
        $membership = \App\Models\SchoolMembership::query()
            ->where('school_id', $schoolContext->id)->where('user_id', $person->id)
            ->whereIn('role', ['school_admin', 'auditor'])->first();
        if ($membership) {
            $schoolItems = [
                ['label' => 'School dashboard', 'icon' => 'layout-dashboard', 'url' => route('school-dashboard.index', $schoolContext), 'active' => request()->routeIs('school-dashboard.*')],
                ['label' => 'School portal', 'icon' => 'school', 'url' => route('school-portal.index', $schoolContext), 'active' => request()->routeIs('school-portal.*')],
                ['label' => 'Security report', 'icon' => 'shield-check', 'url' => route('school-security.report', $schoolContext), 'active' => request()->routeIs('school-security.*')],
            ];
            if ($membership->role === 'school_admin') {
                foreach ([
                    ['Students', 'users', 'school-students.index', 'school-students.*'],
                    ['Academics', 'book-open', 'school-academics.index', 'school-academics.*'],
                    ['Finance', 'wallet', 'school-finance.fees', 'school-finance.fees'],
                    ['Library', 'library', 'school-finance.library', 'school-finance.library'],
                    ['Student accounts', 'user-cog', 'school-student-accounts.index', 'school-student-accounts.*'],
                    ['School administration', 'settings', 'school-admin.index', 'school-admin.*'],
                ] as [$label, $icon, $name, $pattern]) {
                    $schoolItems[] = ['label' => $label, 'icon' => $icon, 'url' => route($name, $schoolContext), 'active' => request()->routeIs($pattern)];
                }
            }
            $nav[] = ['title' => $schoolContext->name, 'icon' => 'school', 'items' => $schoolItems];
        }
    }

    $learning = [];
    if ($legacy) {
        $learning[] = ['label' => 'Announcements', 'icon' => 'megaphone', 'url' => route('announcements.index'), 'active' => request()->routeIs('announcements.*')];
    }
    if ($role === 'student') {
        $learning[] = ['label' => 'My assignments', 'icon' => 'clipboard-list', 'url' => route('assignments.student'), 'active' => request()->routeIs('assignments.student')];
    }
    if ($role === 'teacher') {
        foreach ([
            ['My assignments', 'clipboard-list', 'assignments.teacher'],
            ['My lesson progress', 'book-open', 'curriculum.mine'],
            ['My timetable', 'calendar-days', 'timetables.mine'],
            ['My teaching', 'graduation-cap', 'my-teaching'],
        ] as [$label, $icon, $name]) {
            $learning[] = ['label' => $label, 'icon' => $icon, 'url' => route($name), 'active' => request()->routeIs($name)];
        }
    }
    if ($learning) {
        $nav[] = ['title' => 'Learning & teaching', 'icon' => 'book-open', 'items' => $learning];
    }

    if ($person->isStaff() && $legacy) {
        $nav[] = ['title' => 'Student records', 'icon' => 'users', 'items' => [
            ['label' => 'Students', 'icon' => 'users', 'url' => route('students.index'), 'active' => request()->routeIs('students.*')],
        ]];
    }

    if ($role === 'admin' && $legacy) {
        $administration = [];
        foreach ([
            ['Reports & Analytics', 'chart-column', 'reports.index', 'reports.*'],
            ['Fees', 'wallet', 'fees.index', 'fees.*'],
            ['Library', 'library', 'library.index', 'library.*'],
            ['Student login links', 'link', 'student-links.index', 'student-links.*'],
            ['Examinations', 'clipboard-check', 'exams.index', 'exams.*'],
            ['Curriculum', 'book-open', 'curriculum.index', 'curriculum.index'],
            ['Attendance', 'calendar-check', 'attendance.index', 'attendance.*'],
            ['Timetables', 'calendar-days', 'timetables.index', 'timetables.index'],
            ['Teachers', 'users', 'teachers.index', 'teachers.*'],
            ['Teacher assignments', 'graduation-cap', 'teacher-assignments.index', 'teacher-assignments.*'],
        ] as [$label, $icon, $name, $pattern]) {
            $administration[] = ['label' => $label, 'icon' => $icon, 'url' => route($name), 'active' => request()->routeIs($pattern)];
        }
        $nav[] = ['title' => 'Administration', 'icon' => 'settings', 'items' => $administration];

        $setup = [];
        foreach (['schools' => 'Schools', 'campuses' => 'Campuses', 'academic-years' => 'Academic years', 'subjects' => 'Subjects', 'class-sections' => 'Class sections'] as $key => $label) {
            $setup[] = ['label' => $label, 'icon' => 'settings', 'url' => route('setup.index', $key), 'active' => request()->routeIs('setup.*') && request()->route('module') === $key];
        }
        $setup[] = ['label' => 'User accounts', 'icon' => 'user-cog', 'url' => route('users.index'), 'active' => request()->routeIs('users.*')];
        $setup[] = ['label' => 'All modules', 'icon' => 'layout-grid', 'url' => route('modules.index'), 'active' => request()->routeIs('modules.*')];
        $nav[] = ['title' => 'Institution setup', 'icon' => 'settings', 'items' => $setup];
    }
@endphp
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
<a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[70] focus:rounded-xl focus:bg-white focus:px-4 focus:py-3 focus:shadow-xl">Skip to main content</a>
<div id="schoolapp-shell" class="schoolapp-shell min-h-screen" data-shell>
    <div class="schoolapp-backdrop hidden" data-nav-backdrop aria-hidden="true"></div>

    <aside id="schoolapp-navigation" class="schoolapp-sidebar" data-sidebar aria-label="Application sidebar">
        <div class="flex h-[76px] shrink-0 items-center justify-between border-b border-slate-200/70 px-5">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3 rounded-xl focus-visible:outline-offset-4" title="SchoolApp home">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm shadow-indigo-200"><x-ui.icon name="school" size="size-5"/></span>
                <span class="schoolapp-sidebar-label min-w-0"><span class="block text-base font-extrabold tracking-tight">SchoolApp</span><span class="block text-[11px] font-medium tracking-wide text-slate-500">Management workspace</span></span>
            </a>
            <button type="button" data-nav-toggle aria-controls="schoolapp-navigation" aria-expanded="false" aria-label="Close navigation" class="schoolapp-mobile-close inline-flex size-10 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 lg:hidden"><x-ui.icon name="x" size="size-5"/></button>
        </div>
        <div class="schoolapp-sidebar-scroll flex-1 overflow-y-auto px-3 py-5">
            <div class="schoolapp-sidebar-label mb-5 px-2">
                <label for="navigation-search" class="sr-only">Filter navigation menu</label>
                <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-500 focus-within:border-indigo-400 focus-within:bg-white">
                    <x-ui.icon name="search"/>
                    <input id="navigation-search" data-nav-search type="search" placeholder="Find menu..." autocomplete="off" class="min-w-0 flex-1 bg-transparent text-sm text-slate-800 outline-none placeholder:text-slate-400">
                    <span class="rounded border border-slate-200 px-1.5 py-0.5 text-[10px] font-semibold text-slate-400">/</span>
                </div>
            </div>
            <nav aria-label="Main navigation" class="space-y-2" data-nav-list>
                @foreach($nav as $section)
                    @php $sectionActive = collect($section['items'])->contains(fn ($item) => $item['active']); @endphp
                    <details class="schoolapp-nav-group group rounded-xl" data-nav-group @if($sectionActive || $loop->first) open @endif>
                        <summary class="schoolapp-group-heading flex cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-bold uppercase tracking-wider text-slate-500 hover:bg-slate-100 hover:text-slate-800" title="{{ $section['title'] }}">
                            <x-ui.icon :name="$section['icon']" size="size-4"/>
                            <span class="schoolapp-sidebar-label min-w-0 flex-1 truncate">{{ $section['title'] }}</span>
                            <x-ui.icon name="chevron-down" class="schoolapp-sidebar-label schoolapp-group-chevron transition-transform group-open:rotate-180" size="size-4"/>
                        </summary>
                        <div class="schoolapp-nav-items space-y-0.5 pb-2 pt-1">
                            @foreach($section['items'] as $item)
                                <a href="{{ $item['url'] }}" data-nav-item data-nav-label="{{ mb_strtolower($item['label']) }}" title="{{ $item['label'] }}"
                                   @if($item['active']) aria-current="page" @endif
                                   class="schoolapp-nav-link group/item flex min-h-10 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors {{ $item['active'] ? 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-100' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                    <x-ui.icon :name="$item['icon']" size="size-4"/>
                                    <span class="schoolapp-sidebar-label truncate">{{ $item['label'] }}</span>
                                    @if($item['active'])<span class="schoolapp-sidebar-label ml-auto size-1.5 shrink-0 rounded-full bg-indigo-600" aria-hidden="true"></span>@endif
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endforeach
                <p data-nav-empty class="hidden px-3 py-5 text-center text-sm text-slate-500">No matching menu items.</p>
            </nav>
        </div>
        <div class="shrink-0 border-t border-slate-200 bg-white p-3">
            <div class="flex items-center gap-3 rounded-xl bg-slate-50 px-3 py-3">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">{{ mb_strtoupper(mb_substr($person->name, 0, 1)) }}</span>
                <div class="schoolapp-sidebar-label min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ $person->name }}</p><p class="truncate text-xs capitalize text-slate-500">{{ str_replace('_',' ',$role) }}</p></div>
            </div>
        </div>
    </aside>

    <div class="schoolapp-main flex min-h-screen min-w-0 flex-col">
        <header class="schoolapp-header sticky top-0 z-30 border-b border-slate-200/80 bg-white/95 backdrop-blur-xl">
            <div class="flex h-[76px] items-center gap-3 px-4 sm:px-6 xl:px-8">
                <button type="button" id="mobile-nav-toggle" data-nav-toggle aria-controls="schoolapp-navigation" aria-expanded="false" aria-label="Open navigation" class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 lg:hidden"><x-ui.icon name="menu" size="size-5"/></button>
                <button type="button" data-sidebar-collapse aria-label="Collapse sidebar" aria-expanded="true" class="hidden size-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 lg:inline-flex" title="Toggle sidebar"><x-ui.icon name="panel-left" size="size-5"/></button>
                <div class="min-w-0 flex-1">
                    <div class="hidden items-center gap-1 text-xs text-slate-500 sm:flex"><a href="{{ route('dashboard') }}" class="hover:text-indigo-700">Workspace</a><x-ui.icon name="chevron-right" size="size-3"/><span class="truncate">@yield('heading', 'Dashboard')</span></div>
                    <h1 class="truncate text-base font-bold tracking-tight text-slate-900 sm:mt-0.5 sm:text-xl">@yield('heading', 'Dashboard')</h1>
                </div>
                <span class="hidden rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 xl:inline-flex">School management</span>
                <div class="h-6 w-px bg-slate-200 max-sm:hidden" aria-hidden="true"></div>
                <details class="schoolapp-profile relative" data-profile-menu>
                    <summary class="flex min-h-10 cursor-pointer list-none items-center gap-2 rounded-xl px-2 py-1.5 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-indigo-600" aria-label="Account menu">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">{{ mb_strtoupper(mb_substr($person->name, 0, 1)) }}</span>
                        <span class="hidden max-w-32 truncate text-sm font-semibold text-slate-800 md:block">{{ $person->name }}</span>
                        <x-ui.icon name="chevron-down" size="size-4" class="hidden text-slate-400 md:inline-block"/>
                    </summary>
                    <div class="absolute right-0 top-full z-50 mt-2 w-64 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/10">
                        <div class="border-b border-slate-100 px-3 py-3"><p class="truncate text-sm font-semibold">{{ $person->name }}</p><p class="truncate text-xs text-slate-500">{{ $person->email }}</p><p class="mt-1 text-xs capitalize text-indigo-700">{{ str_replace('_',' ',$role) }} account</p></div>
                        <a href="{{ route('my-schools.index') }}" class="mt-1 flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm text-slate-700 hover:bg-slate-50"><x-ui.icon name="school"/> My Schools</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-left text-sm font-semibold text-rose-700 hover:bg-rose-50"><x-ui.icon name="log-out"/> Sign out</button>
                        </form>
                    </div>
                </details>
            </div>
        </header>

        <main id="main-content" tabindex="-1" class="w-full flex-1 px-4 py-6 sm:px-6 sm:py-8 xl:px-8">
            <div class="schoolapp-page mx-auto w-full max-w-[1480px]">
                @if(session('success'))<x-ui.alert type="success" class="mb-6">{{ session('success') }}</x-ui.alert>@endif
                @if(session('error'))<x-ui.alert type="error" class="mb-6">{{ session('error') }}</x-ui.alert>@endif
                @yield('content')
            </div>
        </main>
        <footer class="border-t border-slate-200/80 px-4 py-4 text-xs text-slate-500 sm:px-6 xl:px-8">
            <div class="mx-auto flex max-w-[1480px] flex-wrap items-center justify-between gap-2"><span>SchoolApp · Learning management workspace</span><span>Secure school access</span></div>
        </footer>
    </div>
</div>
</body>
</html>
