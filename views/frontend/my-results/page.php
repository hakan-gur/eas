<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="eas-my-results">

    <h2>
        My Results
    </h2>


    <?php if (empty($attempts)) : ?>

        <p>
            You have not completed any quizzes yet.
        </p>

    <?php else : ?>

        <div class="eas-my-results-table-wrapper">

            <table class="eas-my-results-table">

                <thead>

                    <tr>

                        <th>
                            Quiz Name
                        </th>

                        <th>
                            Completed Time
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Score
                        </th>

                        <th>
                            Review
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

                        /*
                         * Determine grading status.
                         *
                         * Essay and audio_response require
                         * manual grading.
                         */
                        $grading_status =
                            $this->attempts->grading_status(
                                $attempt_id
                            );

                        /*
                         * Determine whether this attempt
                         * contains manually graded questions.
                         */
                        global $wpdb;

                        $manual_question_count =
                            (int) $wpdb->get_var(
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

                        if ($manual_question_count > 0) {

                            if ($grading_status === 'Needs grading') {

                                $display_status =
                                    'Pending Grading';

                            } else {

                                $display_status =
                                    'Graded';

                            }

                        } else {

                            $display_status =
                                'Auto Graded';

                        }


                        /*
                         * Score.
                         *
                         * Pending manual grading:
                         * do not display a final score.
                         */
                        if (
                            $manual_question_count > 0
                            &&
                            $grading_status === 'Needs grading'
                        ) {

                            $display_score = '—';

                        } else {

                            $display_score =
                                number_format(
                                    (float) $attempt['score'],
                                    2
                                );

                        }


                        /*
                         * Completed date/time.
                         *
                         * The database stores the value using
                         * WordPress's current time.
                         */
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


                        /*
                         * Existing EAS Results page.
                         */
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

                            <td
                                data-label="Quiz Name"
                            >
                                <?php
                                echo esc_html(
                                    $attempt['quiz_title']
                                    ?? 'Quiz'
                                );
                                ?>
                            </td>


                            <td
                                data-label="Completed Time"
                            >
                                <?php
                                echo esc_html(
                                    $completed_time
                                );
                                ?>
                            </td>


                            <td
                                data-label="Status"
                            >

                                <span
                                    class="eas-result-status eas-result-status-<?php echo esc_attr(
                                        sanitize_title(
                                            $display_status
                                        )
                                    ); ?>"
                                >
                                    <?php
                                    echo esc_html(
                                        $display_status
                                    );
                                    ?>
                                </span>

                            </td>


                            <td
                                data-label="Score"
                            >
                                <?php
                                echo esc_html(
                                    $display_score
                                );
                                ?>
                            </td>


                            <td
                                data-label="Review"
                            >

                                <?php if ($review_url) : ?>

                                    <a
                                        class="button button-primary"
                                        href="<?php echo esc_url(
                                            $review_url
                                        ); ?>"
                                    >
                                        Review
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