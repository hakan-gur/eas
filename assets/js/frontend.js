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
 * Supports:
 *
 * 1. Word Bank -> Blank
 * 2. Filled Blank -> Word Bank
 * 3. Filled Blank -> another Blank
 * 4. Word Bank -> occupied Blank
 *
 * When replacing an occupied blank, the old word is returned
 * to the Word Bank.
 *
 * Original values are stored in data-original-value.
 * This is important because displayed words may be capitalized
 * when they begin a sentence.
 */
document.addEventListener('DOMContentLoaded', function () {

    /*
     * Each drag-and-drop question has its own Word Bank,
     * drop blanks, and drag state. This is important when
     * a page contains more than one drag-and-drop question.
     */
    const questionContainers =
        document.querySelectorAll(
            '.eas-question.eas-drag-drop-text'
        );

    questionContainers.forEach(
        function (questionContainer) {

            let draggedWord = null;


    /*
     * --------------------------------------------------------
     * Find Word Bank
     * --------------------------------------------------------
     */
            const wordBank =
                questionContainer.querySelector(
                    '.eas-word-bank'
                );

    if (!wordBank) {
        console.log(
            'No .eas-word-bank found.'
        );
        return;
    }


    /*
     * --------------------------------------------------------
     * Get the options container
     * --------------------------------------------------------
     */
    function getOptionsContainer() {

        const container =
            wordBank.querySelector(
                '.eas-drag-options'
            );

        return container || wordBank;
    }


    /*
     * --------------------------------------------------------
     * Read answers from hidden field
     * --------------------------------------------------------
     */
    function getAnswers(questionContainer) {

        if (!questionContainer) {
            return {};
        }

        const answerField =
            questionContainer.querySelector(
                '.eas-drag-answer'
            );

        if (!answerField) {
            return {};
        }

        if (answerField.value === '') {
            return {};
        }

        try {

            const parsed =
                JSON.parse(answerField.value);

            if (
                parsed &&
                typeof parsed === 'object'
            ) {
                return parsed;
            }

        } catch (error) {

            console.warn(
                'Could not parse drag answers:',
                error
            );

        }

        return {};
    }


    /*
     * --------------------------------------------------------
     * Save answers to hidden field
     * --------------------------------------------------------
     */
    function saveAnswers(
        questionContainer,
        answers
    ) {

        if (!questionContainer) {
            return;
        }

        const answerField =
            questionContainer.querySelector(
                '.eas-drag-answer'
            );

        if (!answerField) {
            return;
        }

        answerField.value =
            JSON.stringify(answers);

        console.log(
            'Saved drag answers:',
            answerField.value
        );
    }


    /*
     * --------------------------------------------------------
     * Remove a position from saved answers
     * --------------------------------------------------------
     */
    function removeSavedAnswer(
        blank
    ) {

        if (!blank) {
            return;
        }

        const questionContainer =
            blank.closest('.eas-question');

        if (!questionContainer) {
            return;
        }

        const answers =
            getAnswers(questionContainer);

        const position =
            blank.dataset.position;

        if (
            position !== undefined &&
            position !== ''
        ) {

            delete answers[position];

        }

        saveAnswers(
            questionContainer,
            answers
        );
    }


    /*
     * --------------------------------------------------------
     * Save a blank answer
     * --------------------------------------------------------
     */
    function saveBlankAnswer(
        blank,
        originalValue
    ) {

        const questionContainer =
            blank.closest('.eas-question');

        if (!questionContainer) {
            return;
        }

        const answers =
            getAnswers(questionContainer);

        answers[
            blank.dataset.position
        ] = originalValue;

        saveAnswers(
            questionContainer,
            answers
        );
    }


    /*
     * --------------------------------------------------------
     * Determine whether a blank begins a sentence
     * --------------------------------------------------------
     *
     * IMPORTANT:
     *
     * We inspect the rendered DOM, not the raw HTML.
     *
     * Therefore:
     *
     * <p>[[1]] ...</p>
     *
     * correctly identifies [[1]] as the first word.
     *
     * HTML tags such as <strong>, <em>, <span>, etc.
     * are ignored by Range.toString().
     */
    function beginsSentence(blank) {

        const paragraph =
            blank.closest('p');

        if (!paragraph) {
            return false;
        }


        const range =
            document.createRange();

        range.selectNodeContents(
            paragraph
        );

        range.setEndBefore(
            blank
        );


        const textBefore =
            range.toString().trim();


        /*
         * Blank is the first visible text
         * in the paragraph.
         */
        if (textBefore === '') {
            return true;
        }


        /*
         * Blank follows sentence-ending punctuation.
         *
         * Examples:
         *
         * "Hello. [[1]]"
         * "Hello! [[1]]"
         * "Hello? [[1]]"
         *
         * Also supports closing quotation marks
         * and brackets.
         */
        if (
            /[.!?]["')\]]*$/.test(
                textBefore
            )
        ) {
            return true;
        }


        return false;
    }


    /*
     * --------------------------------------------------------
     * Get display value
     * --------------------------------------------------------
     *
     * The original word is kept unchanged.
     *
     * Only the displayed value is capitalized.
     */
    function getDisplayValue(
        originalValue,
        blank
    ) {

        let value =
            originalValue;

        if (
            beginsSentence(blank) &&
            value.length > 0
        ) {

            value =
                value.charAt(0).toUpperCase() +
                value.slice(1);

        }

        return value;
    }


    /*
     * --------------------------------------------------------
     * Make a Word Bank word draggable
     * --------------------------------------------------------
     */
    function makeWordDraggable(word) {

        if (!word) {
            return;
        }


        /*
         * Avoid installing the event listener twice.
         */
        if (
            word.dataset.easDragReady === '1'
        ) {
            return;
        }

        word.dataset.easDragReady = '1';


        /*
         * Store the original value.
         *
         * This is deliberately NOT based on the
         * currently displayed capitalized value
         * when the word originally came from a blank.
         */
        if (
            !word.dataset.originalValue
        ) {

            word.dataset.originalValue =
                word.textContent.trim();

        }


        word.draggable = true;


        word.addEventListener(
            'dragstart',
            function (event) {

                const originalValue =
                    word.dataset.originalValue ||
                    word.textContent.trim();


                draggedWord = {

                    value: originalValue,

                    source: 'word-bank',

                    element: word

                };


                event.dataTransfer.effectAllowed =
                    'move';

                event.dataTransfer.setData(
                    'text/plain',
                    originalValue
                );


                console.log(
                    'Dragging from Word Bank:',
                    originalValue
                );

            }
        );

    }


    /*
     * --------------------------------------------------------
     * Return a word to Word Bank
     * --------------------------------------------------------
     */
    function returnWordToBank(
        originalValue
    ) {

        if (
            !originalValue ||
            originalValue.trim() === ''
        ) {
            return;
        }


        const existingWords =
            Array.from(
                wordBank.querySelectorAll(
                    '.eas-draggable-word'
                )
            );


        /*
         * Do not create duplicate options.
         */
        const alreadyExists =
            existingWords.some(
                function (word) {

                    return (
                        word.dataset.originalValue ===
                        originalValue
                    );

                }
            );


        if (alreadyExists) {
            return;
        }


        const newWord =
            document.createElement('span');


        newWord.className =
            'eas-draggable-word';

        newWord.draggable = true;

        newWord.dataset.originalValue =
            originalValue;

        newWord.textContent =
            originalValue;


        getOptionsContainer()
            .appendChild(newWord);


        makeWordDraggable(
            newWord
        );


        console.log(
            'Returned to Word Bank:',
            originalValue
        );

    }


    /*
     * --------------------------------------------------------
     * Initialize existing Word Bank words
     * --------------------------------------------------------
     */
    const initialWords =
        wordBank.querySelectorAll(
            '.eas-draggable-word'
        );


    initialWords.forEach(
        function (word) {

            makeWordDraggable(
                word
            );

        }
    );


    console.log(
        'Drag words found:',
        initialWords.length
    );


    /*
     * --------------------------------------------------------
     * Find all drop blanks
     * --------------------------------------------------------
     */
            const blanks =
                questionContainer.querySelectorAll(
                    '.eas-drop-blank'
                );


    console.log(
        'Drop blanks found:',
        blanks.length
    );


    /*
     * --------------------------------------------------------
     * Configure each blank
     * --------------------------------------------------------
     */
    blanks.forEach(
        function (blank) {


            /*
             * Allow dropping.
             */
            blank.addEventListener(
                'dragover',
                function (event) {

                    event.preventDefault();

                    blank.classList.add(
                        'drag-over'
                    );

                }
            );


            /*
             * Remove visual drag-over state.
             */
            blank.addEventListener(
                'dragleave',
                function () {

                    blank.classList.remove(
                        'drag-over'
                    );

                }
            );


            /*
             * ------------------------------------------------
             * Drop onto blank
             * ------------------------------------------------
             */
            blank.addEventListener(
                'drop',
                function (event) {

                    event.preventDefault();

                    blank.classList.remove(
                        'drag-over'
                    );


                    if (!draggedWord) {

                        console.log(
                            'No dragged word detected.'
                        );

                        return;
                    }


                    const newOriginalValue =
                        draggedWord.value;


                    if (
                        !newOriginalValue
                    ) {

                        draggedWord = null;

                        return;
                    }


                    /*
                     * Determine whether this blank
                     * already contains a word.
                     */
                    const oldOriginalValue =
                        blank.dataset.originalValue ||
                        '';


                    /*
                     * ------------------------------------------------
                     * CASE 1:
                     * The same word is dropped into the same blank.
                     *
                     * Nothing needs to be returned.
                     * ------------------------------------------------
                     */
                    if (
                        oldOriginalValue ===
                        newOriginalValue &&
                        draggedWord.source ===
                        'blank'
                    ) {

                        draggedWord = null;

                        return;
                    }


                    /*
                     * ------------------------------------------------
                     * CASE 2:
                     * The blank already contains another word.
                     *
                     * Return the old word to the Word Bank.
                     * ------------------------------------------------
                     */
                    if (
                        oldOriginalValue &&
                        oldOriginalValue !==
                        newOriginalValue
                    ) {

                        returnWordToBank(
                            oldOriginalValue
                        );

                    }


                    /*
                     * ------------------------------------------------
                     * CASE 3:
                     * The dragged word came from another blank.
                     *
                     * We must clear the original blank.
                     * ------------------------------------------------
                     */
                    if (
                        draggedWord.source ===
                        'blank' &&
                        draggedWord.element
                    ) {

                        const sourceBlank =
                            draggedWord.element;


                        /*
                         * Only clear the source blank
                         * if it is different from the
                         * destination blank.
                         */
                        if (
                            sourceBlank !== blank
                        ) {

                            sourceBlank.textContent =
                                '';

                            sourceBlank.classList.remove(
                                'filled'
                            );


                            delete sourceBlank.dataset
                                .originalValue;


                            removeSavedAnswer(
                                sourceBlank
                            );

                        }

                    }


                    /*
                     * ------------------------------------------------
                     * Display value
                     *
                     * Capitalization is visual only.
                     * ------------------------------------------------
                     */
                    const displayValue =
                        getDisplayValue(
                            newOriginalValue,
                            blank
                        );


                    /*
                     * Render word in blank.
                     */
                    blank.textContent =
                        displayValue;


                    blank.classList.add(
                        'filled'
                    );


                    /*
                     * IMPORTANT:
                     *
                     * Save the ORIGINAL value,
                     * not the visually capitalized value.
                     */
                    blank.dataset.originalValue =
                        newOriginalValue;


                    /*
                     * Make the filled blank draggable.
                     */
                    blank.draggable = true;


                    /*
                     * Save answer.
                     */
                    saveBlankAnswer(
                        blank,
                        newOriginalValue
                    );


                    /*
                     * ------------------------------------------------
                     * If the word came from the Word Bank,
                     * remove that option.
                     * ------------------------------------------------
                     */
                    if (
                        draggedWord.source ===
                        'word-bank'
                    ) {

                        const wordElement =
                            draggedWord.element;


                        if (wordElement) {

                            wordElement.remove();

                        }

                    }


                    console.log(
                        'Dropped:',
                        newOriginalValue,
                        'into position:',
                        blank.dataset.position
                    );


                    draggedWord = null;

                }
            );


            /*
             * ------------------------------------------------
             * Allow dragging a filled blank.
             * ------------------------------------------------
             */
            blank.addEventListener(
                'dragstart',
                function (event) {

                    if (
                        !blank.classList.contains(
                            'filled'
                        )
                    ) {

                        event.preventDefault();

                        return;
                    }


                    const originalValue =
                        blank.dataset.originalValue ||
                        blank.textContent.trim();


                    if (!originalValue) {

                        event.preventDefault();

                        return;
                    }


                    draggedWord = {

                        value: originalValue,

                        source: 'blank',

                        element: blank

                    };


                    event.dataTransfer.effectAllowed =
                        'move';

                    event.dataTransfer.setData(
                        'text/plain',
                        originalValue
                    );


                    console.log(
                        'Dragging from filled blank:',
                        originalValue
                    );

                }
            );


            /*
             * A blank must be draggable so that
             * a filled word can be moved elsewhere.
             */
            blank.draggable = true;

        }
    );


    /*
     * ========================================================
     * Drop back into Word Bank
     * ========================================================
     */
    wordBank.addEventListener(
        'dragover',
        function (event) {

            event.preventDefault();

        }
    );


    wordBank.addEventListener(
        'drop',
        function (event) {

            event.preventDefault();


            if (
                !draggedWord ||
                draggedWord.source !== 'blank'
            ) {

                draggedWord = null;

                return;
            }


            const originalValue =
                draggedWord.value;


            const sourceBlank =
                draggedWord.element;


            /*
             * Return the word to the Word Bank.
             */
            returnWordToBank(
                originalValue
            );


            /*
             * Clear the source blank.
             */
            if (sourceBlank) {

                sourceBlank.textContent =
                    '';

                sourceBlank.classList.remove(
                    'filled'
                );


                delete sourceBlank.dataset
                    .originalValue;


                sourceBlank.draggable = true;


                /*
                 * Clear saved answer.
                 */
                removeSavedAnswer(
                    sourceBlank
                );

            }


            console.log(
                'Word returned to Word Bank:',
                originalValue
            );


            draggedWord = null;

        }
    );

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