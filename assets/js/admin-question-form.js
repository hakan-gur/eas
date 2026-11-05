(function () {
    'use strict';

    document.addEventListener('change', function (event) {
        const select = event.target;

        if (!select.matches('select[name="type"]')) {
            return;
        }

        const url = new URL(window.location.href);
        url.searchParams.set('type', select.value);
        url.searchParams.delete('id');
        window.location.href = url.toString();
    });
})();
