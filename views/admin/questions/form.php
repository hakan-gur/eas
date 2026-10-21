<?php

if (!defined('ABSPATH')) {
    exit;
}

$question_id = 0;
$question_text = '';
$points = 0;
$type = (
    isset($_GET['type']) && is_string($_GET['type'])
        ? sanitize_key(wp_unslash($_GET['type']))
        : 'multiple_choice'
);

if (!in_array($type, array('multiple_choice', 'drag_drop_text', 'fill_blank', 'description', 'essay', 'audio_response'), true)) {
    $type = 'multiple_choice';
}
$audio_delay_seconds = 0;
$audio_prepare_seconds = 5;
$audio_duration_seconds = 45;
$essay_min_lines = 10;

$is_bank = (
    isset($quiz_id)
    && (int) $quiz_id > 0
    && (!isset($page_id) || (int) $page_id <= 0)
);

$quiz_id = isset($quiz_id) ? (int) $quiz_id : 0;
$page_id = isset($page_id) ? (int) $page_id : 0;

if ($question) {
    $question_id = (int) $question['id'];
    $question_text = $question['question_text'];
    $points = (
    $question['type'] === 'description'
        ? 0
        : $question['points']
);

    if ($question_id > 0) {
        $type = $question['type'];
    }

    if (!empty($question['settings'])) {
        $settings = json_decode($question['settings'], true);

        if (is_array($settings)) {
            $essay_min_lines = (int) ($settings['essay_min_lines'] ?? 10);
            $audio_delay_seconds = (int) ($settings['audio_delay_seconds'] ?? 0);
            $audio_prepare_seconds = (int) ($settings['audio_prepare_seconds'] ?? 5);
            $audio_duration_seconds = (int) ($settings['audio_duration_seconds'] ?? 45);
        }
    }
}
?>

<div class="wrap">

    <h1>
        <?php
        echo esc_html(
            $question_id > 0
                ? __('Edit Question', 'eas-quiz')
                : ($is_bank
                    ? __('Create New Question', 'eas-quiz')
                    : __('Add New Question', 'eas-quiz'))
        );
        ?>
    </h1>

    <?php if ($is_bank): ?>
        <p>
            <?php
            printf(
                /* translators: %s: the Question Bank name, displayed in bold. */
                wp_kses_post(__('This question will be saved in the %s and will not be assigned to a page yet.', 'eas-quiz')),
                '<strong>' . esc_html__('Question Bank', 'eas-quiz') . '</strong>'
            );
            ?>
        </p>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">

        <input type="hidden" name="action" value="eas_save_question">
        <input type="hidden" name="quiz_id" value="<?php echo esc_attr($quiz_id); ?>">
        <input type="hidden" name="page_id" value="<?php echo esc_attr($page_id); ?>">
        <input type="hidden" name="question_id" value="<?php echo esc_attr($question_id); ?>">

        <?php wp_nonce_field('eas_save_question'); ?>

        <table class="form-table">
            <tr>
                <th>
                    <label><?php esc_html_e('Question Type', 'eas-quiz'); ?></label>
                </th>
                <td>
                    <select name="type">
                        <option value="multiple_choice" <?php selected($type, 'multiple_choice'); ?>>
                            <?php esc_html_e('Multiple Choice', 'eas-quiz'); ?>
                        </option>
                        <option value="drag_drop_text" <?php selected($type, 'drag_drop_text'); ?>>
                            <?php esc_html_e('Drag and Drop into Text', 'eas-quiz'); ?>
                        </option>
                        <option value="fill_blank" <?php selected($type, 'fill_blank'); ?>>
                            <?php esc_html_e('Fill in the Blanks (C-Test)', 'eas-quiz'); ?>
                        </option>
                        <option value="description" <?php selected($type, 'description'); ?>>
                            <?php esc_html_e('Description', 'eas-quiz'); ?>
                        </option>
                        <option value="essay" <?php selected($type, 'essay'); ?>>
                            <?php esc_html_e('Essay', 'eas-quiz'); ?>
                        </option>
                        <option value="audio_response" <?php selected($type, 'audio_response'); ?>>
                            <?php esc_html_e('Audio Response', 'eas-quiz'); ?>
                        </option>
                    </select>
                </td>
            </tr>

            <tr>
                <th>
                    <label><?php esc_html_e('Question Text', 'eas-quiz'); ?></label>
                </th>
                <td>
                    <?php
                    wp_editor(
                        $question_text,
                        'eas_question_text',
                        array(
                            'textarea_name' => 'question_text',
                            'media_buttons' => true,
                            'teeny' => false,
                            'textarea_rows' => 8,
                            'tinymce' => array('wpautop' => true),
                            'quicktags' => true,
                        )
                    );
                    ?>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="eas_general_feedback">
                        <?php esc_html_e('General Feedback', 'eas-quiz'); ?>
                    </label>
                </th>
                <td>
                    <textarea
                        name="feedback"
                        id="eas_general_feedback"
                        rows="4"
                        class="large-text"
                        placeholder="<?php echo esc_attr__('Enter general feedback. HTML is allowed.', 'eas-quiz'); ?>"
                    ><?php echo esc_textarea($question['feedback'] ?? ''); ?></textarea>
                    <p class="description">
                        <?php esc_html_e('General feedback shown after the question is answered. HTML is allowed.', 'eas-quiz'); ?>
                    </p>
                </td>
            </tr>

            <?php if ($type !== 'description'): ?>
    <tr>
        <th>
            <label><?php esc_html_e('Points', 'eas-quiz'); ?></label>
        </th>
        <td>
            <input
                type="number"
                name="points"
                value="<?php echo esc_attr($points); ?>"
                min="0"
                step="0.5"
            >
        </td>
    </tr>
<?php endif; ?>

            <?php if ($type === 'audio_response'): ?>
                <tr>
                    <th>
                        <label><?php esc_html_e('Delay Before Recording', 'eas-quiz'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="audio_delay_seconds" value="<?php echo esc_attr($audio_delay_seconds); ?>" min="0">
                        <p class="description">
                            <?php esc_html_e('Seconds after page load before preparation countdown begins.', 'eas-quiz'); ?><br>
                            <?php esc_html_e('Include page loading time + instruction audio length.', 'eas-quiz'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th>
                        <label><?php esc_html_e('Preparation Countdown', 'eas-quiz'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="audio_prepare_seconds" value="<?php echo esc_attr($audio_prepare_seconds); ?>" min="0" max="45">
                        <p class="description">
                            <?php esc_html_e('Seconds before recording starts.', 'eas-quiz'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th>
                        <label><?php esc_html_e('Recording Duration', 'eas-quiz'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="audio_duration_seconds" value="<?php echo esc_attr($audio_duration_seconds); ?>" min="10" max="180">
                        <p class="description">
                            <?php esc_html_e('Speaking time (10-180 seconds).', 'eas-quiz'); ?>
                        </p>
                    </td>
                </tr>
            <?php endif; ?>

            <?php if ($type === 'multiple_choice'): ?>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <?php
                    $answer_text = '';
                    $checked = false;

                    if (!empty($answers)) {
                        foreach ($answers as $answer) {
                            if ((int) $answer['sort_order'] === $i) {
                                $answer_text = $answer['answer_text'];
                                $checked = (bool) $answer['is_correct'];
                                break;
                            }
                        }
                    }
                    ?>
                    <tr>
                        <th>
                            <label>
                                <?php
                                echo esc_html(
                                    sprintf(
                                        /* translators: 1: answer option label, 2: option number. */
                                        __('%1$s %2$d', 'eas-quiz'),
                                        __('Option', 'eas-quiz'),
                                        $i
                                    )
                                );
                                ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" name="answer_<?php echo esc_attr($i); ?>" class="regular-text" value="<?php echo esc_attr($answer_text); ?>">
                            <label>
                                <input type="radio" name="correct" value="<?php echo esc_attr($i); ?>" <?php checked($checked); ?>>
                                <?php esc_html_e('Correct', 'eas-quiz'); ?>
                            </label>
                        </td>
                    </tr>
                <?php endfor; ?>

            <?php elseif ($type === 'drag_drop_text'): ?>
                <tr>
                    <th>
                        <label><?php esc_html_e('Instructions', 'eas-quiz'); ?></label>
                    </th>
                    <td>
                        <p><?php esc_html_e('Use placeholders in the question text to create drag-and-drop gaps.', 'eas-quiz'); ?></p>
                        <p><?php esc_html_e('Example:', 'eas-quiz'); ?></p>
                        <code>She went [[1]] the shop and bought [[2]] apples.</code>
                    </td>
                </tr>

                <?php for ($i = 1; $i <= 7; $i++): ?>
                    <?php
                    $answer_text = '';

                    if (!empty($answers)) {
                        foreach ($answers as $answer) {
                            if ((int) $answer['correct_position'] === $i) {
                                $answer_text = $answer['answer_text'];
                                break;
                            }
                        }
                    }
                    ?>
                    <tr>
                        <th>
                            <label>
                                <?php
                                echo esc_html(
                                    sprintf(
                                        /* translators: 1: answer option label, 2: option number. */
                                        __('%1$s %2$d', 'eas-quiz'),
                                        __('Option', 'eas-quiz'),
                                        $i
                                    )
                                );
                                ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" name="answer_<?php echo esc_attr($i); ?>" class="regular-text" value="<?php echo esc_attr($answer_text); ?>">
                        </td>
                    </tr>
                <?php endfor; ?>

                <tr>
                    <th>
                        <label><?php esc_html_e('Distractor', 'eas-quiz'); ?></label>
                    </th>
                    <td>
                        <?php
                        $distractor = '';

                        if (!empty($answers)) {
                            foreach ($answers as $answer) {
                                if ((int) $answer['correct_position'] === 0) {
                                    $distractor = $answer['answer_text'];
                                    break;
                                }
                            }
                        }
                        ?>
                        <input type="text" name="answer_8" class="regular-text" value="<?php echo esc_attr($distractor); ?>">
                    </td>
                </tr>

            <?php elseif ($type === 'essay'): ?>
                <tr>
                    <th>
                        <label><?php esc_html_e('Minimum answer lines', 'eas-quiz'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="essay_min_lines" value="<?php echo esc_attr($essay_min_lines); ?>" min="2" max="100">
                        <p class="description">
                            <?php esc_html_e('Minimum visible writing lines for students.', 'eas-quiz'); ?>
                        </p>
                    </td>
                </tr>

            <?php elseif ($type === 'fill_blank'): ?>
                <tr>
                    <th>
                        <label><?php esc_html_e('Instructions', 'eas-quiz'); ?></label>
                    </th>
                    <td>
                        <p><?php esc_html_e('Enter the correct missing letters in square brackets.', 'eas-quiz'); ?></p>
                        <p>
                            <?php esc_html_e('Example:', 'eas-quiz'); ?>
                            <code>The govern[ment] introduced a new pol[icy].</code>
                        </p>
                    </td>
                </tr>
            <?php endif; ?>
        </table>

        <?php
        submit_button(
            $question_id > 0
                ? __('Update Question', 'eas-quiz')
                : __('Save Question', 'eas-quiz')
        );
        ?>

        <?php if ($is_bank): ?>
            <a class="button" href="<?php echo esc_url(admin_url(
                'admin.php?page=eas-questions&quiz_id=' . $quiz_id . '&action=bank'
            )); ?>">
                <?php esc_html_e('Cancel', 'eas-quiz'); ?>
            </a>
        <?php else: ?>
            <a class="button" href="<?php echo esc_url(admin_url(
                'admin.php?page=eas-questions&page_id=' . $page_id
            )); ?>">
                <?php esc_html_e('Cancel', 'eas-quiz'); ?>
            </a>
        <?php endif; ?>

        <script>
        document
            .querySelector('select[name="type"]')
            .addEventListener('change', function () {
                const url = new URL(window.location.href);
                url.searchParams.set('type', this.value);
                url.searchParams.delete('id');
                window.location.href = url.toString();
            });
        </script>

    </form>
</div>
