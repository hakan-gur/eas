(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        const link = event.target.closest('.eas-confirm-delete');

        if (!link) {
            return;
        }

        const message = link.getAttribute('data-confirm');

        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    });
})();
