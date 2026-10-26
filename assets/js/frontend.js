console.log("EAS frontend loaded.");


/*
 * ============================================================
 * TOEFL-style C-Test keyboard behaviour
 * ============================================================
 *
 * - Automatically moves to next letter box
 * - Backspace moves backward
 * - Backspace can cross blank boundaries
 * - Arrow keys navigate within blanks
 */
document.addEventListener('input', function (event) {

    if (!event.target.classList.contains('eas-letter-input')) {
        return;
    }

    const currentInput = event.target;

    const blank = currentInput.closest('.eas-c-test-blank');

    if (!blank) {
        return;
    }

    const inputs = Array.from(
        blank.querySelectorAll('.eas-letter-input')
    );

    const index = inputs.indexOf(currentInput);


    /*
     * Move to next letter box in the same blank
     */
    if (
        currentInput.value !== '' &&
        index < inputs.length - 1
    ) {
        inputs[index + 1].focus();
        return;
    }


    /*
     * If this is the last letter box,
     * move to the first letter box
     * of the next blank.
     */
    if (
        currentInput.value !== '' &&
        index === inputs.length - 1
    ) {

        const question =
            blank.closest('.eas-question-text');

        if (!question) {
            return;
        }

        const blanks = Array.from(
            question.querySelectorAll('.eas-c-test-blank')
        );

        const blankIndex =
            blanks.indexOf(blank);

        const nextBlank =
            blanks[blankIndex + 1];

        if (nextBlank) {

            const nextInput =
                nextBlank.querySelector(
                    '.eas-letter-input'
                );

            if (nextInput) {
                nextInput.focus();
            }
        }
    }

});


/*
 * ============================================================
 * C-Test keyboard navigation
 * ============================================================
 */
document.addEventListener('keydown', function (event) {

    if (!event.target.classList.contains('eas-letter-input')) {
        return;
    }

    const currentInput = event.target;

    const blank =
        currentInput.closest('.eas-c-test-blank');

    if (!blank) {
        return;
    }

    const inputs = Array.from(
        blank.querySelectorAll('.eas-letter-input')
    );

    const index =
        inputs.indexOf(currentInput);


    /*
     * Backspace behaviour
     */
    if (event.key === 'Backspace') {

        /*
         * If current box contains a letter,
         * allow normal deletion.
         */
        if (currentInput.value !== '') {
            return;
        }

        /*
         * Empty box:
         * move to previous letter box.
         */
        event.preventDefault();

        if (index > 0) {
            inputs[index - 1].focus();
            return;
        }


        /*
         * First letter of a blank:
         * move to previous blank.
         */
        const question =
            blank.closest('.eas-question-text');

        if (!question) {
            return;
        }

        const blanks = Array.from(
            question.querySelectorAll('.eas-c-test-blank')
        );

        const blankIndex =
            blanks.indexOf(blank);

        const previousBlank =
            blanks[blankIndex - 1];

        if (previousBlank) {

            const previousInputs =
                Array.from(
                    previousBlank.querySelectorAll(
                        '.eas-letter-input'
                    )
                );

            const lastInput =
                previousInputs[
                    previousInputs.length - 1
                ];

            if (lastInput) {
                lastInput.focus();
            }
        }

        return;
    }


    /*
     * Arrow navigation
     */
    if (
        event.key === 'ArrowRight' &&
        index < inputs.length - 1
    ) {

        event.preventDefault();

        inputs[index + 1].focus();

        return;
    }


    if (
        event.key === 'ArrowLeft' &&
        index > 0
    ) {

        event.preventDefault();

        inputs[index - 1].focus();

        return;
    }

});


/*
 * ============================================================
 * Paste support
 * ============================================================
 *
 * Pasting "der" into the first letter box
 * fills d / e / r automatically.
 */
document.addEventListener('paste', function (event) {

    if (!event.target.classList.contains('eas-letter-input')) {
        return;
    }

    event.preventDefault();

    const text =
        event.clipboardData
            .getData('text')
            .trim();

    const currentInput =
        event.target;

    const blank =
        currentInput.closest('.eas-c-test-blank');

    if (!blank) {
        return;
    }

    const inputs = Array.from(
        blank.querySelectorAll('.eas-letter-input')
    );

    const start =
        inputs.indexOf(currentInput);


    text.split('').forEach(function (letter, i) {

        const input =
            inputs[start + i];

        if (input) {
            input.value = letter;
        }

    });


    /*
     * Trigger existing save logic.
     */
    currentInput.dispatchEvent(
        new Event('input', {
            bubbles: true
        })
    );


    const nextInput =
        inputs[start + text.length];

    if (nextInput) {
        nextInput.focus();
    }

});


/*
 * ============================================================
 * DRAG AND DROP - WORDS IN TEXT
 * ============================================================
 *
 * Pointer-based drag/drop is used instead of native HTML5
 * drag/drop. This avoids browser/theme/page-builder conflicts
 * with inline spans and makes the interaction reliable for:
 *
 * 1. Word Bank -> Blank
 * 2. Filled Blank -> another Blank
 * 3. Filled Blank -> Word Bank
 * 4. Word Bank -> occupied Blank
 *
 * A question can have up to seven blanks and one distractor
 * word. The distractor simply remains in the Word Bank.
 */
document.addEventListener('DOMContentLoaded', function () {

    const questionContainers = document.querySelectorAll(
        '.eas-question.eas-drag-drop-text'
    );

    questionContainers.forEach(function (questionContainer) {

        const wordBank = questionContainer.querySelector(
            '.eas-word-bank'
        );

        if (!wordBank) {
            return;
        }

        const optionsContainer = wordBank.querySelector(
            '.eas-drag-options'
        ) || wordBank;

        let dragState = null;
        let dragGhost = null;
        let moved = false;

        function getAnswers() {
            const field = questionContainer.querySelector(
                '.eas-drag-answer'
            );

            if (!field || !field.value) {
                return {};
            }

            try {
                const parsed = JSON.parse(field.value);
                return parsed && typeof parsed === 'object' ? parsed : {};
            } catch (error) {
                return {};
            }
        }

        function saveAnswers(answers) {
            const field = questionContainer.querySelector(
                '.eas-drag-answer'
            );

            if (field) {
                field.value = JSON.stringify(answers);
            }
        }

        function removeSavedAnswer(blank) {
            const position = blank.dataset.position;
            const answers = getAnswers();

            if (position) {
                delete answers[position];
            }

            saveAnswers(answers);
        }

        function saveBlankAnswer(blank, value) {
            const position = blank.dataset.position;
            const answers = getAnswers();

            if (position) {
                answers[position] = value;
            }

            saveAnswers(answers);
        }

        function returnWordToBank(value) {
            if (!value) {
                return;
            }

            const existing = Array.from(
                optionsContainer.querySelectorAll('.eas-draggable-word')
            );

            const exists = existing.some(function (word) {
                return (word.dataset.originalValue || word.textContent.trim()) === value;
            });

            if (exists) {
                return;
            }

            const word = document.createElement('span');
            word.className = 'eas-draggable-word';
            word.dataset.originalValue = value;
            word.textContent = value;
            word.setAttribute('draggable', 'false');
            optionsContainer.appendChild(word);
        }

        /*
         * Determine whether a blank starts a sentence.
         * We examine all rendered text before the blank, not only
         * its immediate <p>. This also works when the question
         * contains headings, spans, strong/em tags, etc.
         */
        function beginsSentence(blank) {
            const textContainer = questionContainer.querySelector(
                '.eas-question-text'
            );

            if (!textContainer) {
                return false;
            }

            const range = document.createRange();
            range.selectNodeContents(textContainer);
            range.setEndBefore(blank);

            const before = range.toString().trim();

            if (before === '') {
                return true;
            }

            return /[.!?]["')\]]*$/.test(before);
        }

        function getDisplayValue(value, blank) {
            if (!value) {
                return '';
            }

            if (beginsSentence(blank)) {
                return value.charAt(0).toUpperCase() + value.slice(1);
            }

            return value;
        }

        function createGhost(word) {
            const rect = word.getBoundingClientRect();
            const ghost = word.cloneNode(true);

            ghost.classList.add('eas-drag-ghost');
            ghost.style.position = 'fixed';
            ghost.style.left = rect.left + 'px';
            ghost.style.top = rect.top + 'px';
            ghost.style.width = rect.width + 'px';
            ghost.style.height = rect.height + 'px';
            ghost.style.margin = '0';
            ghost.style.zIndex = '999999';
            ghost.style.pointerEvents = 'none';
            ghost.style.opacity = '0.85';

            document.body.appendChild(ghost);
            return ghost;
        }

        function moveGhost(event) {
            if (!dragGhost) {
                return;
            }

            dragGhost.style.left = (event.clientX + 10) + 'px';
            dragGhost.style.top = (event.clientY + 10) + 'px';
        }

        function getDropBlankAt(x, y) {
            const blanks = questionContainer.querySelectorAll('.eas-drop-blank');

            for (let i = 0; i < blanks.length; i++) {
                const rect = blanks[i].getBoundingClientRect();

                if (
                    x >= rect.left &&
                    x <= rect.right &&
                    y >= rect.top &&
                    y <= rect.bottom
                ) {
                    return blanks[i];
                }
            }

            return null;
        }

        function clearDragState() {
            if (dragGhost) {
                dragGhost.remove();
            }

            dragGhost = null;
            dragState = null;
            moved = false;

            questionContainer.querySelectorAll('.drag-over').forEach(function (element) {
                element.classList.remove('drag-over');
            });
        }

        function dropIntoBlank(blank) {
            if (!dragState || !blank) {
                return;
            }

            const newValue = dragState.value;

            if (!newValue) {
                return;
            }

            const oldValue = blank.dataset.originalValue || '';

            /*
             * If a word is moved from one blank to another, clear
             * its original blank first. The destination's old word
             * is returned to the Word Bank.
             */
            if (oldValue && oldValue !== newValue) {
                returnWordToBank(oldValue);
            }

            if (
                dragState.sourceType === 'blank' &&
                dragState.sourceElement &&
                dragState.sourceElement !== blank
            ) {
                const sourceBlank = dragState.sourceElement;

                sourceBlank.textContent = '______';
                sourceBlank.classList.remove('filled');
                delete sourceBlank.dataset.originalValue;
                sourceBlank.setAttribute('draggable', 'false');
                removeSavedAnswer(sourceBlank);
            }

            blank.textContent = getDisplayValue(newValue, blank);
            blank.classList.add('filled');
            blank.dataset.originalValue = newValue;
            blank.setAttribute('draggable', 'true');
            saveBlankAnswer(blank, newValue);

            if (dragState.sourceType === 'word-bank' && dragState.sourceElement) {
                dragState.sourceElement.remove();
            }
        }

        function dropIntoWordBank() {
            if (!dragState || dragState.sourceType !== 'blank') {
                return;
            }

            const sourceBlank = dragState.sourceElement;
            returnWordToBank(dragState.value);

            if (sourceBlank) {
                sourceBlank.textContent = '______';
                sourceBlank.classList.remove('filled');
                delete sourceBlank.dataset.originalValue;
                sourceBlank.setAttribute('draggable', 'false');
                removeSavedAnswer(sourceBlank);
            }
        }

        function startDrag(event, element, sourceType) {
            if (event.button !== undefined && event.button !== 0) {
                return;
            }

            const value = element.dataset.originalValue || element.textContent.trim();

            if (!value) {
                return;
            }

            if (sourceType === 'blank' && !element.classList.contains('filled')) {
                return;
            }

            event.preventDefault();

            dragState = {
                value: value,
                sourceType: sourceType,
                sourceElement: element,
                pointerId: event.pointerId
            };

            moved = false;
            dragGhost = createGhost(element);

            element.classList.add('eas-drag-source');

            try {
                element.setPointerCapture(event.pointerId);
            } catch (error) {
                /* Pointer capture is optional. */
            }
        }

        function handlePointerMove(event) {
            if (!dragState || event.pointerId !== dragState.pointerId) {
                return;
            }

            moved = true;
            event.preventDefault();
            moveGhost(event);

            const blank = getDropBlankAt(event.clientX, event.clientY);

            questionContainer.querySelectorAll('.drag-over').forEach(function (element) {
                if (element !== blank) {
                    element.classList.remove('drag-over');
                }
            });

            if (blank) {
                blank.classList.add('drag-over');
            }
        }

        function handlePointerUp(event) {
            if (!dragState || event.pointerId !== dragState.pointerId) {
                return;
            }

            event.preventDefault();

            const source = dragState.sourceElement;
            const dropBlank = getDropBlankAt(event.clientX, event.clientY);

            if (dropBlank) {
                dropIntoBlank(dropBlank);
            } else if (
                dragState.sourceType === 'blank' &&
                wordBank.contains(event.target)
            ) {
                dropIntoWordBank();
            }

            if (source) {
                source.classList.remove('eas-drag-source');
            }

            clearDragState();
        }

        function handlePointerCancel(event) {
            if (!dragState || event.pointerId !== dragState.pointerId) {
                return;
            }

            if (dragState.sourceElement) {
                dragState.sourceElement.classList.remove('eas-drag-source');
            }

            clearDragState();
        }

        /*
         * Event delegation means newly returned Word Bank words
         * automatically work without installing new listeners.
         */
        questionContainer.addEventListener('pointerdown', function (event) {
            const word = event.target.closest('.eas-draggable-word');
            if (word && questionContainer.contains(word)) {
                startDrag(event, word, 'word-bank');
                return;
            }

            const blank = event.target.closest('.eas-drop-blank');
            if (blank && questionContainer.contains(blank)) {
                startDrag(event, blank, 'blank');
            }
        });

        document.addEventListener('pointermove', handlePointerMove, { passive: false });
        document.addEventListener('pointerup', handlePointerUp, { passive: false });
        document.addEventListener('pointercancel', handlePointerCancel, { passive: false });

        /*
         * Initialize all options. HTML5 draggable is deliberately
         * disabled because this implementation uses Pointer Events.
         */
        const initialWords = wordBank.querySelectorAll('.eas-draggable-word');
        initialWords.forEach(function (word) {
            word.setAttribute('draggable', 'false');
            if (!word.dataset.originalValue) {
                word.dataset.originalValue = word.textContent.trim();
            }
        });

        const blanks = questionContainer.querySelectorAll('.eas-drop-blank');
        blanks.forEach(function (blank) {
            if (blank.dataset.answer) {
                blank.dataset.originalValue = blank.dataset.answer;
                blank.classList.add('filled');
                blank.setAttribute('draggable', 'true');
            } else {
                blank.setAttribute('draggable', 'false');
            }
        });

        console.log('Drag words found:', initialWords.length);
        console.log('Drop blanks found:', blanks.length);
    });
});


/*
 * ============================================================
 * Save C-Test / Fill-in-the-Blank answers
 * ============================================================
 */
document.addEventListener(
    'input',
    function (event) {

        if (
            !event.target.classList.contains(
                'eas-letter-input'
            )
        ) {
            return;
        }


        const container =
            event.target.closest(
                '.eas-c-test-blank'
            );


        if (!container) {
            return;
        }


        const position =
            container.dataset.position;


        const inputs =
            container.querySelectorAll(
                '.eas-letter-input'
            );


        let word = '';


        inputs.forEach(
            function (input) {

                word += input.value;

            }
        );


        const question =
            container.closest(
                '.eas-question'
            );


        if (!question) {
            return;
        }


        const hidden =
            question.querySelector(
                '.eas-fill-answer'
            );


        if (!hidden) {
            return;
        }


        let answers = {};


        if (
            hidden.value !== ''
        ) {

            try {

                answers =
                    JSON.parse(
                        hidden.value
                    );

            } catch (e) {

                answers = {};

            }

        }


        answers[position] =
            word;


        hidden.value =
            JSON.stringify(
                answers
            );

    }
);


/*
 * ============================================================
 * Safety save for Fill Blank questions
 * before form submission
 * ============================================================
 */
document.addEventListener(
    'submit',
    function (event) {

        console.log(
            'SUBMIT HANDLER RUNNING'
        );


        /*
         * ------------------------------------------------------
         * Save Fill-in-the-Blank / C-Test answers
         * ------------------------------------------------------
         */
        const fillQuestions =
            document.querySelectorAll(
                '.eas-fill-blank'
            );


        fillQuestions.forEach(
            function (question) {

                const hidden =
                    question.querySelector(
                        '.eas-fill-answer'
                    );


                if (!hidden) {
                    return;
                }


                let answers = {};


                const blanks =
                    question.querySelectorAll(
                        '.eas-c-test-blank'
                    );


                blanks.forEach(
                    function (blank) {

                        const position =
                            blank.dataset.position;


                        const inputs =
                            blank.querySelectorAll(
                                '.eas-letter-input'
                            );


                        let word = '';


                        inputs.forEach(
                            function (input) {

                                word +=
                                    input.value;

                            }
                        );


                        answers[position] =
                            word;

                    }
                );


                hidden.value =
                    JSON.stringify(
                        answers
                    );


                console.log(
                    'Fill blank saved:',
                    hidden.value
                );

            }
        );


        /*
         * ------------------------------------------------------
         * Safety save for Drag-and-Drop questions
         *
         * The individual drop events already save the answers,
         * but this makes sure the hidden fields reflect the
         * current state immediately before submission.
         * ------------------------------------------------------
         */
        const dragQuestions =
            document.querySelectorAll(
                '.eas-question'
            );


        dragQuestions.forEach(
            function (question) {

                const hidden =
                    question.querySelector(
                        '.eas-drag-answer'
                    );


                if (!hidden) {
                    return;
                }


                let answers = {};


                const dragBlanks =
                    question.querySelectorAll(
                        '.eas-drop-blank'
                    );


                dragBlanks.forEach(
                    function (blank) {

                        const position =
                            blank.dataset.position;


                        const originalValue =
                            blank.dataset.originalValue;


                        if (
                            originalValue !== undefined &&
                            originalValue !== ''
                        ) {

                            /*
                             * Save original value,
                             * not visually capitalized text.
                             */
                            answers[position] =
                                originalValue;

                        }

                    }
                );


                hidden.value =
                    JSON.stringify(
                        answers
                    );


                console.log(
                    'Drag answers saved:',
                    hidden.value
                );

            }
        );

    }
);