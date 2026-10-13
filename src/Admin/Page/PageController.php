<?php

declare(strict_types=1);

namespace EAS\Admin\Page;

use EAS\Page\PageRepository;
use EAS\Quiz\QuizRepository;

if (!defined('ABSPATH')) {
    exit;
}

class PageController
{
    private PageRepository $repository;
    private QuizRepository $quizzes;

    public function __construct()
    {
        $this->repository = new PageRepository();
        $this->quizzes = new QuizRepository();

        add_action('admin_menu', array($this, 'register'), 99);
        add_action('admin_post_eas_save_page', array($this, 'save'));
        add_action('admin_post_eas_delete_page', array($this, 'delete'));
        add_action('admin_post_eas_move_page', array($this, 'move'));
        add_action('admin_post_eas_reorder_pages', array($this, 'reorder'));
    }

    public function register(): void
    {
        add_submenu_page(
            'eas-dashboard',
            __('EAS Pages', 'eas-quiz'),
            __('Pages', 'eas-quiz'),
            'manage_options',
            'eas-pages',
            array($this, 'index')
        );
    }

    public function index(): void
    {
        $quiz_id = isset($_GET['quiz_id']) ? (int) $_GET['quiz_id'] : 0;

        if (isset($_GET['action']) && $_GET['action'] === 'add') {
            $this->form($quiz_id);
            return;
        }

        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            $this->form($quiz_id, (int) $_GET['id']);
            return;
        }

        $pages = $this->repository->all_by_quiz($quiz_id);
        $quiz = $this->quizzes->find($quiz_id);

        require EAS_PLUGIN_DIR . 'views/admin/pages/list.php';
    }

    private function form(int $quiz_id, ?int $id = null): void
    {
        $page = null;

        if ($id !== null) {
            $page = $this->repository->find($id);
        }

        require EAS_PLUGIN_DIR . 'views/admin/pages/form.php';
    }

    public function save(): void
    {
        $this->require_admin_capability();

        check_admin_referer('eas_save_page');

        /*
         * Validate page identifiers and editable fields before any database writes.
         */
        $submitted_quiz_id = $_POST['quiz_id'] ?? null;
        if (!is_string($submitted_quiz_id) || !preg_match('/^[1-9][0-9]*$/', $submitted_quiz_id)) {
            wp_die(
                esc_html__('Invalid quiz ID.', 'eas-quiz'),
                '',
                array('response' => 400)
            );
        }

        $quiz_id = filter_var($submitted_quiz_id, FILTER_VALIDATE_INT);
        if ($quiz_id === false || !$this->quizzes->find($quiz_id)) {
            wp_die(
                esc_html__('Quiz not found.', 'eas-quiz'),
                '',
                array('response' => 404)
            );
        }

        foreach (array('title', 'content') as $text_field) {
            if (isset($_POST[$text_field]) && !is_string($_POST[$text_field])) {
                wp_die(
                    esc_html__('Invalid page content.', 'eas-quiz'),
                    '',
                    array('response' => 400)
                );
            }
        }

        $submitted_time_limit = $_POST['page_time_limit'] ?? '0';
        if (
            !is_scalar($submitted_time_limit)
            || !preg_match('/^(0|[1-9][0-9]*)$/', (string) $submitted_time_limit)
        ) {
            wp_die(
                esc_html__('Page time limit must be a non-negative whole number.', 'eas-quiz'),
                '',
                array('response' => 400)
            );
        }

        $page_time_limit = filter_var((string) $submitted_time_limit, FILTER_VALIDATE_INT);
        if ($page_time_limit === false) {
            wp_die(
                esc_html__('Invalid page time limit.', 'eas-quiz'),
                '',
                array('response' => 400)
            );
        }

        $submitted_page_id = $_POST['page_id'] ?? '0';
        if (
            !is_scalar($submitted_page_id)
            || !preg_match('/^(0|[1-9][0-9]*)$/', (string) $submitted_page_id)
        ) {
            wp_die(
                esc_html__('Invalid page ID.', 'eas-quiz'),
                '',
                array('response' => 400)
            );
        }

        $id = filter_var((string) $submitted_page_id, FILTER_VALIDATE_INT);
        if ($id === false) {
            wp_die(
                esc_html__('Invalid page ID.', 'eas-quiz'),
                '',
                array('response' => 400)
            );
        }

        if ($id > 0) {
            $existing_page = $this->repository->find($id);
            if (!$existing_page || (int) $existing_page['quiz_id'] !== $quiz_id) {
                wp_die(
                    esc_html__('Page not found in this quiz.', 'eas-quiz'),
                    '',
                    array('response' => 404)
                );
            }
        }

        $data = array(
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'content' => wp_kses_post(
                wp_unslash(
                    $_POST['content'] ?? ''
                    )
                ),
            'page_time_limit' => $page_time_limit,
            'updated_at' => current_time('mysql')
        );

        if ($id > 0) {
            $this->repository->update($id, $data);
        } else {
            $data['created_at'] = current_time('mysql');
            $this->repository->create($quiz_id, $data);
        }

        wp_redirect(admin_url('admin.php?page=eas-pages&quiz_id=' . $quiz_id));
        exit;
    }

    public function delete(): void
    {
        $this->require_admin_capability();

        $id = $this->validated_positive_request_id($_GET, 'id');
        $quiz_id = $this->validated_positive_request_id($_GET, 'quiz_id');

        if ($id === null || $quiz_id === null) {
            wp_die(
                esc_html__('Invalid page or quiz ID.', 'eas-quiz'),
                '',
                array('response' => 400)
            );
        }

        check_admin_referer('eas_delete_page_' . $id);

        $page = $this->repository->find($id);
        if (!$page || (int) $page['quiz_id'] !== $quiz_id) {
            wp_die(
                esc_html__('Page not found in this quiz.', 'eas-quiz'),
                '',
                array('response' => 404)
            );
        }

        $this->repository->delete($id);

        wp_safe_redirect(admin_url('admin.php?page=eas-pages&quiz_id=' . $quiz_id));
        exit;
    }

    public function move(): void
    {
        $this->require_admin_capability();

        $id = $this->validated_positive_request_id($_GET, 'id');
        $quiz_id = $this->validated_positive_request_id($_GET, 'quiz_id');
        $direction = $_GET['direction'] ?? null;

        if (
            $id === null
            || $quiz_id === null
            || !is_string($direction)
            || !in_array($direction, array('up', 'down'), true)
        ) {
            wp_die(
                esc_html__('Invalid page movement request.', 'eas-quiz'),
                '',
                array('response' => 400)
            );
        }

        check_admin_referer('eas_move_page_' . $id);

        $page = $this->repository->find($id);
        if (!$page || (int) $page['quiz_id'] !== $quiz_id) {
            wp_die(
                esc_html__('Page not found in this quiz.', 'eas-quiz'),
                '',
                array('response' => 404)
            );
        }

        $this->repository->move($id, $direction);

        wp_safe_redirect(admin_url('admin.php?page=eas-pages&quiz_id=' . $quiz_id));
        exit;
    }

    public function reorder(): void
    {
        $this->require_admin_capability();

        $quiz_id = (int) ($_POST['quiz_id'] ?? 0);

        check_admin_referer('eas_reorder_pages_' . $quiz_id);

        $page_ids = isset($_POST['page_ids']) ? (array) $_POST['page_ids'] : array();
        $page_ids = array_map('absint', $page_ids);
        $page_ids = array_values(array_filter($page_ids));

        if ($quiz_id > 0 && !empty($page_ids)) {
            $this->repository->reorder($quiz_id, $page_ids);
        }

        wp_redirect(admin_url('admin.php?page=eas-pages&quiz_id=' . $quiz_id));
        exit;
    }

    /**
     * Validate a positive request ID without allowing PHP casts to reinterpret malformed input.
     */
    private function validated_positive_request_id(array $source, string $key): ?int
    {
        if (!array_key_exists($key, $source) || !is_scalar($source[$key])) {
            return null;
        }

        $value = (string) wp_unslash($source[$key]);

        if (!preg_match('/\\A[1-9][0-9]*\\z/', $value)) {
            return null;
        }

        $id = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            array('options' => array('min_range' => 1))
        );

        return $id === false ? null : $id;
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
