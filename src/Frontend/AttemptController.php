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
            'admin_post_eas_go_to_page',
            array(
                $this,
                'go_to_page'
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

        $quiz_id = $this->validated_request_id($_POST, 'quiz_id');

        if ($quiz_id === null) {
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

        wp_safe_redirect(
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
        $attempt_id = $this->validated_request_id($_GET, 'attempt_id');

        if ($attempt_id === null) {
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

        $attempt_id = $this->validated_request_id($_POST, 'attempt_id');

        if ($attempt_id === null) {
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

        $this->ensure_attempt_access($attempt);

        $this->ensure_attempt_in_progress($attempt);

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
            wp_safe_redirect(
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

        wp_safe_redirect(
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

    public function go_to_page(): void
    {
        check_admin_referer('eas_go_to_page');

        $attempt_id = $this->validated_request_id($_POST, 'attempt_id');
        $target_page_id = $this->validated_request_id($_POST, 'go_to_page_id');

        if ($attempt_id === null || $target_page_id === null) {
            wp_die(esc_html__('Invalid page navigation request.', 'eas-quiz'));
        }

        $attempt = $this->repository->find($attempt_id);
        if (!$attempt) {
            wp_die(esc_html__('Attempt not found.', 'eas-quiz'));
        }

        $this->ensure_attempt_access($attempt);

        $this->ensure_attempt_in_progress($attempt);

        $quiz = $this->quizzes->find((int) $attempt['quiz_id']);
        if (!$quiz) {
            wp_die(esc_html__('Quiz not found.', 'eas-quiz'));
        }

        if (empty($quiz['backward_navigation'])) {
            wp_die(esc_html__('Backward navigation is not allowed.', 'eas-quiz'));
        }

        $target_page = $this->pages->find($target_page_id);
        if (!$target_page || (int) $target_page['quiz_id'] !== (int) $attempt['quiz_id']) {
            wp_die(esc_html__('Invalid target page.', 'eas-quiz'));
        }

        $this->save_current_page_responses(
            $attempt_id,
            (int) $attempt['current_page_id']
        );

        $this->repository->update_current_page(
            $attempt_id,
            $target_page_id
        );

        wp_safe_redirect(
            add_query_arg(
                array('attempt_id' => $attempt_id),
                get_permalink((int) get_option('eas_attempt_page_id'))
            )
        );
        exit;
    }

    public function next_page(): void
    {
        check_admin_referer(
            'eas_next_page'
        );

        $attempt_id = $this->validated_request_id($_POST, 'attempt_id');

        if ($attempt_id === null) {
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

        $this->ensure_attempt_access($attempt);

        $this->ensure_attempt_in_progress($attempt);

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
            wp_safe_redirect(
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

        wp_safe_redirect(
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

        $attempt_id = $this->validated_request_id($_POST, 'attempt_id');

        if ($attempt_id === null) {
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

        $this->ensure_attempt_access($attempt);

        $this->ensure_attempt_in_progress($attempt);

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

                    /*
                     * Multiple Choice responses store the database ID
                     * of the selected answer. Resolve that exact answer
                     * record and verify that it belongs to this question
                     * and is marked as correct.
                     */
                    $submitted_answer_id = absint($submitted);

                    if ($submitted_answer_id > 0) {

                        $selected_answer = $this->answers->find(
                            $submitted_answer_id
                        );

                        if (
                            $selected_answer
                            &&
                            (int) ($selected_answer['question_id'] ?? 0)
                                === $question_id
                            &&
                            (int) ($selected_answer['is_correct'] ?? 0)
                                === 1
                        ) {

                            $points = (float) $question['points'];

                            $score += $points;

                            $this->responses->update_points(
                                $attempt_id,
                                $question_id,
                                $points
                            );
                        }
                    }
                } elseif ($question['type'] === 'drag_drop_text') {

                    /*
                     * Drag-and-Drop scoring:
                     *
                     * Each blank is worth an equal share of the
                     * question's total point value.
                     *
                     * Example:
                     *   Question = 5 points
                     *   2 blanks
                     *   2 correct = 5 points
                     *   1 correct = 2.5 points
                     *   0 correct = 0 points
                     *
                     * Extra distractor words do not affect scoring.
                     */

                    $answers = $this->answers->all_by_question(
                        $question_id
                    );

                    $correct_answers = array();

                    foreach ($answers as $answer) {

                        $position = (int) (
                            $answer['correct_position'] ?? 0
                        );

                        if ($position > 0) {

                            $correct_answers[$position] = trim(
                                (string) (
                                    $answer['answer_text'] ?? ''
                                )
                            );
                        }
                    }

                    /*
                     * Student answers are stored as JSON.
                     */
                    $student_answers = json_decode(
                        stripslashes($submitted),
                        true
                    );

                    /*
                     * Count the correctly answered blanks.
                     */
                    $total_blanks = count(
                        $correct_answers
                    );

                    $correct_count = 0;

                    if (
                        $total_blanks > 0
                        &&
                        is_array($student_answers)
                    ) {

                        foreach (
                            $correct_answers as $position => $correct
                        ) {

                            $student = trim(
                                (string) (
                                    $student_answers[$position]
                                    ?? ''
                                )
                            );

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
                     * Award a proportional share of the question points
                     * for each correctly completed blank.
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

                    $score += $points;

                    $this->responses->update_points(
                        $attempt_id,
                        $question_id,
                        $points
                    );

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

        $my_results_page_id = (int) get_option(
    'eas_my_results_page_id'
);

if ($my_results_page_id <= 0) {
    $my_results_page = get_page_by_path(
        'my-results',
        OBJECT,
        'page'
    );

    if ($my_results_page instanceof \WP_Post) {
        $my_results_page_id = (int) $my_results_page->ID;

        update_option(
            'eas_my_results_page_id',
            $my_results_page_id
        );
    }
}

if ($my_results_page_id <= 0) {
    wp_die(
        esc_html__(
            'My Results page not found.',
            'eas-quiz'
        )
    );
}

$my_results_page_url = get_permalink(
    $my_results_page_id
);

if ($my_results_page_url === false) {
    wp_die(
        esc_html__(
            'Unable to load the My Results page.',
            'eas-quiz'
        )
    );
}

wp_safe_redirect(
    add_query_arg(
        array(
            'attempt_id' => $attempt_id
        ),
        $my_results_page_url
    )
);

exit;
    }

    /**
     * Ensure a frontend request can only modify the current user's attempt.
     */
    private function ensure_attempt_access(array $attempt): void
    {
        if (
            !is_user_logged_in()
            ||
            (int) ($attempt['user_id'] ?? 0) !== get_current_user_id()
        ) {
            wp_die(
                esc_html__(
                    'You are not allowed to access this quiz attempt.',
                    'eas-quiz'
                ),
                '',
                array('response' => 403)
            );
        }
    }

    /**
     * Validate a request ID without allowing PHP casts to reinterpret malformed input.
     * Only canonical positive decimal integers within PHP's integer range are accepted.
     */
    private function validated_request_id(array $source, string $key): ?int
    {
        if (!array_key_exists($key, $source) || !is_scalar($source[$key])) {
            return null;
        }

        $value = (string) wp_unslash($source[$key]);

        if (!preg_match('/\A[1-9][0-9]*\z/', $value)) {
            return null;
        }

        $id = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            array('options' => array('min_range' => 1))
        );

        return $id === false ? null : $id;
    }

    /**
     * Prevent a completed attempt from being changed by replaying an old request.
     */
    private function ensure_attempt_in_progress(array $attempt): void
    {
        if (($attempt['status'] ?? '') !== 'in_progress') {
            wp_die(
                esc_html__(
                    'This quiz attempt is no longer active.',
                    'eas-quiz'
                ),
                '',
                array('response' => 403)
            );
        }
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

            $submitted_answer = $_POST[
                'question_' . $question_id
            ] ?? '';

            // Answers may contain plain text or structured JSON, so preserve
            // their content while rejecting malformed non-scalar request data.
            if (!is_scalar($submitted_answer)) {
                wp_die(
                    esc_html__(
                        'Invalid answer format.',
                        'eas-quiz'
                    ),
                    '',
                    array('response' => 400)
                );
            }

            $answer = (string) wp_unslash($submitted_answer);
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
        if (
            !isset($_POST['nonce'])
            || !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['nonce'])),
                'eas_upload_audio'
            )
        ) {
            wp_send_json_error(
                array('message' => __('Invalid upload security token.', 'eas-quiz')),
                403
            );
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(
                array('message' => __('You must be logged in to upload audio.', 'eas-quiz')),
                403
            );
        }

        $attempt_id = $this->validated_request_id($_POST, 'attempt_id');
        $question_id = $this->validated_request_id($_POST, 'question_id');

        if ($attempt_id === null || $question_id === null) {
            wp_send_json_error(
                array('message' => __('Invalid quiz attempt or question.', 'eas-quiz')),
                400
            );
        }

        $attempt = $this->repository->find($attempt_id);

        if (
            !$attempt
            || (int) ($attempt['user_id'] ?? 0) !== get_current_user_id()
        ) {
            wp_send_json_error(
                array('message' => __('You are not allowed to upload audio for this attempt.', 'eas-quiz')),
                403
            );
        }

        if (($attempt['status'] ?? '') !== 'in_progress') {
            wp_send_json_error(
                array('message' => __('This quiz attempt is no longer active.', 'eas-quiz')),
                403
            );
        }

        $question_is_on_current_page = false;

        foreach (
            $this->questions->all_by_page((int) ($attempt['current_page_id'] ?? 0))
            as $page_question
        ) {
            if (
                (int) ($page_question['id'] ?? 0) === $question_id
                && ($page_question['type'] ?? '') === 'audio_response'
            ) {
                $question_is_on_current_page = true;
                break;
            }
        }

        $question = $this->questions->find($question_id);

        if (
            !$question_is_on_current_page
            || !$question
            || (int) ($question['quiz_id'] ?? 0) !== (int) ($attempt['quiz_id'] ?? 0)
            || ($question['type'] ?? '') !== 'audio_response'
        ) {
            wp_send_json_error(
                array('message' => __('The audio question does not belong to the active quiz page.', 'eas-quiz')),
                403
            );
        }

        if (
            !isset($_FILES['audio'])
            || !is_array($_FILES['audio'])
        ) {
            wp_send_json_error(
                array('message' => __('No audio uploaded.', 'eas-quiz')),
                400
            );
        }

        $file = $_FILES['audio'];

        if (
            !isset($file['error'], $file['tmp_name'], $file['size'])
            || (int) $file['error'] !== UPLOAD_ERR_OK
            || !is_uploaded_file((string) $file['tmp_name'])
        ) {
            wp_send_json_error(
                array('message' => __('The audio upload did not complete successfully.', 'eas-quiz')),
                400
            );
        }

        // Audio-only WebM recordings should remain comfortably below this limit.
        $max_bytes = 20 * MB_IN_BYTES;

        if (
            (int) $file['size'] <= 0
            || (int) $file['size'] > $max_bytes
            || filesize((string) $file['tmp_name']) > $max_bytes
        ) {
            wp_send_json_error(
                array('message' => __('The audio file is empty or exceeds the 20 MB upload limit.', 'eas-quiz')),
                413
            );
        }

        // Verify the WebM/Matroska container signature; never trust the supplied filename.
        $handle = fopen((string) $file['tmp_name'], 'rb');
        $signature = $handle !== false ? fread($handle, 4) : false;
        if ($handle !== false) {
            fclose($handle);
        }

        if ($signature !== "\x1A\x45\xDF\xA3") {
            wp_send_json_error(
                array('message' => __('The uploaded file is not a valid WebM audio recording.', 'eas-quiz')),
                415
            );
        }

        $upload = wp_upload_dir();

        if (!empty($upload['error']) || empty($upload['basedir']) || empty($upload['baseurl'])) {
            wp_send_json_error(
                array('message' => __('The upload directory is unavailable.', 'eas-quiz')),
                500
            );
        }

        // Keep recordings in the plugin's dedicated uploads subdirectory.
        $upload_dir_filter = static function (array $uploads): array {
            $uploads['path'] = trailingslashit($uploads['basedir']) . 'eas-audio';
            $uploads['url'] = trailingslashit($uploads['baseurl']) . 'eas-audio';
            $uploads['subdir'] = '/eas-audio';

            return $uploads;
        };

        add_filter('upload_dir', $upload_dir_filter);

        // wp_handle_upload() takes its file argument by reference, so pass a variable.
        $upload_file = array_merge(
            $file,
            array(
                'name' => 'recording.webm',
            )
        );

        try {
            $uploaded = wp_handle_upload(
                $upload_file,
                array(
                    'test_form' => false,
                    // The WebM signature was verified above. Hosts may identify WebM as
                    // audio/webm, video/webm, or application/octet-stream.
                    'mimes' => array(
                        'webm' => 'audio/webm|video/webm|application/octet-stream',
                    ),
                    'unique_filename_callback' => static function (
                        string $directory,
                        string $name,
                        string $extension
                    ): string {
                        return wp_unique_filename(
                            $directory,
                            'audio_' . wp_generate_password(32, false, false) . $extension
                        );
                    },
                )
            );
        } finally {
            remove_filter('upload_dir', $upload_dir_filter);
        }

        if (
            !is_array($uploaded)
            || !empty($uploaded['error'])
            || empty($uploaded['file'])
            || empty($uploaded['url'])
        ) {
            if (is_array($uploaded) && !empty($uploaded['error'])) {
                error_log('EAS audio upload failed: ' . sanitize_text_field($uploaded['error']));
            }

            wp_send_json_error(
                array(
                    'message' => __('Upload failed. Please check the server log for details.', 'eas-quiz'),
                ),
                500
            );
        }

        $filename = basename($uploaded['file']);

        wp_send_json_success(
            array(
                'file' => $filename,
                'url' => $uploaded['url'],
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
