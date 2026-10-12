<?php

declare(strict_types=1);

namespace EAS\Admin\Quiz;

use EAS\Quiz\QuizRepository;

if (!defined('ABSPATH')) {
    exit;
}

class QuizController
{
    private QuizRepository $repository;

    public function __construct()
    {
        $this->repository = new QuizRepository();

        add_action(
            'admin_post_eas_save_quiz',
            array(
                $this,
                'save'
            )
        );

        add_action(
            'admin_post_eas_delete_quiz',
            array(
                $this,
                'delete'
            )
        );

        add_action(
            'admin_post_eas_duplicate_quiz',
            array(
                $this,
                'duplicate'
            )
        );
    }

    public function index(): void
    {
        $action = sanitize_key(
            $_GET['action'] ?? ''
        );

        if (
            $action === 'bank'
            &&
            isset($_GET['quiz_id'])
        ) {
            $quiz_id = (int) $_GET['quiz_id'];

            if ($quiz_id <= 0) {
                wp_die(
                    esc_html__('Invalid quiz.', 'eas-quiz')
                );
            }

            $quiz = $this->repository->find(
                $quiz_id
            );

            if (!$quiz) {
                wp_die(
                    esc_html__('Quiz not found.', 'eas-quiz')
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

        $quizzes = $this->repository->all();

        foreach ($quizzes as &$quiz) {
            $quiz['page_count'] = (int) $this->repository->page_count(
                (int) $quiz['id']
            );

            $quiz['question_count'] = (int) $this->repository->question_count(
                (int) $quiz['id']
            );

            $quiz['total_points'] = (float) $this->repository->max_points(
                (int) $quiz['id']
            );
        }

        unset($quiz);

        require EAS_PLUGIN_DIR .
            'views/admin/quizzes/list.php';
    }

    public function form(): void
    {
        $quiz = null;

        require EAS_PLUGIN_DIR .
            'views/admin/quizzes/form.php';
    }

    public function edit(): void
    {
        $id = (int) (
            $_GET['id'] ?? 0
        );

        if ($id <= 0) {
            wp_die(
                esc_html__('Invalid quiz.', 'eas-quiz')
            );
        }

        $quiz = $this->repository->find(
            $id
        );

        if (!$quiz) {
            wp_die(
                esc_html__('Quiz not found.', 'eas-quiz')
            );
        }

        require EAS_PLUGIN_DIR .
            'views/admin/quizzes/form.php';
    }

    public function save(): void
    {
        $this->require_admin_capability();

        check_admin_referer(
            'eas_save_quiz'
        );

        /*
         * Validate quiz settings on the server. HTML form constraints
         * can be bypassed by submitting a direct request.
         */
        $submitted_attempts = $_POST['attempts_allowed'] ?? 1;

        if (!is_scalar($submitted_attempts)) {
            wp_die(
                esc_html__('Invalid attempts allowed value.', 'eas-quiz'),
                '',
                array('response' => 400)
            );
        }

        $attempts_allowed = filter_var(
            (string) wp_unslash($submitted_attempts),
            FILTER_VALIDATE_INT
        );

        if ($attempts_allowed === false || $attempts_allowed < 0) {
            wp_die(
                esc_html__('Attempts allowed must be a non-negative whole number.', 'eas-quiz'),
                '',
                array('response' => 400)
            );
        }

        $submitted_time_limit = $_POST['time_limit'] ?? 0;

        if (!is_scalar($submitted_time_limit)) {
            wp_die(
                esc_html__('Invalid time limit.', 'eas-quiz'),
                '',
                array('response' => 400)
            );
        }

        $time_limit = filter_var(
            (string) wp_unslash($submitted_time_limit),
            FILTER_VALIDATE_INT
        );

        if ($time_limit === false || $time_limit < 0) {
            wp_die(
                esc_html__('Time limit must be a non-negative whole number.', 'eas-quiz'),
                '',
                array('response' => 400)
            );
        }

        /*
         * Validate the quiz ID separately from the settings. A missing ID
         * means create; a supplied malformed ID must not become a new quiz.
         */
        if (array_key_exists('quiz_id', $_POST)) {
            $submitted_quiz_id = $_POST['quiz_id'];

            if (!is_scalar($submitted_quiz_id)) {
                wp_die(
                    esc_html__('Invalid quiz ID.', 'eas-quiz'),
                    '',
                    array('response' => 400)
                );
            }

            $id = filter_var(
                (string) wp_unslash($submitted_quiz_id),
                FILTER_VALIDATE_INT
            );

            if ($id === false || $id < 0) {
                wp_die(
                    esc_html__('Invalid quiz ID.', 'eas-quiz'),
                    '',
                    array('response' => 400)
                );
            }
        } else {
            $id = 0;
        }

        if ($id > 0 && !$this->repository->find($id)) {
            wp_die(
                esc_html__('Quiz not found.', 'eas-quiz'),
                '',
                array('response' => 404)
            );
        }

        $data = array(
            'title' => sanitize_text_field(
                $_POST['title'] ?? ''
            ),
            'description' => wp_kses_post(
                $_POST['description'] ?? ''
            ),
            'attempts_allowed' => $attempts_allowed,
            'time_limit' => $time_limit,
            'backward_navigation' => isset(
                $_POST['backward_navigation']
            ) ? 1 : 0,
            'shuffle_options' => isset(
                $_POST['shuffle_options']
            ) ? 1 : 0,
            'updated_at' => current_time(
                'mysql'
            )
        );

        if ($id > 0) {
            $this->repository->update(
                $id,
                $data
            );
        } else {
            $data['created_by'] = get_current_user_id();

            $data['created_at'] = current_time(
                'mysql'
            );

            $this->repository->create(
                $data
            );
        }

        wp_redirect(
            admin_url(
                'admin.php?page=eas-quizzes'
            )
        );

        exit;
    }

    public function duplicate(): void
    {
        $this->require_admin_capability();

        $id = (int) (
            $_GET['id'] ?? 0
        );

        check_admin_referer(
            'eas_duplicate_quiz_' . $id
        );

        $new_id = $this->repository->duplicate(
            $id
        );

        if ($new_id <= 0) {
            wp_die(
                esc_html__('Quiz duplication failed.', 'eas-quiz')
            );
        }

        wp_redirect(
            admin_url(
                'admin.php?page=eas-quizzes'
            )
        );

        exit;
    }

    public function delete(): void
    {
        $this->require_admin_capability();

        $id = (int) (
            $_GET['id'] ?? 0
        );

        check_admin_referer(
            'eas_delete_quiz_' . $id
        );

        $this->repository->delete(
            $id
        );

        wp_redirect(
            admin_url(
                'admin.php?page=eas-quizzes'
            )
        );

        exit;
    }

    private function require_admin_capability(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__('You do not have permission to perform this action.', 'eas-quiz'),
                '',
                array('response' => 403)
            );
        }
    }
}
