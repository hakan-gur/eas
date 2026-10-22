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
        // GET parameters only select read-only admin screens; mutations have dedicated nonce-checked handlers.
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $quiz_id = 0;
        $quiz = null;

        if (array_key_exists('quiz_id', $_GET)) {
            $validated_quiz_id = $this->validated_positive_request_id($_GET, 'quiz_id');

            if ($validated_quiz_id === null) {
                wp_die(
                    esc_html__('Invalid quiz ID.', 'eas-quiz'),
                    '',
                    array('response' => 400)
                );
            }

            $quiz_id = $validated_quiz_id;
            $quiz = $this->quizzes->find($quiz_id);

            if (!$quiz) {
                wp_die(
                    esc_html__('Quiz not found.', 'eas-quiz'),
                    '',
                    array('response' => 404)
                );
            }
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Type-checked and unslashed/sanitized immediately below.
        $action = $_GET['action'] ?? '';

        if (!is_string($action)) {
            wp_die(
                esc_html__('Invalid page request.', 'eas-quiz'),
                '',
                array('response' => 400)
            );
        }

        $action = sanitize_key(wp_unslash($action));

        if ($action === 'add') {
            if ($quiz_id <= 0 || !$quiz) {
                wp_die(
                    esc_html__('Invalid quiz ID.', 'eas-quiz'),
                    '',
                    array('response' => 400)
                );
            }

            $this->form($quiz_id);
            return;
        }

        if ($action === 'edit') {
            $page_id = $this->validated_positive_request_id($_GET, 'id');

            if ($quiz_id <= 0 || !$quiz || $page_id === null) {
                wp_die(
                    esc_html__('Invalid page or quiz ID.', 'eas-quiz'),
                    '',
                    array('response' => 400)
                );
            }

            $page = $this->repository->find($page_id);

            if (!$page || (int) $page['quiz_id'] !== $quiz_id) {
                wp_die(
                    esc_html__('Page not found in this quiz.', 'eas-quiz'),
                    '',
                    array('response' => 404)
                );
            }

            $this->form($quiz_id, $page_id);
            return;
        }

        $pages = $this->repository->all_by_quiz($quiz_id);
        $quiz = $quiz ?? $this->quizzes->find($quiz_id);

        require EAS_PLUGIN_DIR . 'views/admin/pages/list.php';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
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

        // Unslash request data once before validation and sanitization.
        $post = wp_unslash($_POST);

        /*
         * Validate page identifiers and editable fields before any database writes.
         */
        $submitted_quiz_id = $post['quiz_id'] ?? null;
        if (!is_string($submitted_quiz_id)) {
            wp_die(
                esc_html__('Invalid quiz ID.', 'eas-quiz'),
                '',
                array('response' => 400)
            );
        }
        $submitted_quiz_id = $submitted_quiz_id;
        if (!preg_match('/^[1-9][0-9]*$/', $submitted_quiz_id)) {
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
            if (isset($post[$text_field]) && !is_string($post[$text_field])) {
                wp_die(
                    esc_html__('Invalid page content.', 'eas-quiz'),
                    '',
                    array('response' => 400)
                );
            }
        }

        $submitted_time_limit = $post['page_time_limit'] ?? '0';
        if (!is_scalar($submitted_time_limit)) {
            wp_die(
                esc_html__('Page time limit must be a non-negative whole number.', 'eas-quiz'),
                '',
                array('response' => 400)
            );
        }
        $submitted_time_limit = (string) $submitted_time_limit;
        if (
            !preg_match('/^(0|[1-9][0-9]*)$/', $submitted_time_limit)
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

        $submitted_page_id = $post['page_id'] ?? '0';
        if (!is_scalar($submitted_page_id)) {
            wp_die(
                esc_html__('Invalid page ID.', 'eas-quiz'),
                '',
                array('response' => 400)
            );
        }
        $submitted_page_id = (string) $submitted_page_id;
        if (
            !preg_match('/^(0|[1-9][0-9]*)$/', $submitted_page_id)
        ) {
            wp_die(
                esc_html__('Invalid page ID.', 'eas-quiz'),
                '',
                array('response' => 400)
            );
        }

        $id = filter_var($submitted_page_id, FILTER_VALIDATE_INT);
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
            'title' => sanitize_text_field($post['title'] ?? ''),
            'content' => wp_kses_post(
                $post['content'] ?? ''
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

        wp_safe_redirect(admin_url('admin.php?page=eas-pages&quiz_id=' . $quiz_id));
        exit;
    }

    public function delete(): void
    {
        $this->require_admin_capability();

        // These IDs are needed to construct the nonce action; no mutation occurs before nonce verification.
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $id = $this->validated_positive_request_id($_GET, 'id');
        $quiz_id = $this->validated_positive_request_id($_GET, 'quiz_id');
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

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

        // IDs are needed to construct the nonce action; no mutation occurs before nonce verification.
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $id = $this->validated_positive_request_id($_GET, 'id');
        $quiz_id = $this->validated_positive_request_id($_GET, 'quiz_id');
        $direction = $_GET['direction'] ?? null;
        if (is_string($direction)) {
            $direction = sanitize_key(wp_unslash($direction));
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

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

        // The quiz ID is needed to construct the nonce action; no mutation occurs before nonce verification.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $post = wp_unslash($_POST);
        $submitted_quiz_id = $post['quiz_id'] ?? null;
        if (
            !is_scalar($submitted_quiz_id)
            || !preg_match('/\A[1-9][0-9]*\z/', (string) $submitted_quiz_id)
        ) {
            wp_die(
                esc_html__('Invalid quiz ID.', 'eas-quiz'),
                '',
                array('response' => 400)
            );
        }

        $quiz_id = filter_var(
            (string) $submitted_quiz_id,
            FILTER_VALIDATE_INT,
            array('options' => array('min_range' => 1))
        );

        if ($quiz_id === false || !$this->quizzes->find($quiz_id)) {
            wp_die(
                esc_html__('Quiz not found.', 'eas-quiz'),
                '',
                array('response' => 404)
            );
        }

        check_admin_referer('eas_reorder_pages_' . $quiz_id);

        if (!isset($post['page_ids']) || !is_array($post['page_ids'])) {
            wp_die(
                esc_html__('Invalid page order.', 'eas-quiz'),
                '',
                array('response' => 400)
            );
        }

        $submitted_page_ids = array();
        foreach ($post['page_ids'] as $submitted_page_id) {
            if (
                !is_scalar($submitted_page_id)
                || !preg_match('/\A[1-9][0-9]*\z/', (string) $submitted_page_id)
            ) {
                wp_die(
                    esc_html__('Invalid page order.', 'eas-quiz'),
                    '',
                    array('response' => 400)
                );
            }

            $page_id = filter_var(
                (string) $submitted_page_id,
                FILTER_VALIDATE_INT,
                array('options' => array('min_range' => 1))
            );

            if ($page_id === false || in_array($page_id, $submitted_page_ids, true)) {
                wp_die(
                    esc_html__('Invalid or duplicate page ID.', 'eas-quiz'),
                    '',
                    array('response' => 400)
                );
            }

            $submitted_page_ids[] = $page_id;
        }

        $quiz_pages = $this->repository->all_by_quiz($quiz_id);
        $expected_page_ids = array_map(
            static function (array $page): int {
                return (int) $page['id'];
            },
            $quiz_pages
        );

        $actual_sorted = $submitted_page_ids;
        $expected_sorted = $expected_page_ids;
        sort($actual_sorted, SORT_NUMERIC);
        sort($expected_sorted, SORT_NUMERIC);

        if ($actual_sorted !== $expected_sorted) {
            wp_die(
                esc_html__('The page order must contain every page in this quiz exactly once.', 'eas-quiz'),
                '',
                array('response' => 400)
            );
        }

        if (!empty($submitted_page_ids)) {
            $this->repository->reorder($quiz_id, $submitted_page_ids);
        }

        wp_safe_redirect(admin_url('admin.php?page=eas-pages&quiz_id=' . $quiz_id));
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
