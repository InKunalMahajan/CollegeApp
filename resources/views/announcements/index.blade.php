@extends('layouts.app')
@section('title','Announcements')
@section('heading','Announcements')
@section('content')
@if(auth()->user()->role === 'admin')
<section class="mb-7 rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><h2 class="mb-4 font-semibold">Create announcement draft</h2>
@if($errors->any())<div role="alert" class="mb-4 rounded-lg bg-rose-50 p-4 text-sm text-rose-700"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('announcements.store') }}" class="space-y-4">@csrf
<div><label for="title" class="mb-1 block text-sm font-semibold">Title</label><input id="title" name="title" required maxlength="200" class="w-full rounded-lg border border-slate-300 p-2.5"></div>
<div><label for="body" class="mb-1 block text-sm font-semibold">Message</label><textarea id="body" name="body" required rows="4" maxlength="10000" class="w-full rounded-lg border border-slate-300 p-2.5"></textarea></div>
<div><label for="audience" class="mb-1 block text-sm font-semibold">Audience</label><select id="audience" name="audience" class="w-full rounded-lg border border-slate-300 p-2.5">@foreach(['all'=>'All users','admin'=>'Admins','staff'=>'Staff','teacher'=>'Teachers','student'=>'Students'] as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
<button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Save draft</button></form>
</section>
<section class="mb-7 rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><h2 class="mb-4 font-semibold">Unpublished drafts</h2>@forelse($drafts as $draft)<div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 py-3"><div><p class="font-semibold">{{ $draft->title }}</p><p class="text-sm text-slate-500">Audience: {{ $draft->audience }}</p></div><form method="POST" action="{{ route('announcements.publish',$draft) }}">@csrf<button class="font-semibold text-emerald-700">Publish</button></form></div>@empty<p class="text-sm text-slate-500">No drafts.</p>@endforelse</section>
@endif
<section class="space-y-4"><h2 class="font-semibold">Published announcements</h2>@forelse($announcements as $announcement)<article class="schoolapp-panel rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><div class="flex flex-wrap justify-between gap-2"><h3 class="font-semibold">{{ $announcement->title }}</h3><span class="text-xs text-slate-500">{{ $announcement->published_at->format('d M Y H:i') }}</span></div><p class="mt-3 whitespace-pre-line text-sm">{{ $announcement->body }}</p><p class="mt-3 text-xs text-slate-500">By {{ $announcement->author->name }}</p></article>@empty<div class="schoolapp-panel rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-500">No announcements for your role.</div>@endforelse<div>{{ $announcements->links() }}</div></section>
@endsection
