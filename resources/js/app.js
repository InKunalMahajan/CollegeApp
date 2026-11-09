import './bootstrap';

const shell = document.querySelector('[data-shell]');
if (shell) {
    const sidebar = shell.querySelector('[data-sidebar]');
    const backdrop = shell.querySelector('[data-nav-backdrop]');
    const toggles = [...shell.querySelectorAll('[data-nav-toggle]')];
    const collapse = shell.querySelector('[data-sidebar-collapse]');
    const search = shell.querySelector('[data-nav-search]');
    const navGroups = [...shell.querySelectorAll('[data-nav-group]')];
    const profile = shell.querySelector('[data-profile-menu]');
    const desktop = window.matchMedia('(min-width: 1024px)');

    const setMobileOpen = (open) => {
        shell.classList.toggle('schoolapp-mobile-open', open);
        backdrop?.classList.toggle('hidden', !open);
        document.body.classList.toggle('overflow-hidden', open);
        toggles.forEach((toggle) => {
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
        });
        if (open) sidebar?.querySelector('[data-nav-search]')?.focus();
    };

    toggles.forEach((toggle) => toggle.addEventListener('click', () => {
        setMobileOpen(!shell.classList.contains('schoolapp-mobile-open'));
    }));
    backdrop?.addEventListener('click', () => setMobileOpen(false));
    sidebar?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => { if (!desktop.matches) setMobileOpen(false); });
    });

    const setCollapsed = (collapsed) => {
        shell.classList.toggle('schoolapp-collapsed', collapsed);
        collapse?.setAttribute('aria-expanded', String(!collapsed));
        collapse?.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        if (collapsed) navGroups.forEach((group) => { group.open = true; });
        else navGroups.forEach((group) => {
            group.open = !!group.querySelector('[aria-current="page"]') || group === navGroups[0];
        });
    };
    try {
        setCollapsed(window.localStorage.getItem('schoolapp-sidebar-collapsed') === 'true');
    } catch {
        setCollapsed(false);
    }
    collapse?.addEventListener('click', () => {
        const next = !shell.classList.contains('schoolapp-collapsed');
        setCollapsed(next);
        try { window.localStorage.setItem('schoolapp-sidebar-collapsed', String(next)); } catch {}
    });

    const filterMenu = () => {
        const term = (search?.value ?? '').trim().toLocaleLowerCase();
        let matched = 0;
        navGroups.forEach((group) => {
            let groupMatches = 0;
            group.querySelectorAll('[data-nav-item]').forEach((item) => {
                const visible = !term || (item.dataset.navLabel ?? '').includes(term);
                item.classList.toggle('hidden', !visible);
                if (visible) groupMatches++;
            });
            group.classList.toggle('hidden', groupMatches === 0);
            if (term && groupMatches) group.open = true;
            if (term) matched += groupMatches;
        });
        shell.querySelector('[data-nav-empty]')?.classList.toggle('hidden', !term || matched > 0);
        if (!term && !shell.classList.contains('schoolapp-collapsed')) {
            navGroups.forEach((group, i) => {
                group.open = i === 0 || !!group.querySelector('[aria-current="page"]');
            });
        }
    };
    search?.addEventListener('input', filterMenu);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (shell.classList.contains('schoolapp-mobile-open')) {
                setMobileOpen(false);
                document.getElementById('mobile-nav-toggle')?.focus();
            }
            if (profile?.open) profile.open = false;
            if (search && document.activeElement === search) { search.value = ''; filterMenu(); }
        }
        if (event.key === '/' && !event.altKey && !event.ctrlKey && !event.metaKey) {
            const tag = document.activeElement?.tagName?.toLowerCase();
            if (!['input', 'textarea', 'select'].includes(tag) && !document.activeElement?.isContentEditable) {
                if (!desktop.matches) setMobileOpen(true);
                else if (shell.classList.contains('schoolapp-collapsed')) {
                    setCollapsed(false);
                    try { window.localStorage.setItem('schoolapp-sidebar-collapsed', 'false'); } catch {}
                }
                event.preventDefault();
                search?.focus();
            }
        }
    });

    document.addEventListener('click', (event) => {
        if (profile?.open && !profile.contains(event.target)) profile.open = false;
    });
    desktop.addEventListener('change', () => setMobileOpen(false));
}
