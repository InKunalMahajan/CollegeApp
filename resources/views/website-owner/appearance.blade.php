@extends('layouts.app')
@section('title', 'Website Appearance')
@section('heading', 'Website Appearance')
@section('content')
<x-ui.page-heading title="Website Appearance & Typography"
 description="Configure the platform default. Each organization can use its own brand settings without changing the Website Owner workspace."/>
@if(session('success'))<x-ui.alert type="success" class="mb-5">{{ session('success') }}</x-ui.alert>@endif
@if($errors->any())<x-ui.alert type="error" class="mb-5">{{ $errors->first() }}</x-ui.alert>@endif
<div class="grid gap-6 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)]">
    <x-ui.card title="Appearance controls" description="Choose high-contrast colors, a clear font, and a comfortable reading size.">
        <form method="POST" action="{{ route('website-owner.appearance.update') }}" class="space-y-5">
            @csrf @method('PUT')
            <label for="platform-primary" class="block text-sm font-semibold">Primary brand color
                <input id="platform-primary" type="color" name="primary_color"
                    value="{{ old('primary_color', $appearance?->primary_color ?? '#4F46E5') }}"
                    class="mt-2 block h-12 w-24 cursor-pointer" required>
            </label>
            <p class="text-xs text-slate-500">Dark colors work best with white text. Very light colors are rejected to maintain readability.</p>
            <div class="flex flex-wrap gap-2" aria-label="Recommended accessible brand colors">
                @foreach(config('appearance.palette') as $label => $hex)
                    <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs">
                        <span class="inline-block size-4 rounded-full border border-slate-200" style="background-color: {{ $hex }}"></span>
                        <span>{{ ucfirst($label) }} {{ $hex }}</span>
                    </label>
                @endforeach
            </div>
            <label for="platform-font" class="block text-sm font-semibold">Font family
                <select id="platform-font" name="font_family" required class="mt-2 w-full">
                    @foreach(config('appearance.fonts') as $key => $label)
                        <option value="{{ $key }}" @selected(old('font_family', $appearance?->font_family ?? 'system') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label for="platform-size" class="block text-sm font-semibold">Base font size
                <select id="platform-size" name="font_size" required class="mt-2 w-full">
                    @foreach(config('appearance.sizes') as $size)
                        <option value="{{ $size }}" @selected((int) old('font_size', $appearance?->font_size ?? 16) === $size)>{{ $size }} px{{ $size === 16 ? ' (recommended)' : '' }}</option>
                    @endforeach
                </select>
            </label>
            <label for="platform-density" class="block text-sm font-semibold">Spacing pattern
                <select id="platform-density" name="density" required class="mt-2 w-full">
                    <option value="comfortable" @selected(old('density',$appearance?->density ?? 'comfortable') === 'comfortable')>Comfortable — more space</option>
                    <option value="compact" @selected(old('density',$appearance?->density ?? 'comfortable') === 'compact')>Compact — more content</option>
                </select>
            </label>
            <div class="border-t border-slate-200 pt-4">
                <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white">Save website appearance</button>
            </div>
        </form>
    </x-ui.card>
    <div class="space-y-5">
        <x-ui.card title="Design preview" description="Preview the current saved settings. New choices apply when you save.">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">SchoolApp platform</p>
            <h2 class="text-xl font-bold text-slate-900">Welcome to your management workspace</h2>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">Find users, manage organizations, and review system information with a consistent layout.</p>
            <button type="button" disabled class="mt-4 cursor-default rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">Example primary action</button>
        </x-ui.card>
        <x-ui.card title="Content and accessibility guidelines">
            <div class="space-y-3 text-sm text-slate-600">
                <p><strong>Page headings:</strong> say what the page does.</p>
                <p><strong>Descriptions:</strong> explain what can be changed and who has access.</p>
                <p><strong>Text:</strong> use short sentences and familiar words.</p>
                <p><strong>Controls:</strong> use visible labels, validation messages and keyboard focus.</p>
                <p><strong>Typography:</strong> use 16 px as the recommended starting size; 18 or 20 px may improve reading comfort.</p>
            </div>
        </x-ui.card>
    </div>
</div>
@endsection