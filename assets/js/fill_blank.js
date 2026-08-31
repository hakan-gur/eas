document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.eas-fill-blank').forEach(function (question) {

        const hidden = question.querySelector('.eas-fill-answer');

        if (!hidden) {
            return;
        }

        function updateAnswer() {

            const result = {};

            question.querySelectorAll('.eas-c-test-blank').forEach(function (blank) {

                const position = blank.dataset.position;

                let word = '';

                blank.querySelectorAll('.eas-letter-input').forEach(function (input) {

                    word += input.value;

                });

                result[position] = word;

            });

            hidden.value = JSON.stringify(result);

        }

        question.querySelectorAll('.eas-letter-input').forEach(function (input) {

            input.addEventListener('input', updateAnswer);

            input.addEventListener('keyup', updateAnswer);

            input.addEventListener('change', updateAnswer);

        });

    });

});