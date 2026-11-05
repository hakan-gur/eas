(function () {
    'use strict';

    document.querySelectorAll('.eas-question').forEach(function (container) {
        const box = container.querySelector('.eas-essay-box');

        if (!box) {
            return;
        }

        const cut = container.querySelector('.eas-cut');
        const copy = container.querySelector('.eas-copy');
        const paste = container.querySelector('.eas-paste');
        const counter = container.querySelector('.eas-word-count span');

        function updateWordCount() {
            if (!counter) {
                return;
            }

            const text = box.value.trim();
            counter.textContent = text === '' ? '0' : String(text.split(/\s+/).length);
        }

        if (cut) {
            cut.addEventListener('click', function () {
                document.execCommand('cut');
            });
        }

        if (copy) {
            copy.addEventListener('click', function () {
                document.execCommand('copy');
            });
        }

        if (paste) {
            paste.addEventListener('click', async function () {
                try {
                    const text = await navigator.clipboard.readText();
                    const start = box.selectionStart;
                    const end = box.selectionEnd;

                    box.value = box.value.substring(0, start) + text + box.value.substring(end);
                    box.focus();
                    box.setSelectionRange(start + text.length, start + text.length);
                    box.dispatchEvent(new Event('input', { bubbles: true }));
                } catch (error) {
                    window.alert(container.dataset.pasteError || 'Paste is not allowed by your browser.');
                }
            });
        }

        box.addEventListener('input', updateWordCount);
        updateWordCount();
    });
})();
