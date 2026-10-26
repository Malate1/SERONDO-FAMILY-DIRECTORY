(function () {
    var toggle = document.getElementById('menu-toggle');
    var nav = document.getElementById('primary-nav');
    var desktop = window.matchMedia('(min-width: 1024px)');

    function setOpen(open) {
        nav.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
    }

    function syncLayout() {
        var focusWasInNav = nav.contains(document.activeElement);
        toggle.hidden = desktop.matches;
        setOpen(desktop.matches);
        if (!desktop.matches && focusWasInNav) { toggle.focus(); }
    }

    toggle.addEventListener('click', function () { setOpen(nav.hidden); });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !desktop.matches && !nav.hidden) {
            setOpen(false);
            toggle.focus();
        }
    });
    document.addEventListener('click', function (event) {
        if (!desktop.matches && !nav.hidden && !event.target.closest('.site-header')) { setOpen(false); }
    });
    nav.addEventListener('click', function (event) {
        if (!desktop.matches && event.target.closest('a')) { setOpen(false); }
    });
    desktop.addEventListener('change', syncLayout);
    syncLayout();
})();
