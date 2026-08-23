(function () {
    var toggle = document.querySelector('[data-pf-menu]');
    if (!toggle) {
        return;
    }
    toggle.addEventListener('click', function () {
        document.body.classList.toggle('pf-nav-open');
    });
})();
