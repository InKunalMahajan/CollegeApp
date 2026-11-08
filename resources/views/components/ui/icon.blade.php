@props(['name' => 'circle', 'size' => 'size-4'])
<svg {{ $attributes->class([$size, 'inline-block shrink-0 align-middle']) }}
     xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
     fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
     stroke-linejoin="round" aria-hidden="true" focusable="false">
@switch($name)
    @case('users')<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>@break
    @case('user')<path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="8" r="4"/>@break
    @case('search')<circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>@break
    @case('filter')<path d="M4 7h16M7 12h10M10 17h4"/>@break
    @case('edit')<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L10 16l-4 1 1-4z"/>@break
    @case('eye')<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>@break
    @case('arrow-left')<path d="m15 18-6-6 6-6"/><path d="M20 12H9"/>@break
    @case('calendar')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>@break
    @case('school')<path d="m3 10 9-7 9 7M5 9v12h14V9M9 21v-7h6v7"/>@break
    @case('save')<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/>@break
    @case('check-circle')<circle cx="12" cy="12" r="10"/><path d="m8 12 3 3 5-6"/>@break
    @case('refresh')<path d="M20 11a8 8 0 0 0-14-5L4 8m0-4v4h4M4 13a8 8 0 0 0 14 5l2-2m0 4v-4h-4"/>@break
    @default<circle cx="12" cy="12" r="9"/>@break
@endswitch
</svg>
