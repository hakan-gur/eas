(function () {
    'use strict';

    function initFillBlank(root) {
        root.querySelectorAll('.eas-c-test-blank').forEach(function (blank) {
            var inputs = Array.from(blank.querySelectorAll('.eas-letter-input'));

            inputs.forEach(function (input, index) {
                input.addEventListener('input', function () {
                    if (input.value !== '' && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                        return;
                    }

                    if (input.value !== '' && index === inputs.length - 1) {
                        var nextBlank = blank.parentElement.querySelectorAll('.eas-c-test-blank')[
                            Array.from(blank.parentElement.querySelectorAll('.eas-c-test-blank')).indexOf(blank) + 1
                        ];
                        if (nextBlank) {
                            var nextInput = nextBlank.querySelector('.eas-letter-input');
                            if (nextInput) nextInput.focus();
                        }
                    }
                });

                input.addEventListener('keydown', function (event) {
                    if (event.key === 'Backspace' && input.value === '' && index > 0) {
                        event.preventDefault();
                        inputs[index - 1].focus();
                    }

                    if (event.key === 'ArrowLeft' && index > 0) {
                        event.preventDefault();
                        inputs[index - 1].focus();
                    }

                    if (event.key === 'ArrowRight' && index < inputs.length - 1) {
                        event.preventDefault();
                        inputs[index + 1].focus();
                    }
                });
            });
        });
    }

    function initDragDrop(root) {
        root.querySelectorAll('.eas-drag-drop-text').forEach(function (question) {
            var dragged = null;
            var bank = question.querySelector('.eas-drag-options');
            if (!bank) return;

            var blanks = Array.from(question.querySelectorAll('.eas-drop-blank'));
            var words = Array.from(bank.querySelectorAll('.eas-draggable-word'));

            words.forEach(function (word) {
                word.dataset.originalValue = word.dataset.originalValue || word.textContent.trim();
                word.draggable = true;
                word.addEventListener('dragstart', function (event) {
                    dragged = word;
                    word.classList.add('eas-preview-dragging');
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', word.textContent.trim());
                });
                word.addEventListener('dragend', function () {
                    word.classList.remove('eas-preview-dragging');
                    dragged = null;
                });
            });

            function beginsSentence(blank) {
                var textContainer = question.querySelector('.eas-question-text');
                if (!textContainer) return false;

                var range = document.createRange();
                range.selectNodeContents(textContainer);
                range.setEndBefore(blank);
                var before = range.toString().trim();

                return before === '' || /[.!?]["')\\]]*$/.test(before);
            }

            function displayValue(value, blank) {
                if (!value) return '';
                return beginsSentence(blank)
                    ? value.charAt(0).toUpperCase() + value.slice(1)
                    : value;
            }

            function clearBlank(blank) {
                var existing = blank.querySelector('.eas-preview-word');
                if (existing) {
                    existing.className = 'eas-draggable-word';
                    existing.draggable = true;
                    bank.appendChild(existing);
                }
                blank.classList.remove('filled');
                blank.textContent = '______';
            }

            function putWord(blank, word) {
                if (!blank || !word) return;
                clearBlank(blank);
                var clone = word.cloneNode(true);
                var originalValue = word.dataset.originalValue || word.textContent.trim();
                clone.dataset.originalValue = originalValue;
                clone.textContent = displayValue(originalValue, blank);
                clone.className = 'eas-draggable-word eas-preview-word';
                clone.draggable = true;
                clone.addEventListener('dragstart', function (event) {
                    dragged = clone;
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', clone.dataset.originalValue || clone.textContent.trim());
                });
                blank.textContent = '';
                blank.appendChild(clone);
                blank.classList.add('filled');
                if (word.parentElement === bank) word.remove();
            }

            blanks.forEach(function (blank) {
                blank.addEventListener('dragover', function (event) {
                    event.preventDefault();
                    blank.classList.add('eas-preview-drag-over');
                });
                blank.addEventListener('dragleave', function () {
                    blank.classList.remove('eas-preview-drag-over');
                });
                blank.addEventListener('drop', function (event) {
                    event.preventDefault();
                    blank.classList.remove('eas-preview-drag-over');
                    putWord(blank, dragged);
                });
                blank.addEventListener('dblclick', function () {
                    clearBlank(blank);
                });
            });

            bank.addEventListener('dragover', function (event) {
                event.preventDefault();
            });
            bank.addEventListener('drop', function (event) {
                event.preventDefault();
                if (dragged && dragged.parentElement && dragged.parentElement.classList.contains('eas-drop-blank')) {
                    var source = dragged.parentElement;
                    dragged.className = 'eas-draggable-word';
                    bank.appendChild(dragged);
                    source.classList.remove('filled');
                    source.textContent = '______';
                }
            });
        });
    }

    function init() {
        var root = document.querySelector('.eas-question-preview');
        if (!root) return;
        initFillBlank(root);
        initDragDrop(root);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
