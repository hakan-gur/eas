(function () {
    'use strict';

    function initializeAttempt() {
        const form = document.getElementById('eas-attempt-form');

        if (!form) {
            return;
        }

        const actionField = form.querySelector('input[name="action"]');
        const nonceField = form.querySelector('input[name="_wpnonce"]');

        function submitExpiredPage() {
            if (actionField) {
                actionField.value = form.dataset.pageExpiryAction || 'eas_submit_attempt';
            }

            if (nonceField && form.dataset.pageExpiryNonce) {
                nonceField.value = form.dataset.pageExpiryNonce;
            }

            form.submit();
        }

        function startTimer(initialSeconds, countdownId, onExpire) {
            let remaining = Math.max(0, parseInt(initialSeconds, 10) || 0);
            let submitted = false;
            let timer = null;
            const countdown = document.getElementById(countdownId);

            function update() {
                if (remaining < 0) {
                    remaining = 0;
                }

                if (countdown) {
                    const minutes = Math.floor(remaining / 60);
                    const seconds = remaining % 60;
                    countdown.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
                }

                if (remaining <= 0) {
                    if (!submitted) {
                        submitted = true;
                        if (timer !== null) {
                            window.clearInterval(timer);
                        }
                        onExpire();
                    }
                    return;
                }

                remaining--;
            }

            update();

            if (!submitted) {
                timer = window.setInterval(update, 1000);
            }
        }

        if (form.hasAttribute('data-page-time-remaining')) {
            startTimer(
                form.dataset.pageTimeRemaining,
                'eas-page-countdown',
                submitExpiredPage
            );
        } else if (form.hasAttribute('data-quiz-time-remaining')) {
            startTimer(
                form.dataset.quizTimeRemaining,
                'eas-countdown',
                function () {
                    if (actionField) {
                        actionField.value = 'eas_submit_attempt';
                    }
                    if (nonceField && form.dataset.quizExpiryNonce) {
                        nonceField.value = form.dataset.quizExpiryNonce;
                    }
                    form.submit();
                }
            );
        }

        form.addEventListener('submit', function (event) {
            if (
                actionField
                && actionField.value === 'eas_submit_attempt'
                && form.dataset.submitConfirm
                && !window.confirm(form.dataset.submitConfirm)
            ) {
                event.preventDefault();
            }
        });

        form.querySelectorAll('.eas-attempt-navigation-submit').forEach(function (button) {
            button.addEventListener('click', function () {
                if (actionField) {
                    actionField.value = button.dataset.easAction || '';
                }

                if (nonceField && button.dataset.easNonce) {
                    nonceField.value = button.dataset.easNonce;
                }

                form.submit();
            });
        });

        const layout = document.getElementById('eas-attempt-layout');
        const toggle = document.getElementById('eas-navigation-toggle');

        if (!layout || !toggle) {
            return;
        }

        const storageKey = form.dataset.navigationStorageKey || '';
        const showLabel = form.dataset.showNavigationLabel || '';
        const hideLabel = form.dataset.hideNavigationLabel || '';

        function updateToggleState(hidden) {
            layout.classList.toggle('navigation-hidden', hidden);
            toggle.setAttribute('aria-expanded', hidden ? 'false' : 'true');
            toggle.textContent = hidden ? '<' : '>';
            toggle.title = hidden ? showLabel : hideLabel;
        }

        try {
            updateToggleState(
                storageKey !== ''
                && window.sessionStorage.getItem(storageKey) === '1'
            );
        } catch (error) {
            updateToggleState(false);
        }

        toggle.addEventListener('click', function () {
            const hidden = !layout.classList.contains('navigation-hidden');
            updateToggleState(hidden);

            try {
                if (storageKey !== '') {
                    window.sessionStorage.setItem(storageKey, hidden ? '1' : '0');
                }
            } catch (error) {
                // Navigation remains usable if session storage is unavailable.
            }
        });

        layout.querySelectorAll('.eas-navigation-page-number').forEach(function (button) {
            button.addEventListener('click', function () {
                const pageId = button.getAttribute('data-page-id');
                const target = document.getElementById('eas-go-to-page-id');

                if (!pageId || !actionField || !nonceField || !target) {
                    return;
                }

                actionField.value = 'eas_go_to_page';
                nonceField.value = form.dataset.goToPageNonce || '';
                target.value = pageId;
                form.submit();
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeAttempt, { once: true });
    } else {
        initializeAttempt();
    }
})();
