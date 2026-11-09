<div class="{{ $level ? 'ml-3 border-l-2 border-indigo-100 pl-4 sm:ml-6' : '' }}">
    <details class="rounded-xl border border-slate-200 bg-white p-3" @if($level === 0) open @endif>
        <summary class="cursor-pointer list-none rounded-lg px-2 py-1">
            <div class="flex flex-wrap items-center gap-3">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-700"><x-ui.icon name="user"/></span>
                <span class="min-w-0 flex-1"><strong class="block text-sm">{{ $node['name'] }}</strong><span class="block text-xs text-slate-500">{{ $node['title'] }} · {{ $node['number'] }}</span></span>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $node['reportees'] }} direct</span>
            </div>
            <p class="mt-2 pl-2 text-xs text-slate-500">{{ $node['department'] }} · {{ $node['location'] }}</p>
        </summary>
        @if($groups->has((string) $node['id']))
            <div class="mt-3 space-y-3">
                @foreach($groups->get((string) $node['id']) as $child)
                    @include('school-employees.node', ['node' => $child, 'groups' => $groups, 'level' => $level + 1])
                @endforeach
            </div>
        @endif
    </details>
</div>
