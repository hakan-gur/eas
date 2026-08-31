<?php
declare(strict_types=1);
namespace EAS\Admin\Question;
use EAS\Question\QuestionRepository;
use EAS\Page\PageRepository;
use EAS\Quiz\QuizRepository;
if (!defined('ABSPATH')) {
    exit;
}
class QuestionController
{
    private QuestionRepository $repository;
    private PageRepository $pages;
    private QuizRepository $quizzes;
    public function __construct()
    {
        $this->repository = new QuestionRepository();
        $this->pages = new PageRepository();
        $this->quizzes = new QuizRepository();
        add_action(
            'admin_menu',
            array($this, 'register')
        );
        add_action(
            'admin_post_eas_save_question',
            array($this, 'save')
        );
        add_action(
            'admin_post_eas_delete_question',
            array($this, 'delete')
        );
        add_action(
            'admin_post_eas_preview_question',
            array($this, 'preview')
        );
        
        add_action(
            'admin_post_eas_add_existing_question',
            array(
                $this,
                'add_existing_question'
                )
        );
        
        add_action(
            'admin_post_eas_move_question_up',
            array($this, 'move_up')
        );
        
        add_action(
            'admin_post_eas_move_question_down',
            array($this, 'move_down')
        );
        
        add_action(
            'admin_post_eas_import_questions',
            array($this, 'import_questions')
        );
        
        add_action(
            'admin_post_eas_export_questions',
            array($this, 'export_questions')
        );
        
        add_action(
            'admin_notices',
            array($this, 'import_success_notice')
        );
    }
    public function register(): void
    {
        add_submenu_page(
            'options.php',
            'EAS Questions',
            'Questions',
            'manage_options',
            'eas-questions',
            array($this, 'index')
        );
    }
    public function index(): void
    {
            $action = sanitize_key(
        $_GET['action'] ?? ''
    );
    /*
     * Quiz-level question bank.
     */
    if (
        $action === 'bank'
        &&
        isset($_GET['quiz_id'])
    ) {
        $quiz_id = (int) $_GET['quiz_id'];
        if ($quiz_id <= 0) {
            wp_die(
                'Invalid quiz.'
            );
        }
        $quiz = $this->quizzes->find(
            $quiz_id
        );
        if (!$quiz) {
            wp_die(
                'Quiz not found.'
            );
        }
        $questions =
            $this->repository->all_by_quiz(
                $quiz_id
            );
        foreach ($questions as &$question) {
            $question['pages'] =
                $this->repository->pages_for_question(
                    (int) $question['id']
                );
        }
        unset($question);
        $quiz_title =
            $quiz['title'] ?? '';
        require EAS_PLUGIN_DIR .
            'views/admin/questions/bank.php';
        return;
    }
    /*
     * Create a new question directly
     * in the quiz question bank.
     */
    if (
        $action === 'bank_add'
        &&
        isset($_GET['quiz_id'])
    ) {
        $quiz_id = (int) $_GET['quiz_id'];
        if ($quiz_id <= 0) {
            wp_die(
                'Invalid quiz.'
            );
        }
        $quiz = $this->quizzes->find(
            $quiz_id
        );
        if (!$quiz) {
            wp_die(
                'Quiz not found.'
            );
        }
        $question = null;
        $answers = array();
        $page_id = 0;
        
        require EAS_PLUGIN_DIR .
            'views/admin/questions/form.php';
        return;
    }
    /*
 * ---------------------------------------------------------
 * Import questions into the quiz question bank.
 * ---------------------------------------------------------
 */
if (
    $action === 'import'
    &&
    isset($_GET['quiz_id'])
) {
    $quiz_id = (int) $_GET['quiz_id'];
    if ($quiz_id <= 0) {
        wp_die(
            'Invalid quiz.'
        );
    }
    $quiz = $this->quizzes->find(
        $quiz_id
    );
    if (!$quiz) {
        wp_die(
            'Quiz not found.'
        );
    }
    $quiz_title =
        $quiz['title'] ?? '';
    require EAS_PLUGIN_DIR .
        'views/admin/questions/import.php';
    return;
}
    
        /*
 * Edit an existing question directly
 * in the quiz question bank.
 */
if (
    $action === 'bank_edit'
    &&
    isset($_GET['quiz_id'])
    &&
    isset($_GET['id'])
) {
    $quiz_id = (int) $_GET['quiz_id'];
    $question_id = (int) $_GET['id'];
    if ($quiz_id <= 0) {
        wp_die(
            'Invalid quiz.'
        );
    }
    if ($question_id <= 0) {
        wp_die(
            'Invalid question.'
        );
    }
    /*
     * Verify that the quiz exists.
     */
    $quiz = $this->quizzes->find(
        $quiz_id
    );
    if (!$quiz) {
        wp_die(
            'Quiz not found.'
        );
    }
    /*
     * Load the question.
     */
    $question = $this->repository->find(
        $question_id
    );
    if (!$question) {
        wp_die(
            'Question not found.'
        );
    }
    /*
     * Make sure the question belongs
     * to this quiz.
     */
    if (
        (int) $question['quiz_id']
        !==
        $quiz_id
    ) {
        wp_die(
            'This question does not belong to this quiz.'
        );
    }
    /*
     * Load the question's answers.
     */
    $answers = $this->repository->get_answers(
        $question_id
    );
    /*
     * This is a quiz-bank question,
     * so it is not attached to a page
     * while being edited.
     */
    $page_id = 0;
    /*
     * Keep the quiz ID available to the form.
     */
    $quiz_title = $quiz['title'] ?? '';
    require EAS_PLUGIN_DIR .
        'views/admin/questions/form.php';
    return;
}
        $page_id = isset($_GET['page_id'])
            ? (int) $_GET['page_id']
            : 0;
        if (
            isset($_GET['action'])
            &&
            $_GET['action'] === 'add'
        ) {
            $this->form($page_id);
            return;
        }
        
        if (
            isset($_GET['action'])
            &&
            $_GET['action'] === 'add_existing'
            ) {
                $this->add_existing($page_id);
                return;
            }
        if (
            isset($_GET['action'])
            &&
            $_GET['action'] === 'edit'
            &&
            isset($_GET['id'])
        ) {
            $this->form(
                $page_id,
                (int) $_GET['id']
            );
            return;
        }
        $questions = $this->repository->all_by_page(
            $page_id
        );
        $page = $this->pages->find(
            $page_id
        );
        $quiz = null;
        if ($page) {
            $quiz = $this->quizzes->find(
                (int) $page['quiz_id']
            );
        }
        $quiz_title = $quiz['title'] ?? '';
        $page_title = $page['title'] ?? '';
        require EAS_PLUGIN_DIR .
            'views/admin/questions/list.php';
    }
    private function form(
        int $page_id,
        ?int $id = null
    ): void {
        $question = null;
        $answers = array();
        if ($id !== null) {
            $question = $this->repository->find(
                $id
            );
            $answers = $this->repository->get_answers(
                $id
            );
        }
        require EAS_PLUGIN_DIR .
            'views/admin/questions/form.php';
    }
    public function save(): void
{
    check_admin_referer(
        'eas_save_question'
    );
    /*
     * A question can be saved in two contexts:
     *
     * 1. From a page:
     *    page_id > 0
     *
     *    The question is created/updated in the
     *    quiz question bank AND attached to the page.
     *
     * 2. From the quiz question bank:
     *    page_id = 0
     *    quiz_id > 0
     *
     *    The question is created/updated in the
     *    question bank only.
     */
    $page_id = (int) (
        $_POST['page_id'] ?? 0
    );
    $quiz_id = (int) (
        $_POST['quiz_id'] ?? 0
    );
    /*
     * If this is a page question, obtain the quiz
     * from the page. This prevents the submitted
     * quiz_id from being used to attach a question
     * to the wrong quiz.
     */
    if ($page_id > 0) {
        $page = $this->pages->find(
            $page_id
        );
        if (!$page) {
            wp_die(
                'Page not found.'
            );
        }
        $quiz_id = (int) $page['quiz_id'];
    } else {
        /*
         * Question-bank question.
         */
        if ($quiz_id <= 0) {
            wp_die(
                'Invalid quiz.'
            );
        }
        $page = null;
    }
    /*
     * Verify that the quiz exists.
     */
    $quiz = $this->quizzes->find(
        $quiz_id
    );
    if (!$quiz) {
        wp_die(
            'Quiz not found.'
        );
    }
    /*
     * Basic question data.
     */
    $data = array(
        'quiz_id' => $quiz_id,
        'type' => sanitize_key(
            $_POST['type']
            ?? 'multiple_choice'
        ),
        'question_text' => wp_kses_post(
    wp_unslash(
        $_POST['question_text']
        ?? ''
    )
),
        'settings' => '',
        'feedback' => wp_kses_post(
            $_POST['feedback']
            ?? ''
        ),
        'points' => (float) (
            $_POST['points']
            ?? 1
        ),
        'updated_at' => current_time(
            'mysql'
        )
    );
    /*
     * Essay settings.
     */
    if (
        $data['type'] === 'essay'
    ) {
        $data['settings'] =
            wp_json_encode(
                array(
                    'essay_min_lines' =>
                        (int) (
                            $_POST[
                                'essay_min_lines'
                            ]
                            ?? 10
                        )
                )
            );
    }
    /*
     * Audio-response settings.
     */
    if (
        $data['type'] === 'audio_response'
    ) {
        $data['settings'] =
            wp_json_encode(
                array(
                    'audio_delay_seconds' =>
                        (int) (
                            $_POST[
                                'audio_delay_seconds'
                            ]
                            ?? 0
                        ),
                    'audio_prepare_seconds' =>
                        (int) (
                            $_POST[
                                'audio_prepare_seconds'
                            ]
                            ?? 5
                        ),
                    'audio_duration_seconds' =>
                        (int) (
                            $_POST[
                                'audio_duration_seconds'
                            ]
                            ?? 45
                        )
                )
            );
    }
    /*
     * Existing question or new question?
     */
    $id = isset(
        $_POST['question_id']
    )
        ? (int) $_POST['question_id']
        : 0;
    if ($id > 0) {
        /*
         * Make sure the question belongs to
         * this quiz before updating it.
         */
        $existing_question =
            $this->repository->find(
                $id
            );
        if (
            !$existing_question
            ||
            (int) $existing_question['quiz_id']
                !== $quiz_id
        ) {
            wp_die(
                'Question not found in this quiz.'
            );
        }
        /*
         * Do not change the original creation
         * timestamp when editing.
         */
        $this->repository->update(
            $id,
            $data
        );
    } else {
        /*
         * Create a new question in the
         * quiz question bank.
         */
        $data['created_at'] =
            current_time(
                'mysql'
            );
        $id =
            $this->repository->create(
                $quiz_id,
                $data
            );
    }
    if ($id <= 0) {
        wp_die(
            'Question could not be saved.'
        );
    }
    /*
     * ---------------------------------------------------------
     * SAVE ANSWERS
     * ---------------------------------------------------------
     */
    $answers = array();
    /*
     * Drag and Drop into Text.
     */
    if (
        $data['type'] === 'drag_drop_text'
    ) {
        for (
            $i = 1;
            $i <= 7;
            $i++
        ) {
            $text = trim(
                $_POST[
                    'answer_' . $i
                ]
                ?? ''
            );
            if ($text !== '') {
                $answers[] = array(
                    'text' => $text,
                    'correct_position' => $i
                );
            }
        }
        /*
         * Optional distractor.
         */
        $distractor = trim(
            $_POST['answer_8']
            ?? ''
        );
        if ($distractor !== '') {
            $answers[] = array(
                'text' => $distractor,
                'correct_position' => 0
            );
        }
    }
    elseif (
$data['type'] === 'fill_blank'
) {
/*
 * Fill in the Blanks / C-Test.
 *
 * Correct answers are stored in square brackets
 * inside question_text.
 *
 * Example:
 *
 * The govern[ment] introduced a new pol[icy].
 *
 * This creates:
 *
 * Position 1 = ment
 * Position 2 = icy
 *
 * The answers are saved in the normal EAS
 * answers table so that the frontend grading
 * code can compare the student's answers
 * against them.
 */
$answers = array();
$question_text_for_answers =
    $data['question_text'];
/*
 * Find every [correct_answer] in the question.
 *
 * The regular expression deliberately ignores
 * double square brackets [[1]], [[2]], etc.,
 * because those belong to Drag-and-Drop questions.
 */
if (
    preg_match_all(
        '/(?<!\[)\[([^\[\]]+)\](?!\])/',
        $question_text_for_answers,
        $matches
    )
) {
    $position = 1;
    foreach (
        $matches[1]
        as $correct_answer
    ) {
        $correct_answer =
            trim($correct_answer);
        if ($correct_answer === '') {
            continue;
        }
        $answers[] = array(
            'text' =>
                $correct_answer,
            'correct' =>
                true,
            'correct_position' =>
                $position
        );
        $position++;
    }
}
}
    /*
     * Multiple Choice and other
     * answer-based question types.
     */
    else {
    for (
        $i = 1;
        $i <= 5;
        $i++
    ) {
        $answers[] = array(
            'text' =>
                wp_unslash(
                    $_POST[
                        'answer_' . $i
                    ]
                    ?? ''
                ),
            'correct' =>
                isset(
                    $_POST['correct']
                )
                &&
                (int) $_POST['correct']
                    === $i
        );
    }
}
    /*
     * Check whether a multiple-choice question
     * has a correct answer.
     */
    $has_correct_answer = false;
    foreach (
        $answers
        as $answer
    ) {
        if (
            !empty(
                $answer['correct']
            )
        ) {
            $has_correct_answer = true;
            break;
        }
    }
    /*
     * Save the answers.
     */
    $this->repository->save_answers(
        $id,
        $answers
    );
    /*
     * ---------------------------------------------------------
     * PAGE ATTACHMENT
     * ---------------------------------------------------------
     *
     * Only questions created/edited from a page
     * are attached to that page.
     *
     * A question created directly in the question
     * bank remains unattached.
     */
    if ($page_id > 0) {
        $existing_relationship =
            $this->repository->find_page_item(
                $page_id,
                $id
            );
        if (!$existing_relationship) {
            $next_sort_order =
                $this->repository
                    ->next_page_sort_order(
                        $page_id
                    );
            $this->repository->attach_to_page(
                $page_id,
                $id,
                $next_sort_order
            );
        }
        /*
         * Redirect back to the page's
         * question list.
         */
        $redirect_url = admin_url(
            'admin.php?page=eas-questions' .
            '&page_id=' .
            $page_id
        );
    } else {
        /*
         * Question-bank question:
         * do NOT attach it to a page.
         */
        $redirect_url = admin_url(
            'admin.php?page=eas-questions' .
            '&quiz_id=' .
            $quiz_id .
            '&action=bank'
        );
    }
    /*
     * Warning for multiple-choice questions
     * without a correct answer.
     */
    if (
        $data['type'] === 'multiple_choice'
        &&
        !$has_correct_answer
    ) {
        $redirect_url = add_query_arg(
            'eas_warning',
            'missing_correct_answer',
            $redirect_url
        );
    }
    wp_redirect(
        $redirect_url
    );
    exit;
}
    private function add_existing(int $page_id): void
{
    if ($page_id <= 0) {
        wp_die('Invalid page.');
    }
    $page = $this->pages->find($page_id);
    if (!$page) {
        wp_die('Page not found.');
    }
    $quiz_id = (int) $page['quiz_id'];
    if ($quiz_id <= 0) {
        wp_die('Invalid quiz.');
    }
    /*
     * All questions belonging to this quiz.
     */
    $question_bank = $this->repository->all_by_quiz(
        $quiz_id
    );
    /*
     * Questions already assigned to this page.
     */
    $page_questions = $this->repository->all_by_page(
        $page_id
    );
    $assigned = array();
    foreach ($page_questions as $question) {
        $assigned[(int) $question['id']] = true;
    }
    $quiz = $this->quizzes->find($quiz_id);
    $quiz_title = $quiz['title'] ?? '';
    $page_title = $page['title'] ?? '';
    require EAS_PLUGIN_DIR .
        'views/admin/questions/add-existing.php';
}
public function add_existing_question(): void
{
    check_admin_referer(
        'eas_add_existing_question'
    );
    $page_id = (int) (
        $_POST['page_id'] ?? 0
    );
    $question_id = (int) (
        $_POST['question_id'] ?? 0
    );
    if ($page_id <= 0 || $question_id <= 0) {
        wp_die(
            'Invalid page or question.'
        );
    }
    $page = $this->pages->find(
        $page_id
    );
    if (!$page) {
        wp_die(
            'Page not found.'
        );
    }
    $question = $this->repository->find(
        $question_id
    );
    if (!$question) {
        wp_die(
            'Question not found.'
        );
    }
    /*
     * Make sure the question belongs to
     * the same quiz as the page.
     */
    if (
        (int) $question['quiz_id']
        !==
        (int) $page['quiz_id']
    ) {
        wp_die(
            'This question does not belong to this quiz.'
        );
    }
    /*
     * Add the relationship only.
     *
     * The question and its answers are NOT copied.
     */
    $this->repository->add_to_page(
        $question_id,
        $page_id
    );
    wp_redirect(
        admin_url(
            'admin.php?page=eas-questions&page_id=' .
            $page_id
        )
    );
    exit;
}
public function move_up(): void
{
    $id = (int) (
        $_GET['id'] ?? 0
    );
    $page_id = (int) (
        $_GET['page_id'] ?? 0
    );
    if ($id <= 0 || $page_id <= 0) {
        wp_die(
            'Invalid question or page.'
        );
    }
    check_admin_referer(
        'eas_move_question_up_' . $id
    );
    $current = $this->repository->find_page_item(
        $page_id,
        $id
    );
    if (!$current) {
        wp_die(
            'Question is not assigned to this page.'
        );
    }
    $previous = $this->repository->get_adjacent_page_item(
        $page_id,
        (int) $current['sort_order'],
        'up'
    );
    if ($previous) {
        $this->repository->swap_page_item_order(
            $page_id,
            $id,
            (int) $previous['item_id'],
            (int) $current['sort_order'],
            (int) $previous['sort_order']
        );
    }
    wp_redirect(
        admin_url(
            'admin.php?page=eas-questions&page_id='
            . $page_id
        )
    );
    exit;
}
public function move_down(): void
{
    $id = (int) (
        $_GET['id'] ?? 0
    );
    $page_id = (int) (
        $_GET['page_id'] ?? 0
    );
    if ($id <= 0 || $page_id <= 0) {
        wp_die(
            'Invalid question or page.'
        );
    }
    check_admin_referer(
        'eas_move_question_down_' . $id
    );
    /*
     * Get the current question/page relationship.
     */
    $current = $this->repository->find_page_item(
        $page_id,
        $id
    );
    if (!$current) {
        wp_die(
            'Question is not assigned to this page.'
        );
    }
    $current_order = (int) $current['sort_order'];
    /*
     * Find the question immediately below this one.
     */
    $next = $this->repository->get_adjacent_page_item(
        $page_id,
        $current_order,
        'down'
    );
    if ($next) {
        $this->repository->swap_page_item_order(
            $page_id,
            $id,
            (int) $next['item_id'],
            $current_order,
            (int) $next['sort_order']
        );
    }
    wp_redirect(
        admin_url(
            'admin.php?page=eas-questions&page_id='
            . $page_id
        )
    );
    exit;
}
 public function delete(): void
{
    $id = (int) (
        $_GET['id'] ?? 0
    );
    $page_id = (int) (
        $_GET['page_id'] ?? 0
    );
    /*
     * A question ID is always required.
     */
    if ($id <= 0) {
        wp_die(
            'Invalid question.'
        );
    }
    check_admin_referer(
        'eas_delete_question_' . $id
    );
    /*
     * Make sure the question actually exists.
     */
    $question = $this->repository->find(
        $id
    );
    if (!$question) {
        wp_die(
            'Question not found.'
        );
    }
    /*
     * ---------------------------------------------------------
     * CASE 1: Question is being deleted from a PAGE
     * ---------------------------------------------------------
     *
     * Remove only the page/question relationship.
     *
     * The question remains in the Question Bank.
     */
    if ($page_id > 0) {
        $this->repository->detach_from_page(
            $page_id,
            $id
        );
        wp_redirect(
            admin_url(
                'admin.php?page=eas-questions&page_id='
                . $page_id
            )
        );
        exit;
    }
    /*
     * ---------------------------------------------------------
     * CASE 2: Question is being deleted from the QUESTION BANK
     * ---------------------------------------------------------
     *
     * Permanently delete the question, its answers,
     * and all page assignments.
     */
    $this->repository->delete(
        $id
    );
    /*
     * Return to the Question Bank.
     */
    $quiz_id = (int) (
        $question['quiz_id'] ?? 0
    );
    if ($quiz_id > 0) {
        wp_redirect(
            admin_url(
                'admin.php?page=eas-questions'
                . '&quiz_id='
                . $quiz_id
                . '&action=bank'
            )
        );
    } else {
        wp_redirect(
            admin_url(
                'admin.php?page=eas-quizzes'
            )
        );
    }
    exit;
}
    public function preview(): void
    {
        $id = (int) (
            $_GET['id'] ?? 0
        );
        if ($id <= 0) {
            wp_die(
                'Invalid question.'
            );
        }
        $question = $this->repository->find(
            $id
        );
        if (!$question) {
            wp_die(
                'Question not found.'
            );
        }
        $renderer =
            new \EAS\Question\QuestionRenderer();
        ?>
    <div class="wrap">
        <h1>
            Question Preview
        </h1>
        <hr>
        <?php
        echo $renderer->render(
            $question,
            1
        );
        ?>
        <hr>
        <p>
            <a
                href="javascript:window.close();"
                class="button"
            >
                Close Preview
            </a>
        </p>
    </div>
    <?php
    exit;
}
public function import_questions(): void
{
    check_admin_referer(
        'eas_import_questions'
    );
    $quiz_id = (int) (
        $_POST['quiz_id'] ?? 0
    );
    if ($quiz_id <= 0) {
        wp_die(
            'Invalid quiz.'
        );
    }
    /*
     * ---------------------------------------------------------
     * GET IMPORT TEXT
     * ---------------------------------------------------------
     */
    $import_text = wp_unslash(
        $_POST['import_text'] ?? ''
    );
    if (trim($import_text) === '') {
        wp_die(
            'No questions were provided.'
        );
    }
    /*
     * Normalize line endings.
     */
    $import_text = str_replace(
        array("\r\n", "\r"),
        "\n",
        $import_text
    );
    /*
     * ---------------------------------------------------------
     * FIND GIFT QUESTIONS
     * ---------------------------------------------------------
     *
     * Each question starts with:
     *
     * ::NUMBER::
     *
     * Examples:
     *
     * ::000::
     * ::001::
     * ::041::
     *
     * We deliberately do NOT use blank lines to separate
     * questions because GIFT questions can contain blank lines.
     */
    $pattern =
        '/::(\d+)::(.*?)(?=\n::\d+::|\z)/s';
    preg_match_all(
        $pattern,
        $import_text,
        $matches,
        PREG_SET_ORDER
    );
    if (
        empty($matches)
        ||
        !is_array($matches)
    ) {
        wp_die(
            'No valid Moodle GIFT questions were found.'
        );
    }
    $imported_count = 0;
    /*
     * ---------------------------------------------------------
     * PROCESS EACH GIFT QUESTION
     * ---------------------------------------------------------
     */
    foreach (
        $matches as $question_index => $match
    ) {
        $question_number =
            trim($match[1]);
        $question_content =
            trim($match[2]);
        if ($question_content === '') {
            continue;
        }
        /*
         * -----------------------------------------------------
         * DESCRIPTION QUESTION
         * -----------------------------------------------------
         *
         * A GIFT question without {...} is treated as a
         * Description question.
         *
         * Example:
         *
         * ::000::<strong>Questions 41 through 43...</strong>
         */
        if (
            strpos(
                $question_content,
                '{'
            ) === false
        ) {
            /*
             * ::000:: means no visible number.
             *
             * Any other number is kept in the question text.
             */
            if (
                (int) $question_number !== 0
            ) {
                $question_text =
                    $question_number .
                    '. ' .
                    $question_content;
            } else {
                $question_text =
                    $question_content;
            }
            $question_data = array(
                'quiz_id' =>
                    $quiz_id,
                'type' =>
                    'description',
                'question_text' =>
                    wp_kses_post(
                        $question_text
                    ),
                'settings' =>
                    '',
                'feedback' =>
                    '',
                'points' =>
                    0,
                'sort_order' =>
                    0,
                'created_at' =>
                    current_time(
                        'mysql'
                    ),
                'updated_at' =>
                    current_time(
                        'mysql'
                    )
            );
            $question_id =
                $this->repository->create(
                    $quiz_id,
                    $question_data
                );
            if ($question_id <= 0) {
                wp_die(
                    'Description question ' .
                    ($question_index + 1) .
                    ' could not be imported.'
                );
            }
            $imported_count++;
            continue;
        }
        /*
         * -----------------------------------------------------
         * MULTIPLE-CHOICE QUESTION
         * -----------------------------------------------------
         */
        /*
         * Find the opening and closing braces.
         */
        $open_brace =
            strpos(
                $question_content,
                '{'
            );
        $close_brace =
            strrpos(
                $question_content,
                '}'
            );
        if (
            $open_brace === false
            ||
            $close_brace === false
            ||
            $close_brace <= $open_brace
        ) {
            wp_die(
                'Question ' .
                ($question_index + 1) .
                ' has an invalid GIFT answer block.'
            );
        }
        /*
         * Question text is everything before the
         * opening brace.
         */
        $question_text =
            trim(
                substr(
                    $question_content,
                    0,
                    $open_brace
                )
            );
        /*
         * Answer block is everything between
         * { and }.
         */
        $answer_block =
            trim(
                substr(
                    $question_content,
                    $open_brace + 1,
                    $close_brace -
                    $open_brace -
                    1
                )
            );
        if ($question_text === '') {
            wp_die(
                'Question ' .
                ($question_index + 1) .
                ' has no question text.'
            );
        }
        /*
         * Add the original Moodle question number.
         *
         * ::000:: is deliberately not displayed.
         */
        if (
            (int) $question_number !== 0
        ) {
            $question_text =
                $question_number .
                '. ' .
                $question_text;
        }
        /*
         * -----------------------------------------------------
         * GENERAL FEEDBACK
         * -----------------------------------------------------
         *
         * Moodle GIFT:
         *
         * ####General feedback
         *
         * Everything after #### belongs to General Feedback.
         */
        $general_feedback = '';
        $feedback_position =
            strpos(
                $answer_block,
                '####'
            );
        if (
            $feedback_position !== false
        ) {
            $general_feedback =
                trim(
                    substr(
                        $answer_block,
                        $feedback_position + 4
                    )
                );
            /*
             * Remove the feedback from the answer block.
             */
            $answer_block =
                trim(
                    substr(
                        $answer_block,
                        0,
                        $feedback_position
                    )
                );
        }
        /*
         * -----------------------------------------------------
         * PARSE ANSWERS
         * -----------------------------------------------------
         *
         * Moodle GIFT:
         *
         * ~wrong
         * =correct
         *
         * Your Moodle configuration places every option
         * on its own line, so we can safely parse each
         * line separately.
         */
        $answer_lines =
            preg_split(
                "/\n/",
                $answer_block
            );
        if (
            !is_array($answer_lines)
        ) {
            wp_die(
                'Question ' .
                ($question_index + 1) .
                ' contains no valid answer options.'
            );
        }
        $answers = array();
        $correct_count = 0;
        foreach (
            $answer_lines as $answer_line
        ) {
            $answer_line =
                trim($answer_line);
            if ($answer_line === '') {
                continue;
            }
            /*
             * Correct answer.
             */
            if (
                isset($answer_line[0])
                &&
                $answer_line[0] === '='
            ) {
                $answer_text =
                    ltrim(
                        substr(
                            $answer_line,
                            1
                        )
                    );
                if ($answer_text === '') {
                    continue;
                }
                $answers[] = array(
                    'text' =>
                        $answer_text,
                    'correct' =>
                        true
                );
                $correct_count++;
                continue;
            }
            /*
             * Incorrect answer.
             */
            if (
                isset($answer_line[0])
                &&
                $answer_line[0] === '~'
            ) {
                $answer_text =
                    ltrim(
                        substr(
                            $answer_line,
                            1
                        )
                    );
                if ($answer_text === '') {
                    continue;
                }
                $answers[] = array(
                    'text' =>
                        $answer_text,
                    'correct' =>
                        false
                );
                continue;
            }
            /*
             * Ignore anything else in the answer block.
             */
        }
        /*
         * Multiple-choice questions must have at least
         * two options and exactly one correct answer.
         */
        if (
            count($answers) < 2
        ) {
            wp_die(
                'Question ' .
                ($question_index + 1) .
                ' must contain at least two answer options.'
            );
        }
        if (
            $correct_count !== 1
        ) {
            wp_die(
                'Question ' .
                ($question_index + 1) .
                ' must have exactly one correct answer. ' .
                'Found: ' .
                $correct_count .
                '.'
            );
        }
        /*
         * -----------------------------------------------------
         * CREATE MULTIPLE-CHOICE QUESTION
         * -----------------------------------------------------
         */
        $question_data = array(
            'quiz_id' =>
                $quiz_id,
            'type' =>
                'multiple_choice',
            'question_text' =>
                wp_kses_post(
                    $question_text
                ),
            'settings' =>
                '',
            'feedback' =>
                wp_kses_post(
                    $general_feedback
                ),
            'points' =>
                1,
            'sort_order' =>
                0,
            'created_at' =>
                current_time(
                    'mysql'
                ),
            'updated_at' =>
                current_time(
                    'mysql'
                )
        );
        $question_id =
            $this->repository->create(
                $quiz_id,
                $question_data
            );
        if ($question_id <= 0) {
            wp_die(
                'Multiple-choice question ' .
                ($question_index + 1) .
                ' could not be imported.'
            );
        }
        /*
         * Save the answer options.
         */
        $this->repository->save_answers(
            $question_id,
            $answers
        );
        $imported_count++;
    }
    /*
     * ---------------------------------------------------------
     * RETURN TO QUESTION BANK
     * ---------------------------------------------------------
     */
    wp_redirect(
        admin_url(
            'admin.php?page=eas-questions' .
            '&quiz_id=' .
            $quiz_id .
            '&action=bank' .
            '&eas_imported=' .
            $imported_count
        )
    );
    exit;
}
public function import_success_notice(): void
{
    if (
        !isset($_GET['page'])
        || $_GET['page'] !== 'eas-questions'
        || empty($_GET['eas_imported'])
    ) {
        return;
    }
    ?>
    <div class="notice notice-success is-dismissible">
        <p>
            <strong>Questions imported successfully.</strong>
        </p>
    </div>
    <?php
}
public function export_questions(): void
{
    /*
     * ---------------------------------------------------------
     * SECURITY
     * ---------------------------------------------------------
     */
    $quiz_id = (int) (
        $_GET['quiz_id'] ?? 0
    );
    if ($quiz_id <= 0) {
        wp_die(
            'Invalid quiz.'
        );
    }
    check_admin_referer(
        'eas_export_questions_' . $quiz_id
    );
    /*
     * ---------------------------------------------------------
     * VERIFY QUIZ
     * ---------------------------------------------------------
     */
    $quiz = $this->quizzes->find(
        $quiz_id
    );
    if (!$quiz) {
        wp_die(
            'Quiz not found.'
        );
    }
    /*
     * ---------------------------------------------------------
     * GET QUESTIONS
     * ---------------------------------------------------------
     *
     * We use the question bank, not the pages.
     *
     * This means every question belonging to the quiz
     * is considered, regardless of page assignment.
     */
    $questions =
        $this->repository->all_by_quiz(
            $quiz_id
        );
    /*
     * ---------------------------------------------------------
     * BUILD GIFT
     * ---------------------------------------------------------
     */
    $gift = array();
    foreach (
        $questions as $question
    ) {
        $question_id = (int) (
            $question['id'] ?? 0
        );
        $type = $question['type'] ?? '';
        /*
         * -----------------------------------------------------
         * IGNORE QUESTION TYPES OTHER THAN:
         *
         * multiple_choice
         * description
         * -----------------------------------------------------
         */
        if (
            $type !== 'multiple_choice'
            &&
            $type !== 'description'
        ) {
            continue;
        }
        if ($question_id <= 0) {
            continue;
        }
        /*
         * -----------------------------------------------------
         * QUESTION TEXT
         * -----------------------------------------------------
         */
        $question_text =
            (string) (
                $question['question_text'] ?? ''
            );
        $question_text =
            trim($question_text);
        /*
         * Remove WordPress HTML only from the exported
         * question text if necessary.
         *
         * We keep the actual text rather than the HTML
         * formatting because this is a Moodle GIFT export.
         */
        $question_text =
            wp_strip_all_tags(
                $question_text
            );
        /*
         * Escape GIFT special characters.
         *
         * Backslash must be escaped first.
         */
        $question_text =
            str_replace(
                '\\',
                '\\\\',
                $question_text
            );
        $question_text =
            str_replace(
                array(
                    '{',
                    '}',
                    '~',
                    '='
                ),
                array(
                    '\{',
                    '\}',
                    '\~',
                    '\='
                ),
                $question_text
            );
        /*
         * -----------------------------------------------------
         * DESCRIPTION
         * -----------------------------------------------------
         *
         * Format:
         *
         * ::123::Description
         */
        if (
            $type === 'description'
        ) {
            $gift[] =
                '::' .
                $question_id .
                '::' .
                $question_text;
            continue;
        }
        /*
         * -----------------------------------------------------
         * MULTIPLE CHOICE
         * -----------------------------------------------------
         */
        $answers =
            $this->repository->get_answers(
                $question_id
            );
        /*
         * Start the question.
         */
        $gift_question =
            '::' .
            $question_id .
            '::' .
            $question_text .
            '{';
        /*
         * Add answer options.
         */
        foreach (
            $answers as $answer
        ) {
            $answer_text =
                trim(
                    (string) (
                        $answer['answer_text'] ?? ''
                    )
                );
            if ($answer_text === '') {
                continue;
            }
            /*
             * Escape GIFT special characters.
             */
            $answer_text =
                str_replace(
                    '\\',
                    '\\\\',
                    $answer_text
                );
            $answer_text =
                str_replace(
                    array(
                        '{',
                        '}',
                        '~',
                        '='
                    ),
                    array(
                        '\{',
                        '\}',
                        '\~',
                        '\='
                    ),
                    $answer_text
                );
            /*
             * Correct answer.
             */
            if (
                !empty(
                    $answer['is_correct']
                )
            ) {
                $gift_question .=
                    "\n=" .
                    $answer_text;
            } else {
                /*
                 * Incorrect answer.
                 */
                $gift_question .=
                    "\n~" .
                    $answer_text;
            }
        }
        /*
         * -----------------------------------------------------
         * GENERAL FEEDBACK
         * -----------------------------------------------------
         *
         * Even when there is no feedback, we deliberately
         * output:
         *
         * ####
         *
         * so that the exported format remains consistent
         * with the requested format.
         */
        $feedback =
            trim(
                (string) (
                    $question['feedback'] ?? ''
                )
            );
        if ($feedback !== '') {
            $feedback =
                wp_strip_all_tags(
                    $feedback
                );
            $feedback =
                str_replace(
                    '\\',
                    '\\\\',
                    $feedback
                );
            $feedback =
                str_replace(
                    array(
                        '{',
                        '}'
                    ),
                    array(
                        '\{',
                        '\}'
                    ),
                    $feedback
                );
            $gift_question .=
                "\n####" .
                $feedback;
        } else {
            $gift_question .=
                "\n####";
        }
        /*
         * Close the GIFT answer block.
         */
        $gift_question .=
            '}';
        $gift[] =
            $gift_question;
    }
    /*
     * ---------------------------------------------------------
     * FINAL OUTPUT
     * ---------------------------------------------------------
     */
    $output =
        implode(
            "\n\n",
            $gift
        );
    /*
     * UTF-8 BOM is deliberately NOT added.
     */
    $filename =
        'eas-quiz-' .
        $quiz_id .
        '-gift.gift';
    /*
     * Make sure nothing has been sent before the download
     * headers.
     */
    if (
        headers_sent()
    ) {
        wp_die(
            'The GIFT export could not be started because output has already been sent.'
        );
    }
    nocache_headers();
    header(
        'Content-Type: text/plain; charset=utf-8'
    );
    header(
        'Content-Disposition: attachment; filename="' .
        $filename .
        '"'
    );
    header(
        'Content-Length: ' .
        strlen($output)
    );
    echo $output;
    exit;
}
}