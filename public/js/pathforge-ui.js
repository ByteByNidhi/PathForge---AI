(function () {
    var toggle = document.querySelector('[data-pf-menu]');
    if (toggle) {
        toggle.addEventListener('click', function () {
            document.body.classList.toggle('pf-nav-open');
        });
    }

    var publicToggle = document.querySelector('[data-pf-public-menu]');
    if (publicToggle) {
        publicToggle.addEventListener('click', function () {
            document.body.classList.toggle('pf-public-nav-open');
        });
    }

    if (typeof Choices !== 'undefined') {
        document.querySelectorAll('select').forEach(function (select) {
            if (select.dataset.pfChoices === '1' || select.closest('.choices')) {
                return;
            }
            select.dataset.pfChoices = '1';
            new Choices(select, {
                searchEnabled: select.options.length > 10,
                shouldSort: false,
                itemSelectText: '',
                allowHTML: false,
                position: 'auto'
            });
        });
    }

    var modal = document.getElementById('pf-confirm');
    var messageEl = document.getElementById('pf-confirm-message');
    var okBtn = document.getElementById('pf-confirm-ok');
    var pendingForm = null;

    function closeModal() {
        if (!modal) return;
        modal.hidden = true;
        pendingForm = null;
    }

    if (modal && messageEl && okBtn) {
        document.querySelectorAll('[data-pf-confirm-cancel]').forEach(function (el) {
            el.addEventListener('click', closeModal);
        });
        okBtn.addEventListener('click', function () {
            if (pendingForm) {
                pendingForm.dataset.pfConfirmed = '1';
                pendingForm.submit();
            }
            closeModal();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });
    }

    document.querySelectorAll('form[data-pf-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.pfConfirmed === '1') {
                return;
            }
            event.preventDefault();
            pendingForm = form;
            if (messageEl) {
                messageEl.textContent = form.getAttribute('data-pf-confirm') || 'Please confirm this action.';
            }
            if (okBtn) {
                var danger = form.querySelector('.btn-danger, .pf-btn--danger');
                okBtn.className = danger ? 'btn btn-danger' : 'btn';
                okBtn.textContent = danger ? 'Delete' : 'Confirm';
            }
            if (modal) {
                modal.hidden = false;
            }
        });
    });
})();
