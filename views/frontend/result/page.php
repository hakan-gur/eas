<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="eas-result">
    <h2>
        <?php echo esc_html($attempt['quiz_title']); ?>
    </h2>
    <p>
        <strong><?php esc_html_e('Score:', 'eas-quiz'); ?></strong>
        <?php echo esc_html($attempt['score']); ?>
    </p>
    <hr>
    <?php
    $review_question_number = 0;
    ?>
    <?php foreach ($page_responses as $page_index => $page_data): ?>
        <?php
        $page = $page_data['page'];
        $page_responses_list =
            $page_data['responses'];
        ?>
        <div class="eas-result-page">
            <h2 class="eas-result-page-title">
                <?php
                printf(
                    esc_html__('Page %d', 'eas-quiz'),
                    $page_index + 1
                );
                ?>
                <?php if (!empty($page['title'])): ?>
                    :
                    <?php echo esc_html($page['title']); ?>
                <?php endif; ?>
            </h2>
            <?php if (empty($page_responses_list)): ?>
                <p>
                    <?php esc_html_e('No responses on this page.', 'eas-quiz'); ?>
                </p>
            <?php else: ?>
                <?php foreach (
                    $page_responses_list
                    as $index => $response
                ): ?>
                <?php
                $review_question_number++;
                ?>
                    <div class="eas-result-question">
                        <h3>
                            <?php
                            printf(
                                esc_html__('Question %d', 'eas-quiz'),
                                $review_question_number
                            );
                            ?>
                        </h3>
                        <div>
                            <?php echo wp_kses_post(
                                $response['question_text']
                            ); ?>
                        </div>
                        <?php
                        if (
                            ($response['type'] ?? '')
                            !== 'description'
                        ) :
                        ?>
                            <p>
                                <strong>
                                    <?php esc_html_e('Your answer:', 'eas-quiz'); ?>
                                </strong>
                                <?php
                                $answer =
                                    $response['answer']
                                    ?? '';

                                if (
                                    ($response['type'] ?? '') === 'fill_blank'
                                ) {
                                    echo esc_html(
                                        $response['student_answer'] ?? ''
                                    );
                                } elseif (
                                    str_ends_with(
                                        $answer,
                                        '.webm'
                                    )
                                ) {
                                    $audio_url =
                                        wp_upload_dir()['baseurl']
                                        . '/eas-audio/'
                                        . $answer;
                                    ?>
                                    <audio controls>
                                        <source
                                            src="<?php echo esc_url($audio_url); ?>"
                                            type="audio/webm">
                                    </audio>
                                    <?php
                                } elseif (
                                    ($response['type'] ?? '') === 'drag_drop_text'
                                ) {
                                    echo esc_html(
                                        $response['student_answer'] ?? ''
                                    );
                                } else {
                                    if (!empty($response['answer_text'])) {
                                        echo esc_html(
                                            $response['answer_text']
                                        );
                                    } elseif (
                                        is_numeric($answer)
                                        &&
                                        !empty($response['options'])
                                        &&
                                        is_array($response['options'])
                                    ) {
                                        $option_label = $answer;

                                        foreach (
                                            $response['options']
                                            as $opt
                                        ) {
                                            if (
                                                isset($opt['id'])
                                                &&
                                                (int) $opt['id']
                                                === (int) $answer
                                            ) {
                                                $option_label =
                                                    $opt['option_text']
                                                    ??
                                                    $opt['text']
                                                    ??
                                                    $opt['label']
                                                    ??
                                                    $answer;

                                                break;
                                            }
                                        }

                                        echo esc_html(
                                            $option_label
                                        );
                                    } elseif (is_numeric($answer)) {
                                        global $wpdb;

                                        $answer_text =
                                            $wpdb->get_var(
                                                $wpdb->prepare(
                                                    "SELECT answer_text
                                                     FROM {$wpdb->prefix}eas_answers
                                                     WHERE id = %d",
                                                    intval($answer)
                                                )
                                            );

                                        echo esc_html(
                                            !empty($answer_text)
                                                ? $answer_text
                                                : $answer
                                        );
                                    } else {
                                        echo esc_html($answer);
                                    }
                                }
                                ?>
                            </p>

                            <?php if (!empty($response['general_feedback'])) : ?>
                                <p>
                                    <strong>
                                        <?php esc_html_e('General feedback:', 'eas-quiz'); ?>
                                    </strong>
                                    <?php echo wp_kses_post(
                                        $response['general_feedback']
                                    ); ?>
                                </p>
                            <?php endif; ?>

                            <p>
                                <strong>
                                    <?php esc_html_e('Points:', 'eas-quiz'); ?>
                                </strong>
                                <?php echo esc_html($response['points_awarded']); ?>
                                /
                                <?php echo esc_html($response['max_points']); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($response['teacher_feedback'])) : ?>
                            <p>
                                <strong>
                                    <?php esc_html_e('Teacher feedback:', 'eas-quiz'); ?>
                                </strong>
                            </p>
                            <div class="eas-teacher-feedback">
                                <?php echo nl2br(
                                    esc_html(
                                        $response['teacher_feedback']
                                    )
                                ); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <hr>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <hr class="eas-result-page-separator">
    <?php endforeach; ?>
</div>