<?php
declare(strict_types=1);
namespace EAS\Attempt;
use EAS\Page\PageRepository;
use EAS\Question\QuestionRepository;
use EAS\Question\QuestionRenderer;
use EAS\Quiz\QuizRepository;
use EAS\Core\View;
if (!defined('ABSPATH')) {
    exit;
}
class AttemptRenderer
{
    private AttemptRepository $attempts;
    private PageRepository $pages;
    private QuestionRepository $questions;
    private QuestionRenderer $questionRenderer;
    private QuizRepository $quizzes;
	private ?array $current_quiz = null;
    public function __construct()
    {
        $this->attempts = new AttemptRepository();
        $this->pages = new PageRepository();
        $this->questions = new QuestionRepository();
        $this->questionRenderer = new QuestionRenderer();
        $this->quizzes = new QuizRepository();
    }
    public function render(int $attempt_id): string
    {
        $attempt = $this->attempts->find($attempt_id);
        if (!$attempt) {
            return '<p>Attempt not found.</p>';
        }
        if (!empty($attempt['current_page_id'])) {
            $page = $this->pages->find(
                (int) $attempt['current_page_id']
            );
        } else {
            $page = $this->pages->firstByQuiz(
                (int) $attempt['quiz_id']
            );
        }
        if (!$page) {
            return '<p>No pages found.</p>';
        }
        $quiz = $this->quizzes->find(
            (int) $attempt['quiz_id']
        );
$this->current_quiz = $quiz;
$remaining_seconds = null;
$page_time_remaining = null;

// --------------------------------------------------
// QUIZ TIMER
// --------------------------------------------------
$pages_with_time_limit =
    $this->pages->get_pages_with_time_limit(
        (int) $attempt['quiz_id']
    );

// Only use quiz timer if NO page timers exist.
if (
    $quiz
    &&
    (int) $quiz['time_limit'] > 0
    &&
    empty($pages_with_time_limit)
) {

    $timezone = wp_timezone();

    $start_time = \DateTimeImmutable::createFromFormat(
        'Y-m-d H:i:s',
        $attempt['started_at'],
        $timezone
    );

    if ($start_time !== false) {

        $total_seconds =
            (int) $quiz['time_limit'] * 60;

        $elapsed =
            time() - $start_time->getTimestamp();

        $remaining_seconds =
            max(
                0,
                $total_seconds - $elapsed
            );

    } else {

        $remaining_seconds = 0;

    }
}
// --------------------------------------------------
// PAGE TIMER
// --------------------------------------------------
$page_time_limit =
    (int) ($page['page_time_limit'] ?? 0);
if ($page_time_limit > 0) {
    if (!empty($attempt['page_started_at'])) {
        /*
         * page_started_at is stored in UTC.
         */
        $page_start_time = strtotime(
            $attempt['page_started_at'] . ' UTC'
        );
    } else {
        /*
         * This is mainly for old attempts created
         * before page_started_at was introduced.
         */
        $page_start_time = time();
    }
    $page_total_seconds =
        $page_time_limit;
    $page_elapsed =
        time() - $page_start_time;
    $page_time_remaining =
        max(
            0,
            $page_total_seconds - $page_elapsed
        );
}
        $questions = $this->questions->all_by_page(
            (int) $page['id']
        );
        $responses = new \EAS\Response\ResponseRepository();

$attempt_responses = array();

foreach (
    $questions
    as $question
) {

    $response = $responses->find(
        $attempt_id,
        (int) $question['id']
    );

    if ($response) {

        $attempt_responses[
            (int) $question['id']
        ] = $response;

    }
}
        $question_offset = 0;
        $previous_pages = $this->pages->all_by_quiz(
            (int) $attempt['quiz_id']
        );
        foreach ($previous_pages as $previous_page) {
            if (
                (int) $previous_page['page_number']
                <
                (int) $page['page_number']
            ) {
                $question_offset += count(
                    $this->questions->all_by_page(
                        (int) $previous_page['id']
                    )
                );
            }
        }
        return View::render(
    'frontend/attempt/page',
    [
        'attempt' => $attempt,
        'page' => $page,
        'quiz' => $quiz,
        'questions' => $questions,
        'question_offset' => $question_offset,
        'renderer' => $this,
        'pages' => $this->pages,
        'remaining_seconds' => $remaining_seconds,
        'page_time_limit' => $page_time_limit,
        'page_time_remaining' => $page_time_remaining,
        'attempt_responses' => $attempt_responses
    ]
);
    }
    public function render_question(
    array $question,
    int $number,
    ?array $response = null
): string
{
    return $this->questionRenderer->render(
        $question,
        $number,
        $this->current_quiz,
        $response
    );
}
}