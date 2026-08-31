<?php
declare(strict_types=1);

namespace EAS\Result;

use EAS\Answer\AnswerRepository;
use EAS\Question\QuestionRepository;
use EAS\Page\PageRepository;

if (!defined('ABSPATH')) {
    exit;
}

class ResultRenderer
{
    private ResultRepository $repository;
    private AnswerRepository $answers;
    private QuestionRepository $questions;
    private PageRepository $pages;

    public function __construct()
    {
        $this->repository = new ResultRepository();
        $this->answers = new AnswerRepository();
        $this->questions = new QuestionRepository();
        $this->pages = new PageRepository();
    }

    /*
     * =========================================================
     * REVIEW PAGE
     * =========================================================
     */
    public function render(int $attempt_id): string
    {
        $attempt = $this->repository->get_attempt(
            $attempt_id
        );

        if (!$attempt) {
            return '<p>Attempt not found.</p>';
        }

        $responses = $this->repository->get_responses(
            $attempt_id
        );

        /*
         * =====================================================
         * PROCESS RESPONSES
         * =====================================================
         */
        foreach ($responses as &$response) {

            $type = $response['type'] ?? '';

            /*
             * -------------------------------------------------
             * FILL-IN-BLANK
             * -------------------------------------------------
             *
             * Responses can be stored as:
             *
             * [null,"ment","icy"]
             *
             * or:
             *
             * {"1":"ment","2":"icy"}
             *
             * Position 0 is not a real answer.
             */
            if ($type === 'fill_blank') {

                $raw_answer = (string) (
                    $response['answer'] ?? ''
                );

                $student_answers = json_decode(
                    stripslashes($raw_answer),
                    true
                );

                if (!is_array($student_answers)) {
                    $student_answers = array();
                }

                /*
                 * Remove the unused position 0.
                 */
                unset($student_answers[0]);

                /*
                 * Remove empty/null answers.
                 */
                foreach (
                    $student_answers as $position => $answer
                ) {
                    if (
                        $answer === null
                        ||
                        trim((string) $answer) === ''
                    ) {
                        unset(
                            $student_answers[$position]
                        );
                    }
                }

                /*
                 * Keep answers in blank order.
                 */
                ksort($student_answers);

                /*
                 * Example:
                 *
                 * [1] => ment
                 * [2] => icy
                 *
                 * becomes:
                 *
                 * ment icy
                 */
                $response['student_answer'] =
                    implode(
                        ' ',
                        array_map(
                            'strval',
                            $student_answers
                        )
                    );
            }

            /*
             * -------------------------------------------------
             * DRAG-DROP TEXT
             * -------------------------------------------------
             */
            elseif ($type === 'drag_drop_text') {

                $raw_answer = (string) (
                    $response['answer'] ?? ''
                );

                $student_answers = json_decode(
                    stripslashes($raw_answer),
                    true
                );

                if (!is_array($student_answers)) {
                    $student_answers = array();
                }

                /*
                 * Remove unused position 0.
                 */
                unset($student_answers[0]);

                /*
                 * Remove empty/null values.
                 */
                foreach (
                    $student_answers as $position => $word
                ) {
                    if (
                        $word === null
                        ||
                        trim((string) $word) === ''
                    ) {
                        unset(
                            $student_answers[$position]
                        );
                    }
                }

                ksort($student_answers);

                /*
                 * Display student's words.
                 */
                $response['student_answer'] =
                    implode(
                        ' ',
                        array_map(
                            'strval',
                            $student_answers
                        )
                    );

                /*
                 * Reconstruct the question text.
                 *
                 * [[1]] -> word
                 * [[2]] -> word
                 */
                foreach (
                    $student_answers as $position => $word
                ) {
                    $response['question_text'] =
                        str_replace(
                            '[[' . $position . ']]',
                            (string) $word,
                            $response['question_text']
                        );
                }

                /*
                 * Get correct answers.
                 */
                $correct_answers =
                    $this->get_correct_answers(
                        (int) $response['item_id']
                    );

                if (!empty($correct_answers)) {

                    ksort($correct_answers);

                    $response['correct_answer'] =
                        implode(
                            ' ',
                            array_map(
                                'strval',
                                $correct_answers
                            )
                        );
                }
            }

            /*
             * -------------------------------------------------
             * NORMAL ANSWERS
             * -------------------------------------------------
             */
            else {

                $response['student_answer'] =
                    $response['answer'] ?? '';
            }
        }

        unset($response);

        /*
         * =====================================================
         * ORGANIZE RESPONSES BY PAGE
         * =====================================================
         */
        $pages = $this->pages->all_by_quiz(
            (int) $attempt['quiz_id']
        );

        $responses_by_question = array();

        foreach ($responses as $response) {

            $responses_by_question[
                (int) $response['item_id']
            ] = $response;
        }

        $page_responses = array();

        foreach ($pages as $page) {

            $questions = $this->questions->all_by_page(
                (int) $page['id']
            );

            $page_items = array();

            foreach ($questions as $question) {

    $question_id =
        (int) $question['id'];

    if (
        isset(
            $responses_by_question[
                $question_id
            ]
        )
    ) {

        $response =
            $responses_by_question[
                $question_id
            ];

        /*
         * Add the question's General Feedback
         * to the response so that the review
         * view can display it.
         */
        $response['general_feedback'] =
            $question['feedback'] ?? '';

        $page_items[] =
            $response;
    }
}

            $page_responses[] = array(
                'page' => $page,
                'responses' => $page_items
            );
        }

        /*
         * =====================================================
         * RENDER REVIEW VIEW
         * =====================================================
         */
        ob_start();

        require EAS_PLUGIN_DIR .
            'views/frontend/result/page.php';

        return ob_get_clean();
    }

    /*
     * =========================================================
     * GET CORRECT ANSWERS
     * =========================================================
     */
    private function get_correct_answers(
        int $question_id
    ): array {

        $answers = $this->answers->all_by_question(
            $question_id
        );

        $correct = array();

        foreach ($answers as $answer) {

            $position = (int) (
                $answer['correct_position'] ?? 0
            );

            if ($position > 0) {

                $correct[$position] =
                    (string) (
                        $answer['answer_text'] ?? ''
                    );
            }
        }

        return $correct;
    }

    /*
     * =========================================================
     * RESULT SUMMARY
     * =========================================================
     *
     * This method deliberately contains no ?> / <?php
     * transitions. This avoids PHP parser problems caused by
     * mixing PHP and HTML inside this class.
     */
    public function render_summary(
        int $attempt_id
    ): string {

        $attempts =
            new \EAS\Attempt\AttemptRepository();

        $attempt = $attempts->find(
            $attempt_id
        );

        if (!$attempt) {
            return '<p>Attempt not found.</p>';
        }

        /*
         * Get quiz title.
         */
        $quizzes =
            new \EAS\Quiz\QuizRepository();

        $quiz = $quizzes->find(
            (int) $attempt['quiz_id']
        );

        $quiz_title =
            $quiz['title'] ?? 'Quiz';

        /*
         * Calculate maximum possible points.
         */
        $questions =
            new \EAS\Question\QuestionRepository();

        $pages =
            new \EAS\Page\PageRepository();

        $quiz_pages =
            $pages->all_by_quiz(
                (int) $attempt['quiz_id']
            );

        $max_points = 0;

        foreach ($quiz_pages as $page) {

            $page_questions =
                $questions->all_by_page(
                    (int) $page['id']
                );

            foreach ($page_questions as $question) {

                if (
                    ($question['type'] ?? '')
                    === 'description'
                ) {
                    continue;
                }

                $max_points +=
                    (float) (
                        $question['points'] ?? 0
                    );
            }
        }

        /*
         * Calculate time spent.
         */
        $time_spent = 0;

        if (
            !empty($attempt['started_at'])
            &&
            !empty($attempt['completed_at'])
        ) {

            $start = strtotime(
                $attempt['started_at']
            );

            $end = strtotime(
                $attempt['completed_at']
            );

            if (
                $start !== false
                &&
                $end !== false
                &&
                $end >= $start
            ) {
                $time_spent =
                    $end - $start;
            }
        }

        $minutes = floor(
            $time_spent / 60
        );

        $seconds =
            $time_spent % 60;

        /*
         * =====================================================
         * BUILD SUMMARY HTML
         * =====================================================
         */
        $html = '';

        $html .= '<div class="eas-result-summary">';

        $html .= '<h2>';
        $html .= esc_html($quiz_title);
        $html .= '</h2>';

        $html .= '<div class="eas-summary-score">';
        $html .= '<strong>Total Score:</strong> ';

        $html .= esc_html(
            number_format(
                (float) $attempt['score'],
                2
            )
        );

        $html .= ' / ';

        $html .= esc_html(
            number_format(
                $max_points,
                2
            )
        );

        $html .= '</div>';

        $html .= '<div class="eas-summary-time">';
        $html .= '<strong>Time Spent:</strong> ';

        $html .= esc_html(
            $minutes
        );

        $html .= ' minutes';

        if ($seconds > 0) {

            $html .= ' ';

            $html .= esc_html(
                $seconds
            );

            $html .= ' seconds';
        }

        $html .= '</div>';

        /*
         * Review button.
         */
        $review_url = add_query_arg(
            array(
                'attempt_id' => $attempt_id,
                'review' => 1
            ),
            get_permalink()
        );

        $html .= '<p class="eas-summary-review">';

        $html .= '<a ';
        $html .= 'class="button button-primary" ';
        $html .= 'href="';
        $html .= esc_url($review_url);
        $html .= '">';

        $html .= 'Review';

        $html .= '</a>';

        $html .= '</p>';

        $html .= '</div>';

        return $html;
    }
}