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
    @case('menu')<path d="M4 7h16M4 12h16M4 17h16"/>@break
    @case('layout-dashboard')<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>@break
    @case('megaphone')<path d="m3 11 16-6v14L3 13zM7 14l2 6h4l-3-7"/>@break
    @case('clipboard-list')<rect x="5" y="4" width="14" height="18" rx="2"/><path d="M9 4.5V3h6v1.5M9 11h6M9 15h6"/>@break
    @case('book-open')<path d="M12 7c-3-3-7-3-10-2v14c4-1 7-1 10 2 3-3 6-3 10-2V5c-3-1-7-1-10 2zM12 7v14"/>@break
    @case('graduation-cap')<path d="m2 9 10-5 10 5-10 5zM6 11v5c4 3 8 3 12 0v-5M22 9v7"/>@break
    @case('chart-column')<path d="M4 20V11h4v9M10 20V4h4v16M16 20v-6h4v6M2 20h20"/>@break
    @case('wallet')<rect x="3" y="6" width="18" height="14" rx="2"/><path d="M3 10h18M7 6V4h12"/>@break
    @case('library')<path d="M3 4h5v17H3zM10 4h5v17h-5zM17 6l4-1 3 15-4 1z"/>@break
    @case('link')<path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-2 2M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l2-2"/>@break
    @case('clipboard-check')<rect x="5" y="4" width="14" height="18" rx="2"/><path d="M9 4.5V3h6v1.5m-7 11 3 3 5-6"/>@break
    @case('calendar-check')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18m5 6 2 2 4-4"/>@break
    @case('calendar-days')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h2M14 14h2M8 18h2"/>@break
    @case('user-cog')<circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 9-5M19 16l1-1m-1 1 1 1m-1-1h-2m2 0h2"/>@break
    @case('layout-grid')<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>@break
    @case('settings')<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2"/>@break
    @case('log-out')<path d="M10 17l5-5-5-5M15 12H3M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"/>@break
    @case('plus')<path d="M12 5v14M5 12h14"/>@break
    @case('alert-circle')<circle cx="12" cy="12" r="10"/><path d="M12 7v6M12 17h.01"/>@break
    @case('x')<path d="M18 6 6 18M6 6l12 12"/>@break
    @case('chevron-down')<path d="m6 9 6 6 6-6"/>@break
    @case('chevron-right')<path d="m9 18 6-6-6-6"/>@break
    @case('panel-left')<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/>@break
    @case('shield-check')<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>@break
    @default<circle cx="12" cy="12" r="9"/>@break
@endswitch
</svg>
