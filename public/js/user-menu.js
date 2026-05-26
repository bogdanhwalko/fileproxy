/* User menu dropdown — closes the topbar user details on outside click + Esc.
   Loaded once per page via @once / @push from app-topbar component. */
(() => {
    if (window.__fpUserMenuBound) return;
    window.__fpUserMenuBound = true;

    // Close any open [data-user-menu] when clicking outside or pressing Esc
    document.addEventListener('click', (e) => {
        document.querySelectorAll('details[data-user-menu][open]').forEach((m) => {
            if (! m.contains(e.target)) m.removeAttribute('open');
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('details[data-user-menu][open]').forEach((m) => m.removeAttribute('open'));
        }
    });
})();
