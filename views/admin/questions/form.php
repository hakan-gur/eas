<?php

if (!defined('ABSPATH')) {
    exit;
}

$question_id = 0;
$question_text = '';
$points = 1;

$type = $_GET['type'] ?? 'multiple_choice';

$audio_delay_seconds = 0;
$audio_prepare_seconds = 5;
$audio_duration_seconds = 45;

$essay_min_lines = 10;

/*
 * Determine whether this form belongs to a page
 * or directly to a quiz question bank.
 */
$is_bank = (
    isset($quiz_id)
    &&
    (int) $quiz_id > 0
    &&
    (!isset($page_id) || (int) $page_id <= 0)
);

$quiz_id = isset($quiz_id)
    ? (int) $quiz_id
    : 0;

$page_id = isset($page_id)
    ? (int) $page_id
    : 0;


/*
 * Existing question.
 */
if ($question) {

    $question_id =
        (int) $question['id'];

    $question_text =
        $question['question_text'];

    $points =
        $question['points'];

    if ($question_id > 0) {
        $type =
            $question['type'];
    }

    if (!empty($question['settings'])) {

        $settings = json_decode(
            $question['settings'],
            true
        );

        if (is_array($settings)) {

            $essay_min_lines =
                (int) (
                    $settings['essay_min_lines']
                    ?? 10
                );

            $audio_delay_seconds =
                (int) (
                    $settings['audio_delay_seconds']
                    ?? 0
                );

            $audio_prepare_seconds =
                (int) (
                    $settings['audio_prepare_seconds']
                    ?? 5
                );

            $audio_duration_seconds =
                (int) (
                    $settings['audio_duration_seconds']
                    ?? 45
                );
        }
    }
}

?>

<div class="wrap">

    <h1>

        <?php

        if ($question_id > 0) {

            echo $is_bank
                ? 'Edit Question'
                : 'Edit Question';

        } else {

            echo $is_bank
                ? 'Create New Question'
                : 'Add New Question';

        }

        ?>

    </h1>


    <?php if ($is_bank): ?>

        <p>
            This question will be saved in the
            <strong>Question Bank</strong> and will
            not be assigned to a page yet.
        </p>

    <?php endif; ?>


    <form method="post"
          action="<?php echo admin_url(
              'admin-post.php'
          ); ?>">


        <input type="hidden"
               name="action"
               value="eas_save_question">


        <input type="hidden"
               name="quiz_id"
               value="<?php echo esc_attr(
                   $quiz_id
               ); ?>">


        <input type="hidden"
               name="page_id"
               value="<?php echo esc_attr(
                   $page_id
               ); ?>">
               
               <?php if (!empty($quiz_id)) : ?>
<input type="hidden"
       name="quiz_id"
       value="<?php echo esc_attr($quiz_id); ?>">
<?php endif; ?>


        <input type="hidden"
               name="question_id"
               value="<?php echo esc_attr(
                   $question_id
               ); ?>">


        <?php

        wp_nonce_field(
            'eas_save_question'
        );

        ?>


        <table class="form-table">


            <tr>

                <th>

                    <label>
                        Question Type
                    </label>

                </th>

                <td>

                    <select name="type">

                        <option value="multiple_choice"
                            <?php selected(
                                $type,
                                'multiple_choice'
                            ); ?>>

                            Multiple Choice

                        </option>


                        <option value="drag_drop_text"
                            <?php selected(
                                $type,
                                'drag_drop_text'
                            ); ?>>

                            Drag and Drop into Text

                        </option>


                        <option value="fill_blank"
                            <?php selected(
                                $type,
                                'fill_blank'
                            ); ?>>

                            Fill in the Blanks (C-Test)

                        </option>


                        <option value="description"
                            <?php selected(
                                $type,
                                'description'
                            ); ?>>

                            Description

                        </option>


                        <option value="essay"
                            <?php selected(
                                $type,
                                'essay'
                            ); ?>>

                            Essay

                        </option>


                        <option value="audio_response"
                            <?php selected(
                                $type,
                                'audio_response'
                            ); ?>>

                            Audio Response

                        </option>

                    </select>

                </td>

            </tr>


            <tr>

                <th>

                    <label>
                        Question Text
                    </label>

                </th>

                <td>

<?php

wp_editor(
    $question_text,
    'eas_question_text',
    array(
        'textarea_name'  => 'question_text',
        'media_buttons' => true,
        'teeny'         => false,
        'textarea_rows' => 8,
        'tinymce'       => array(
            'wpautop' => true
        ),
        'quicktags'     => true
    )
);

?>

                </td>

            </tr>

<tr>

    <th>

        <label for="eas_general_feedback">
            General Feedback
        </label>

    </th>

    <td>

        <textarea
            name="feedback"
            id="eas_general_feedback"
            rows="4"
            class="large-text"
            placeholder="Enter general feedback. HTML is allowed."
        ><?php echo esc_textarea(
            $question['feedback'] ?? ''
        ); ?></textarea>

        <p class="description">
            General feedback shown after the question is answered.
            HTML is allowed.
        </p>

    </td>

</tr>
            <tr>

                <th>

                    <label>
                        Points
                    </label>

                </th>

                <td>

                    <input type="number"
                           name="points"
                           value="<?php echo esc_attr(
                               $points
                           ); ?>"
                           min="0"
                           step="0.5">

                </td>

            </tr>


            <?php if (
                $type === 'audio_response'
            ): ?>


                <tr>

                    <th>

                        <label>
                            Delay Before Recording
                        </label>

                    </th>

                    <td>

                        <input type="number"
                               name="audio_delay_seconds"
                               value="<?php echo esc_attr(
                                   $audio_delay_seconds
                               ); ?>"
                               min="0">

                        <p class="description">

                            Seconds after page load before
                            preparation countdown begins.

                            <br>

                            Include page loading time +
                            instruction audio length.

                        </p>

                    </td>

                </tr>


                <tr>

                    <th>

                        <label>
                            Preparation Countdown
                        </label>

                    </th>

                    <td>

                        <input type="number"
                               name="audio_prepare_seconds"
                               value="<?php echo esc_attr(
                                   $audio_prepare_seconds
                               ); ?>"
                               min="0"
                               max="45">

                        <p class="description">

                            Seconds before recording starts.

                        </p>

                    </td>

                </tr>


                <tr>

                    <th>

                        <label>
                            Recording Duration
                        </label>

                    </th>

                    <td>

                        <input type="number"
                               name="audio_duration_seconds"
                               value="<?php echo esc_attr(
                                   $audio_duration_seconds
                               ); ?>"
                               min="12"
                               max="60">

                        <p class="description">

                            Speaking time (12-60 seconds).

                        </p>

                    </td>

                </tr>


            <?php endif; ?>


            <?php if (
                $type === 'multiple_choice'
            ): ?>


                <?php

                for ($i = 1; $i <= 5; $i++) {

                    $answer_text = '';
                    $checked = false;

                    if (!empty($answers)) {

                        foreach (
                            $answers
                            as $answer
                        ) {

                            if (
                                (int)
                                $answer['sort_order']
                                === $i
                            ) {

                                $answer_text =
                                    $answer['answer_text'];

                                $checked =
                                    (bool)
                                    $answer['is_correct'];

                                break;
                            }
                        }
                    }

                    ?>

                    <tr>

                        <th>

                            <label>
                                Option
                                <?php echo $i; ?>
                            </label>

                        </th>

                        <td>

                            <input type="text"
                                   name="answer_<?php echo $i; ?>"
                                   class="regular-text"
                                   value="<?php echo esc_attr(
                                       $answer_text
                                   ); ?>">

                            <label>

                                <input type="radio"
                                       name="correct"
                                       value="<?php echo $i; ?>"
                                       <?php checked(
                                           $checked
                                       ); ?>>

                                Correct

                            </label>

                        </td>

                    </tr>

                    <?php
                }
                ?>


            <?php elseif (
                $type === 'drag_drop_text'
            ): ?>


                <tr>

                    <th>

                        <label>
                            Instructions
                        </label>

                    </th>

                    <td>

                        <p>
                            Use placeholders in the question
                            text to create drag-and-drop gaps.
                        </p>

                        <p>
                            Example:
                        </p>

                        <code>
                            She went [[1]] the shop and bought [[2]] apples.
                        </code>

                    </td>

                </tr>


                <?php

                for ($i = 1; $i <= 7; $i++) {

                    $answer_text = '';

                    if (!empty($answers)) {

                        foreach (
                            $answers
                            as $answer
                        ) {

                            if (
                                (int)
                                $answer['correct_position']
                                === $i
                            ) {

                                $answer_text =
                                    $answer['answer_text'];

                                break;
                            }
                        }
                    }

                    ?>

                    <tr>

                        <th>

                            <label>
                                Option
                                <?php echo $i; ?>
                            </label>

                        </th>

                        <td>

                            <input type="text"
                                   name="answer_<?php echo $i; ?>"
                                   class="regular-text"
                                   value="<?php echo esc_attr(
                                       $answer_text
                                   ); ?>">

                        </td>

                    </tr>

                    <?php
                }
                ?>


                <tr>

                    <th>

                        <label>
                            Distractor
                        </label>

                    </th>

                    <td>

                        <?php

                        $distractor = '';

                        if (!empty($answers)) {

                            foreach (
                                $answers
                                as $answer
                            ) {

                                if (
                                    (int)
                                    $answer['correct_position']
                                    === 0
                                ) {

                                    $distractor =
                                        $answer['answer_text'];

                                    break;
                                }
                            }
                        }

                        ?>

                        <input type="text"
                               name="answer_8"
                               class="regular-text"
                               value="<?php echo esc_attr(
                                   $distractor
                               ); ?>">

                    </td>

                </tr>


            <?php elseif (
                $type === 'essay'
            ): ?>


                <tr>

                    <th>

                        <label>
                            Minimum answer lines
                        </label>

                    </th>

                    <td>

                        <input type="number"
                               name="essay_min_lines"
                               value="<?php echo esc_attr(
                                   $essay_min_lines
                               ); ?>"
                               min="2"
                               max="100">

                        <p class="description">

                            Minimum visible writing lines
                            for students.

                        </p>

                    </td>

                </tr>


            <?php elseif (
                $type === 'fill_blank'
            ): ?>


                <tr>

                    <th>

                        <label>
                            Instructions
                        </label>

                    </th>

                    <td>

                        <p>

                            Enter the correct missing
                            letters in square brackets.

                        </p>

                        <p>

                            Example:

                            <code>
                                The govern[ment] introduced
                                a new pol[icy].
                            </code>

                        </p>

                    </td>

                </tr>


            <?php endif; ?>


        </table>


        <?php

        submit_button(

            $question_id > 0
                ? 'Update Question'
                : 'Save Question'

        );

        ?>


        <?php if ($is_bank): ?>

            <a class="button"
               href="<?php echo admin_url(

                   'admin.php?page=eas-questions' .
                   '&quiz_id=' .
                   $quiz_id .
                   '&action=bank'

               ); ?>">

                Cancel

            </a>

        <?php else: ?>

            <a class="button"
               href="<?php echo admin_url(

                   'admin.php?page=eas-questions' .
                   '&page_id=' .
                   $page_id

               ); ?>">

                Cancel

            </a>

        <?php endif; ?>


        <script>

        document
            .querySelector('select[name="type"]')
            .addEventListener(
                'change',
                function () {

                    const url =
                        new URL(
                            window.location.href
                        );

                    url.searchParams.set(
                        'type',
                        this.value
                    );

                    url.searchParams.delete(
                        'id'
                    );

                    window.location.href =
                        url.toString();

                }
            );

        </script>


    </form>

</div>