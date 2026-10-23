<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="eas-my-results">

    <h2>
        <?php esc_html_e('My Results', 'eas-quiz'); ?>
    </h2>

    <?php if (empty($attempts)) : ?>

        <p>
            <?php esc_html_e('You have not completed any quizzes yet.', 'eas-quiz'); ?>
        </p>

    <?php else : ?>

        <div class="eas-my-results-table-wrapper">

            <table class="eas-my-results-table">

                <thead>

                    <tr>

                        <th>
                            <?php esc_html_e('Quiz Name', 'eas-quiz'); ?>
                        </th>

                        <th>
                            <?php esc_html_e('Completed Time', 'eas-quiz'); ?>
                        </th>

                        <th>
                            <?php esc_html_e('Status', 'eas-quiz'); ?>
                        </th>

                        <th>
                            <?php esc_html_e('Score', 'eas-quiz'); ?>
                        </th>

                        <th>
                            <?php esc_html_e('Review', 'eas-quiz'); ?>
                        </th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach (
                        $attempts
                        as $attempt
                    ) : ?>

                        <?php
                        $attempt_id =
                            (int) $attempt['id'];

                        $grading_status =
                            $this->attempts->grading_status(
                                $attempt_id
                            );

                        $manual_question_count = wp_cache_get(
                            'attempt_' . $attempt_id,
                            'eas_manual_question_count'
                        );

                        if (false === $manual_question_count) {

                            global $wpdb;

                            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The custom EAS tables require this joined count, which is cached per attempt for 60 seconds.
                            $manual_question_count = (int) $wpdb->get_var(
                                $wpdb->prepare(
                                    "
                                    SELECT COUNT(*)
                                    FROM {$wpdb->prefix}eas_responses r
                                    INNER JOIN {$wpdb->prefix}eas_items i
                                        ON r.item_id = i.id
                                    WHERE r.attempt_id = %d
                                    AND i.type IN (
                                        'essay',
                                        'audio_response'
                                    )
                                    ",
                                    $attempt_id
                                )
                            );

                            wp_cache_set(
                                'attempt_' . $attempt_id,
                                $manual_question_count,
                                'eas_manual_question_count',
                                60
                            );

                        }

                        if ($manual_question_count > 0) {

                            if ($grading_status === 'Needs grading') {

                                $display_status =
                                    __('Pending Grading', 'eas-quiz');

                            } else {

                                $display_status =
                                    __('Graded', 'eas-quiz');

                            }

                        } else {

                            $display_status =
                                __('Auto Graded', 'eas-quiz');

                        }

                        if (
                            $manual_question_count > 0
                            &&
                            $grading_status === 'Needs grading'
                        ) {

                            $display_score = '—';

                        } else {

                            $display_score =
                                number_format_i18n(
                                    (float) $attempt['score'],
                                    2
                                );

                        }

                        $completed_time = '—';

                        if (!empty($attempt['completed_at'])) {

                            $timestamp = strtotime(
                                $attempt['completed_at']
                            );

                            if ($timestamp !== false) {

                                $completed_time =
                                    wp_date(
                                        get_option(
                                            'date_format'
                                        ) . ' ' .
                                        get_option(
                                            'time_format'
                                        ),
                                        $timestamp
                                    );

                            }

                        }

                        $results_page_id =
                            (int) get_option(
                                'eas_results_page_id'
                            );

                        $review_url = '';

                        if ($results_page_id > 0) {

                            $results_url =
                                get_permalink(
                                    $results_page_id
                                );

                            if ($results_url) {

                                $review_url =
                                    add_query_arg(
                                        array(
                                            'attempt_id' =>
                                                $attempt_id,
                                            'review' => 1
                                        ),
                                        $results_url
                                    );

                            }

                        }

                        ?>

                        <tr>

                            <td data-label="<?php esc_attr_e('Quiz Name', 'eas-quiz'); ?>">
                                <?php
                                echo esc_html(
                                    $attempt['quiz_title']
                                    ?? __('Quiz', 'eas-quiz')
                                );
                                ?>
                            </td>

                            <td data-label="<?php esc_attr_e('Completed Time', 'eas-quiz'); ?>">
                                <?php echo esc_html($completed_time); ?>
                            </td>

                            <td data-label="<?php esc_attr_e('Status', 'eas-quiz'); ?>">
                                <span
                                    class="eas-result-status eas-result-status-<?php echo esc_attr(
                                        sanitize_title(
                                            $display_status
                                        )
                                    ); ?>"
                                >
                                    <?php echo esc_html($display_status); ?>
                                </span>
                            </td>

                            <td data-label="<?php esc_attr_e('Score', 'eas-quiz'); ?>">
                                <?php echo esc_html($display_score); ?>
                            </td>

                            <td data-label="<?php esc_attr_e('Review', 'eas-quiz'); ?>">
                                <?php if ($review_url) : ?>
                                    <a
                                        class="button button-primary"
                                        href="<?php echo esc_url($review_url); ?>"
                                    >
                                        <?php esc_html_e('Review', 'eas-quiz'); ?>
                                    </a>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>