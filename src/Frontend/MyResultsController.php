<?php

declare(strict_types=1);

namespace EAS\Frontend;

use EAS\Attempt\AttemptRepository;
use EAS\Quiz\QuizRepository;

if (!defined('ABSPATH')) {
    exit;
}

class MyResultsController
{
    private AttemptRepository $attempts;
    private QuizRepository $quizzes;
    private ResultController $results;

    public function __construct()
    {
        $this->attempts = new AttemptRepository();
        $this->quizzes = new QuizRepository();
        $this->results = new ResultController();
    }

    public function register(): void
    {
        add_shortcode(
            'eas_my_quizresults',
            array(
                $this,
                'render'
            )
        );
    }

    public function render(): string
    {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__(
                'Please log in to view your quiz results.',
                'eas-quiz'
            ) . '</p>';
        }
        /*
         * Validate optional result-navigation parameters before conversion.
         * Reject arrays, malformed integers, zero/negative attempt IDs, and overflow.
         */
        $review = 0;

        if (isset($_GET['review'])) {
            $submitted_review = $_GET['review'];

            if (!is_scalar($submitted_review)) {
                return '<p>' . esc_html__('Invalid result request.', 'eas-quiz') . '</p>';
            }

            $submitted_review = (string) wp_unslash($submitted_review);

            if (!preg_match('/\A[01]\z/', $submitted_review)) {
                return '<p>' . esc_html__('Invalid result request.', 'eas-quiz') . '</p>';
            }

            $review = (int) $submitted_review;
        }

        if (isset($_GET['attempt_id'])) {
            $submitted_attempt_id = $_GET['attempt_id'];

            if (
                !is_scalar($submitted_attempt_id)
                || !preg_match('/\A[1-9][0-9]*\z/', (string) wp_unslash($submitted_attempt_id))
            ) {
                return '<p>' . esc_html__('Invalid result request.', 'eas-quiz') . '</p>';
            }

            $attempt_id = filter_var(
                (string) wp_unslash($submitted_attempt_id),
                FILTER_VALIDATE_INT,
                array('options' => array('min_range' => 1))
            );

            if ($attempt_id === false) {
                return '<p>' . esc_html__('Invalid result request.', 'eas-quiz') . '</p>';
            }
        }

        if ($review === 1) {
            return $this->results->render();
        }
        $user_id = get_current_user_id();

        $attempts = $this->attempts->all_by_user(
            $user_id
        );

        $attempts = array_filter(
            $attempts,
            static function (array $attempt): bool {
                return (
                    ($attempt['status'] ?? '')
                    === 'completed'
                );
            }
        );

        usort(
            $attempts,
            static function (
                array $a,
                array $b
            ): int {
                return strcmp(
                    (string) (
                        $b['completed_at'] ?? ''
                    ),
                    (string) (
                        $a['completed_at'] ?? ''
                    )
                );
            }
        );

        foreach ($attempts as &$attempt) {
            $quiz = $this->quizzes->find(
                (int) $attempt['quiz_id']
            );

            $attempt['quiz_title'] =
                $quiz['title']
                ?? __('Quiz', 'eas-quiz');

            $attempt['grading_status'] =
                $this->get_grading_status(
                    (int) $attempt['id']
                );

            $attempt['max_points'] =
                $this->get_max_points(
                    (int) $attempt['quiz_id']
                );
        }

        unset($attempt);

        ob_start();
        ?>

        <div class="eas-my-results">

            <h2><?php esc_html_e('My Results', 'eas-quiz'); ?></h2>

            <?php if (empty($attempts)) : ?>

                <p>
                    <?php esc_html_e(
                        'You have not completed any quizzes yet.',
                        'eas-quiz'
                    ); ?>
                </p>

            <?php else : ?>

                <div class="eas-my-results-table-wrapper">

                    <table class="eas-my-results-table">

                        <thead>
                            <tr>
                                <th>
                                    <?php esc_html_e(
                                        'Quiz Name',
                                        'eas-quiz'
                                    ); ?>
                                </th>

                                <th>
                                    <?php esc_html_e(
                                        'Completed Time',
                                        'eas-quiz'
                                    ); ?>
                                </th>

                                <th>
                                    <?php esc_html_e(
                                        'Status',
                                        'eas-quiz'
                                    ); ?>
                                </th>

                                <th>
                                    <?php esc_html_e(
                                        'Score',
                                        'eas-quiz'
                                    ); ?>
                                </th>

                                <th>
                                    <?php esc_html_e(
                                        'Review',
                                        'eas-quiz'
                                    ); ?>
                                </th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php foreach ($attempts as $attempt) : ?>

                            <tr>
                                <td>
                                    <?php echo esc_html(
                                        $attempt['quiz_title']
                                    ); ?>
                                </td>

                                <td>
                                    <?php
                                    if (!empty($attempt['completed_at'])) {
                                        $timestamp = strtotime(
                                            $attempt['completed_at']
                                        );

                                        if ($timestamp !== false) {
                                            echo esc_html(
                                                wp_date(
                                                    get_option('date_format')
                                                    . ' '
                                                    . get_option('time_format'),
                                                    $timestamp
                                                )
                                            );
                                        } else {
                                            echo '—';
                                        }
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    $status =
                                        $attempt['grading_status'];
                                    ?>

                                    <?php if ($status === 'Auto Graded') : ?>

                                        <span class="eas-status eas-status-auto">
                                            <?php esc_html_e(
                                                'Auto Graded',
                                                'eas-quiz'
                                            ); ?>
                                        </span>

                                    <?php elseif ($status === 'Pending Grading') : ?>

                                        <span class="eas-status eas-status-pending">
                                            <?php esc_html_e(
                                                'Pending Grading',
                                                'eas-quiz'
                                            ); ?>
                                        </span>

                                    <?php else : ?>

                                        <span class="eas-status eas-status-graded">
                                            <?php esc_html_e(
                                                'Graded',
                                                'eas-quiz'
                                            ); ?>
                                        </span>

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php
                                    if ($status === 'Pending Grading') {
                                        echo '—';
                                    } else {
                                        $score =
                                            (float) (
                                                $attempt['score']
                                                ?? 0
                                            );

                                        $max_points =
                                            (float) (
                                                $attempt['max_points']
                                                ?? 0
                                            );

                                        echo esc_html(
                                            number_format_i18n(
                                                $score,
                                                2
                                            )
                                        );

                                        if ($max_points > 0) {
                                            echo ' / ';

                                            echo esc_html(
                                                number_format_i18n(
                                                    $max_points,
                                                    2
                                                )
                                            );
                                        }
                                    }
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    $results_url =
                                        $this->get_results_page_url();

                                    if ($results_url !== '') :
                                    ?>

                                        <a
                                            class="button"
                                            href="<?php echo esc_url(
                                                add_query_arg(
                                                    array(
                                                        'attempt_id' =>
                                                            (int) $attempt['id'],
                                                        'review' => 1
                                                    ),
                                                    $results_url
                                                )
                                            ); ?>"
                                        >
                                            <?php esc_html_e(
                                                'Review',
                                                'eas-quiz'
                                            ); ?>
                                        </a>

                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

        <?php

        return ob_get_clean();
    }

    private function get_grading_status(
        int $attempt_id
    ): string {
        $status =
            $this->attempts->grading_status(
                $attempt_id
            );

        if ($status === 'Needs grading') {
            return 'Pending Grading';
        }

        global $wpdb;

        $responses_table =
            $wpdb->prefix . 'eas_responses';

        $items_table =
            $wpdb->prefix . 'eas_items';

        $manual_count =
            $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT COUNT(*)
                    FROM {$responses_table} r
                    INNER JOIN {$items_table} i
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

        if ((int) $manual_count > 0) {
            return 'Graded';
        }

        return 'Auto Graded';
    }

    private function get_max_points(
        int $quiz_id
    ): float {
        global $wpdb;

        $pages_table =
            $wpdb->prefix . 'eas_pages';

        $page_items_table =
            $wpdb->prefix . 'eas_page_items';

        $items_table =
            $wpdb->prefix . 'eas_items';

        $max_points =
            $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT COALESCE(
                        SUM(i.points),
                        0
                    )
                    FROM {$pages_table} p
                    INNER JOIN {$page_items_table} pi
                        ON p.id = pi.page_id
                    INNER JOIN {$items_table} i
                        ON pi.item_id = i.id
                    WHERE p.quiz_id = %d
                    AND i.type != 'description'
                    ",
                    $quiz_id
                )
            );

        return (float) $max_points;
    }

    private function get_results_page_url(): string
    {
        $my_results_page_id =
            (int) get_option(
                'eas_my_results_page_id'
            );

        if ($my_results_page_id <= 0) {
            return '';
        }

        $url = get_permalink(
            $my_results_page_id
        );

        if ($url === false) {
            return '';
        }

        return $url;
    }
}
