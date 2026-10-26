(function () {
    var menu = document.getElementById('mobile-menu');
    var toggle = document.getElementById('menu-toggle');
    var nav = document.getElementById('primary-nav');
    if (!menu || !toggle || !nav) { return; }
    var desktop = window.matchMedia('(min-width: 1024px)');

    function closeMenu(returnFocus) {
        menu.open = false;
        toggle.setAttribute('aria-expanded', 'false');
        if (returnFocus) { toggle.focus(); }
    }

    function syncLayout() {
        if (desktop.matches) {
            var focusWasInMenu = menu.contains(document.activeElement);
            closeMenu(false);
            if (focusWasInMenu) { document.querySelector('#desktop-nav a').focus(); }
        }
    }

    menu.addEventListener('toggle', function () { toggle.setAttribute('aria-expanded', String(menu.open)); });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && menu.open) { closeMenu(true); }
    });
    document.addEventListener('click', function (event) {
        if (menu.open && !menu.contains(event.target)) { closeMenu(false); }
    });
    nav.addEventListener('click', function (event) {
        if (event.target.closest('a')) { closeMenu(false); }
    });
    if (desktop.addEventListener) { desktop.addEventListener('change', syncLayout); }
    else { desktop.addListener(syncLayout); }
    toggle.setAttribute('aria-expanded', String(menu.open));
    syncLayout();
})();
