<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="eas-result">
    <h2>
        <?php echo esc_html(
            $attempt['quiz_title']
        ); ?>
    </h2>
    <p>
        <strong>Score:</strong>
        <?php echo esc_html(
            $attempt['score']
        ); ?>
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
                Page <?php echo esc_html(
                    $page_index + 1
                ); ?>
                <?php if (!empty($page['title'])): ?>
                    :
                    <?php echo esc_html(
                        $page['title']
                    ); ?>
                <?php endif; ?>
            </h2>
            <?php if (empty($page_responses_list)): ?>
                <p>
                    No responses on this page.
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
    Question
    <?php echo esc_html(
        $review_question_number
    ); ?>
</h3>
                        <div>
                            <?php echo wp_kses_post(
                                $response['question_text']
                            ); ?>
                        </div>
                        <?php
                        /*
                         * Description questions:
                         *
                         * These are informational content only.
                         * Do not display an answer or points.
                         */
                        if (
                            ($response['type'] ?? '')
                            !== 'description'
                        ) :
                        ?>
                            <p>
                                <strong>
                                    Your answer:
                                </strong>
                                <?php
                                $answer =
                                    $response['answer']
                                    ?? '';
/*
 * Fill-in-the-blank response
 *
 * ResultRenderer prepares the clean student answer
 * in $response['student_answer'].
 */
if (
    ($response['type'] ?? '') === 'fill_blank'
) {
    echo esc_html(
        $response['student_answer'] ?? ''
    );
}
/*
 * Audio response
 */
elseif (
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
            src="<?php echo esc_url(
                $audio_url
            ); ?>"
            type="audio/webm">
    </audio>
    <?php
}
/*
 * Drag-and-drop response
 */
elseif (
    ($response['type'] ?? '') === 'drag_drop_text'
) {
    echo esc_html(
        $response['student_answer'] ?? ''
    );
}
/*
 * Normal answers / Multiple Choice
 */
else {
    /*
     * 1. Controller-provided answer text.
     */
    if (
        !empty(
            $response['answer_text']
        )
    ) {
        echo esc_html(
            $response['answer_text']
        );
    }

    /*
     * 2. Options attached to the response.
     */
    elseif (
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
                (int)$opt['id']
                === (int)$answer
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
    }

    /*
     * 3. Database lookup fallback.
     */
    elseif (
        is_numeric($answer)
    ) {
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
    }

    /*
     * 4. Normal string answer.
     */
    else {
        echo esc_html(
            $answer
        );
    }
}
                                ?>
                            </p>
                            <?php if (
    !empty(
        $response['general_feedback']
    )
) : ?>
    <p>
        <strong>
            General feedback:
        </strong>
        <?php echo wp_kses_post(
            $response['general_feedback']
        ); ?>
    </p>
<?php endif; ?>

<p>
    <strong>
        Points:
    </strong>
    <?php echo esc_html(
        $response[
            'points_awarded'
        ]
    ); ?>
    /
    <?php echo esc_html(
        $response[
            'max_points'
        ]
    ); ?>
</p>
                        <?php endif; ?>
                        <?php if (
                            !empty(
                                $response[
                                    'teacher_feedback'
                                ]
                            )
                        ) : ?>
                            <p>
                                <strong>
                                    Teacher feedback:
                                </strong>
                            </p>
                            <div
                                class="eas-teacher-feedback"
                            >
                                <?php echo nl2br(
                                    esc_html(
                                        $response[
                                            'teacher_feedback'
                                        ]
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