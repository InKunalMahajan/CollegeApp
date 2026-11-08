@extends('layouts.app')
@section('title', 'Management modules')
@section('heading', 'Management modules')
@section('content')
<section>
    <div class="mb-6 max-w-3xl">
        <h2 class="text-xl font-semibold">CollegeApp module roadmap</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">All 16 planned modules are listed below. A status of <strong>Partial</strong> means some functionality exists; it does not mean the module is complete. Planned modules are not interactive yet.</p>
    </div>
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full min-w-[680px] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr><th scope="col" class="px-5 py-4">No.</th><th scope="col" class="px-5 py-4">Module</th><th scope="col" class="px-5 py-4">Main features</th><th scope="col" class="px-5 py-4">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($modules as $module)
                <tr>
                    <td class="px-5 py-4 text-slate-500">{{ $loop->iteration }}</td>
                    <td class="px-5 py-4 font-semibold text-slate-900">{{ $module['name'] }}</td>
                    <td class="px-5 py-4 text-slate-600">{{ $module['features'] }}</td>
                    <td class="px-5 py-4">
                        @if($module['status'] === 'partial')
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">Partial</span>
                        @else
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Planned</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="mt-5 text-xs text-slate-500">Module access and permissions must be implemented and tested individually before release.</p>
</section>
@endsection
