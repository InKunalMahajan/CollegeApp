import './bootstrap';

/* Minimal progressive enhancement for the mobile sidebar. */
const navToggle = document.querySelector('[data-nav-toggle]');
if (navToggle) {
    const panel = document.getElementById(navToggle.getAttribute('aria-controls'));
    const close = () => {
        panel?.classList.add('hidden');
        navToggle.setAttribute('aria-expanded', 'false');
        navToggle.setAttribute('aria-label', 'Open navigation');
    };
    navToggle.addEventListener('click', () => {
        if (!panel) return;
        const opening = panel.classList.contains('hidden');
        panel.classList.toggle('hidden', !opening);
        navToggle.setAttribute('aria-expanded', String(opening));
        navToggle.setAttribute('aria-label', opening ? 'Close navigation' : 'Open navigation');
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && navToggle.getAttribute('aria-expanded') === 'true') {
            close();
            navToggle.focus();
        }
    });
    window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => {
        if (event.matches) close();
    });
}
