<?php
declare(strict_types=1);

namespace EAS\Frontend;

use EAS\Attempt\AttemptRepository;
use EAS\Attempt\AttemptRenderer;
use EAS\Response\ResponseRepository;
use EAS\Question\QuestionRepository;
use EAS\Page\PageRepository;
use EAS\Answer\AnswerRepository;
use EAS\Quiz\QuizRepository;
use EAS\Audio\AudioRepository;

if (!defined('ABSPATH')) {
    exit;
}

class AttemptController
{
    private AttemptRepository $repository;
    private AttemptRenderer $renderer;
    private ResponseRepository $responses;
    private QuestionRepository $questions;
    private PageRepository $pages;
    private AnswerRepository $answers;
    private QuizRepository $quizzes;
    private AudioRepository $audio;

    public function __construct()
    {
        $this->repository = new AttemptRepository();
        $this->renderer = new AttemptRenderer();
        $this->responses = new ResponseRepository();
        $this->questions = new QuestionRepository();
        $this->pages = new PageRepository();
        $this->answers = new AnswerRepository();
        $this->quizzes = new QuizRepository();

        $this->audio = new AudioRepository();
    }

    public function register(): void
    {
        add_action(
            'admin_post_eas_start_quiz',
            array(
                $this,
                'start'
            )
        );

        add_action(
            'admin_post_eas_next_page',
            array(
                $this,
                'next_page'
            )
        );

        add_action(
            'admin_post_eas_previous_page',
            array(
                $this,
                'previous_page'
            )
        );

        add_action(
            'admin_post_eas_upload_audio',
            array(
                $this,
                'upload_audio'
            )
        );

        add_action(
            'admin_post_nopriv_eas_start_quiz',
            array(
                $this,
                'not_logged_in'
            )
        );

        add_action(
            'admin_post_eas_submit_attempt',
            array(
                $this,
                'submit'
            )
        );

        add_shortcode(
            'eas_attempt',
            array(
                $this,
                'attempt_page'
            )
        );
    }

    public function start(): void
    {
        check_admin_referer(
            'eas_start_quiz'
        );

        if (!is_user_logged_in()) {
            wp_die(
                esc_html__(
                    'You must be logged in.',
                    'eas-quiz'
                )
            );
        }

        $quiz_id = (int) (
            $_POST['quiz_id'] ?? 0
        );

        if ($quiz_id <= 0) {
            wp_die(
                esc_html__(
                    'Invalid quiz.',
                    'eas-quiz'
                )
            );
        }

        $quiz = $this->quizzes->find(
            $quiz_id
        );

        if (!$quiz) {
            wp_die(
                esc_html__(
                    'Quiz not found.',
                    'eas-quiz'
                )
            );
        }

        /*
         * Check attempts allowed.
         *
         * Administrators are exempt from the attempt limit
         * so they can preview and test quizzes repeatedly.
         *
         * Students remain subject to the configured
         * attempts_allowed limit.
         */
        if (!current_user_can('manage_options')) {

            $allowed = (int) $quiz['attempts_allowed'];

            if ($allowed > 0) {

                $completed = $this->repository->count_completed_attempts(
                    get_current_user_id(),
                    $quiz_id
                );

                if ($completed >= $allowed) {

                    wp_die(
                        esc_html__(
                            'You have reached the maximum number of attempts allowed for this quiz.',
                            'eas-quiz'
                        )
                    );
                }
            }
        }

        $attempt_id = $this->repository->create(
            $quiz_id,
            get_current_user_id()
        );

        $attempt_page_id = (int) get_option(
            'eas_attempt_page_id'
        );

        if ($attempt_page_id <= 0) {
            wp_die(
                esc_html__(
                    'Attempt page not found.',
                    'eas-quiz'
                )
            );
        }

        wp_redirect(
            add_query_arg(
                array(
                    'attempt_id' => $attempt_id
                ),
                get_permalink($attempt_page_id)
            )
        );

        exit;
    }

    public function attempt_page(): string
    {
        $attempt_id = (int) (
            $_GET['attempt_id'] ?? 0
        );

        if ($attempt_id <= 0) {
            return '<p>' .
                esc_html__(
                    'No attempt selected.',
                    'eas-quiz'
                ) .
                '</p>';
        }

        return $this->renderer->render(
            $attempt_id
        );
    }

    public function previous_page(): void
    {
        check_admin_referer(
            'eas_previous_page'
        );

        $attempt_id = (int) (
            $_POST['attempt_id'] ?? 0
        );

        if ($attempt_id <= 0) {
            wp_die(
                esc_html__(
                    'Invalid attempt.',
                    'eas-quiz'
                )
            );
        }

        $attempt = $this->repository->find(
            $attempt_id
        );

        if (!$attempt) {
            wp_die(
                esc_html__(
                    'Attempt not found.',
                    'eas-quiz'
                )
            );
        }

        $quiz = $this->quizzes->find(
            (int) $attempt['quiz_id']
        );

        if (!$quiz) {
            wp_die(
                esc_html__(
                    'Quiz not found.',
                    'eas-quiz'
                )
            );
        }

        /*
         * Backward navigation must be enabled.
         */
        if (
            empty($quiz['backward_navigation'])
        ) {
            wp_die(
                esc_html__(
                    'Backward navigation is not allowed.',
                    'eas-quiz'
                )
            );
        }

        /*
         * Save the answers on the current page
         * before moving backward.
         */
        $this->save_current_page_responses(
            $attempt_id,
            (int) $attempt['current_page_id']
        );

        $current_page = $this->pages->find(
            (int) $attempt['current_page_id']
        );

        if (!$current_page) {
            wp_die(
                esc_html__(
                    'Current page not found.',
                    'eas-quiz'
                )
            );
        }

        /*
         * Find the previous page.
         */
        $previous_page = $this->pages->previousByQuiz(
            (int) $attempt['quiz_id'],
            (int) $current_page['page_number']
        );

        if (!$previous_page) {
            wp_redirect(
                add_query_arg(
                    array(
                        'attempt_id' => $attempt_id
                    ),
                    get_permalink(
                        (int) get_option(
                            'eas_attempt_page_id'
                        )
                    )
                )
            );

            exit;
        }

        $this->repository->update_current_page(
            $attempt_id,
            (int) $previous_page['id']
        );

        wp_redirect(
            add_query_arg(
                array(
                    'attempt_id' => $attempt_id
                ),
                get_permalink(
                    (int) get_option(
                        'eas_attempt_page_id'
                    )
                )
            )
        );

        exit;
    }

    public function next_page(): void
    {
        check_admin_referer(
            'eas_next_page'
        );

        $attempt_id = (int) (
            $_POST['attempt_id'] ?? 0
        );

        if ($attempt_id <= 0) {
            wp_die(
                esc_html__(
                    'Invalid attempt.',
                    'eas-quiz'
                )
            );
        }

        $attempt = $this->repository->find(
            $attempt_id
        );

        if (!$attempt) {
            wp_die(
                esc_html__(
                    'Attempt not found.',
                    'eas-quiz'
                )
            );
        }

        $this->save_current_page_responses(
            $attempt_id,
            (int) $attempt['current_page_id']
        );

        $current_page = $this->pages->find(
            (int) $attempt['current_page_id']
        );

        if (!$current_page) {
            wp_die(
                esc_html__(
                    'Current page not found.',
                    'eas-quiz'
                )
            );
        }

        $next_page = $this->pages->nextByQuiz(
            (int) $attempt['quiz_id'],
            (int) $current_page['page_number']
        );

        if (!$next_page) {
            wp_redirect(
                add_query_arg(
                    array(
                        'attempt_id' => $attempt_id
                    ),
                    get_permalink(
                        (int) get_option(
                            'eas_attempt_page_id'
                        )
                    )
                )
            );

            exit;
        }

        $this->repository->update_current_page(
            $attempt_id,
            (int) $next_page['id']
        );

        wp_redirect(
            add_query_arg(
                array(
                    'attempt_id' => $attempt_id
                ),
                get_permalink(
                    (int) get_option(
                        'eas_attempt_page_id'
                    )
                )
            )
        );

        exit;
    }

    public function submit(): void
    {
        check_admin_referer(
            'eas_submit_attempt'
        );

        $attempt_id = (int) (
            $_POST['attempt_id'] ?? 0
        );

        if ($attempt_id <= 0) {
            wp_die(
                esc_html__(
                    'Invalid attempt.',
                    'eas-quiz'
                )
            );
        }

        $attempt = $this->repository->find(
            $attempt_id
        );

        if (!$attempt) {
            wp_die(
                esc_html__(
                    'Attempt not found.',
                    'eas-quiz'
                )
            );
        }

        $this->save_current_page_responses(
            $attempt_id,
            (int) $attempt['current_page_id']
        );

        $pages = $this->pages->all_by_quiz(
            (int) $attempt['quiz_id']
        );

        if (empty($pages)) {
            wp_die(
                esc_html__(
                    'No pages found.',
                    'eas-quiz'
                )
            );
        }

        $score = 0;

        $pages = $this->pages->all_by_quiz(
            (int) $attempt['quiz_id']
        );

        foreach ($pages as $page) {

            $page_questions = $this->questions->all_by_page(
                (int) $page['id']
            );

            foreach ($page_questions as $question) {

                $question_id = (int) $question['id'];

                $response = $this->responses->find(
                    $attempt_id,
                    $question_id
                );

                $submitted = $response['answer'] ?? '';

                $points = 0;

                if ($question['type'] === 'multiple_choice') {

                    $answers = $this->answers->all_by_question(
                        $question_id
                    );

                    foreach ($answers as $answer) {

                        if (
                            (string) $answer['id'] === (string) $submitted
                            &&
                            (int) $answer['is_correct'] === 1
                        ) {

                            $points = (float) $question['points'];

                            $score += $points;

                            $this->responses->update_points(
                                $attempt_id,
                                $question_id,
                                $points
                            );

                            break;
                        }
                    }
                } elseif ($question['type'] === 'drag_drop_text') {

                    /*
                     * Drag-and-Drop scoring:
                     *
                     * All blanks must be correct.
                     * No partial credit.
                     */

                    $answers = $this->answers->all_by_question(
                        $question_id
                    );

                    $correct_answers = array();

                    foreach ($answers as $answer) {

                        if (
                            (int) $answer['correct_position'] > 0
                        ) {

                            $correct_answers[
                                (int) $answer['correct_position']
                            ] = trim(
                                $answer['answer_text']
                            );
                        }
                    }

                    /*
                     * Student answers are stored as JSON
                     */
                    $all_correct = true;

                    if (
                        !is_array($student_answers)
                    ) {

                        $all_correct = false;

                    } else {

                        foreach (
                            $correct_answers as $position => $correct
                        ) {

                            $student = trim(
                                $student_answers[$position] ?? ''
                            );

                            if (
                                strcasecmp(
                                    $student,
                                    $correct
                                ) !== 0
                            ) {

                                $all_correct = false;

                                break;
                            }
                        }
                    }

                    error_log(
                        print_r(
                            $correct_answers,
                            true
                        )
                    );

                    error_log(
                        print_r(
                            $student_answers,
                            true
                        )
                    );

                    if ($all_correct) {

                        $points = (float) $question['points'];

                        $score += $points;

                        $this->responses->update_points(
                            $attempt_id,
                            $question_id,
                            $points
                        );
                    }
                } elseif ($question['type'] === 'fill_blank') {

                    /*
                     * Fill Blank scoring:
                     *
                     * Each blank is worth an equal share of the
                     * question's total point value.
                     *
                     * Example:
                     *   Question = 10 points
                     *   10 blanks
                     *   8 correct
                     *
                     *   10 × (8 / 10) = 8 points
                     *
                     * This works for any question point value.
                     */

                    $answers = $this->answers->all_by_question(
                        $question_id
                    );

                    /*
                     * Build the correct-answer map.
                     *
                     * Example:
                     * {
                     *   1: "der",
                     *   2: "the",
                     *   3: "meeting"
                     * }
                     */

                    $correct_answers = array();

                    foreach ($answers as $answer) {

                        $position = (int) (
                            $answer['correct_position'] ?? 0
                        );

                        if ($position > 0) {

                            $correct_answers[$position] =
                                trim(
                                    (string) (
                                        $answer['answer_text'] ?? ''
                                    )
                                );
                        }
                    }

                    /*
                     * Decode the student's submitted answers.
                     */

                    $submitted = stripslashes(
                        $submitted
                    );

                    $student_answers = json_decode(
                        $submitted,
                        true
                    );

                    error_log(
                        'FILL BLANK CORRECT ANSWERS:'
                    );

                    error_log(
                        print_r(
                            $correct_answers,
                            true
                        )
                    );

                    error_log(
                        'FILL BLANK STUDENT ANSWERS:'
                    );

                    error_log(
                        print_r(
                            $student_answers,
                            true
                        )
                    );

                    /*
                     * Number of blanks.
                     */

                    $total_blanks = count(
                        $correct_answers
                    );

                    /*
                     * Number of correctly answered blanks.
                     */

                    $correct_count = 0;

                    if (
                        $total_blanks > 0
                        &&
                        is_array($student_answers)
                    ) {

                        foreach (
                            $correct_answers
                            as $position => $correct
                        ) {

                            $student = trim(
                                (string) (
                                    $student_answers[$position]
                                    ?? ''
                                )
                            );

                            /*
                             * Case-insensitive comparison.
                             */

                            if (
                                strcasecmp(
                                    $student,
                                    $correct
                                ) === 0
                            ) {

                                $correct_count++;
                            }
                        }
                    }

                    /*
                     * Calculate proportional score.
                     *
                     * Example:
                     *
                     * Question = 7 points
                     * 8 out of 10 correct
                     *
                     * 7 × 8 / 10 = 5.6
                     */

                    if ($total_blanks > 0) {

                        $question_points =
                            (float) $question['points'];

                        $points =
                            $question_points
                            * (
                                $correct_count
                                / $total_blanks
                            );

                    } else {

                        $points = 0;
                    }

                    /*
                     * Add this question's partial score
                     * to the overall quiz score.
                     */

                    $score += $points;

                    /*
                     * Save the points earned for this
                     * individual question.
                     */

                    $this->responses->update_points(
                        $attempt_id,
                        $question_id,
                        $points
                    );
                }
            }
        }

        $this->repository->complete(
            $attempt_id,
            $score
        );

        $result_page_id = (int) get_option(
            'eas_results_page_id'
        );

        if ($result_page_id <= 0) {
            wp_die(
                esc_html__(
                    'Result page not found.',
                    'eas-quiz'
                )
            );
        }

        wp_redirect(
            add_query_arg(
                array(
                    'attempt_id' => $attempt_id
                ),
                get_permalink($result_page_id)
            )
        );

        exit;
    }

    private function save_current_page_responses(
        int $attempt_id,
        int $page_id
    ): void {

        $questions = $this->questions->all_by_page(
            $page_id
        );

        foreach ($questions as $question) {

            $question_id = (int) $question['id'];

            $answer = (string) (
                $_POST[
                    'question_' . $question_id
                ] ?? ''
            );
            $existing = $this->responses->find(
                $attempt_id,
                $question_id
            );

            if ($existing) {

                $this->responses->update_answer(
                    $attempt_id,
                    $question_id,
                    $answer
                );

                $response_id = (int) $existing['id'];

            } else {

                $response_id = $this->responses->create(
                    $attempt_id,
                    $question_id,
                    $answer,
                    0
                );
            }

            if (
                $question['type'] === 'audio_response'
                &&
                !empty($answer)
            ) {

                $this->audio->create(
                    $response_id,
                    $answer
                );
            }
        }
    }

    public function upload_audio(): void
    {
        check_admin_referer(
            'eas_upload_audio',
            'nonce'
        );

        if (empty($_FILES['audio'])) {

            wp_send_json_error(
                array(
                    'message' => __(
                        'No audio uploaded.',
                        'eas-quiz'
                    )
                )
            );
        }

        $upload = wp_upload_dir();

        $directory =
            trailingslashit($upload['basedir'])
            . 'eas-audio';

        if (!file_exists($directory)) {
            wp_mkdir_p($directory);
        }

        $filename =
            uniqid('audio_', true)
            . '.webm';

        $destination =
            trailingslashit($directory)
            . $filename;

        if (
            !move_uploaded_file(
                $_FILES['audio']['tmp_name'],
                $destination
            )
        ) {

            wp_send_json_error(
                array(
                    'message' => __(
                        'Upload failed.',
                        'eas-quiz'
                    )
                )
            );
        }

        wp_send_json_success(
            array(
                'file' => $filename,
                'url'  =>
                    trailingslashit($upload['baseurl'])
                    . 'eas-audio/'
                    . $filename
            )
        );
    }

    public function not_logged_in(): void
    {
        wp_die(
            esc_html__(
                'Please log in.',
                'eas-quiz'
            )
        );
    }
}
