<?php

declare(strict_types=1);

namespace Examenanto\Admin;

use Examenanto\Admin\Attempt\GradeController;
use Examenanto\Admin\Attempt\AttemptController;
use Examenanto\Attempt\AttemptRepository;
use Examenanto\Admin\Quiz\QuizController;
use Examenanto\Admin\Page\PageController;
use Examenanto\Admin\Question\QuestionController;
use Examenanto\Quiz\QuizRepository;

if (!defined('ABSPATH')) {
    exit;
}

class AdminController
{
    private QuizController $quizController;
    private PageController $pageController;
    private QuestionController $questionController;
    private AttemptController $attemptController;
    private AttemptRepository $attempts;
    private GradeController $gradeController;
    private QuizRepository $quizzes;

    public function __construct()
    {
        $this->quizController = new QuizController();
        $this->pageController = new PageController();
        $this->questionController = new QuestionController();
        $this->attemptController = new AttemptController();

        $this->attemptController->register();

        $this->attempts = new AttemptRepository();
        $this->gradeController = new GradeController();
        $this->quizzes = new QuizRepository();

        add_action(
            'admin_menu',
            array(
                $this,
                'register_menu'
            )
        );

        add_action(
            'admin_enqueue_scripts',
            array(
                $this,
                'enqueue_admin_assets'
            )
        );
    }

    public function register_menu(): void
    {
        add_menu_page(
            __('Examenanto Assessment Suite', 'eas-quiz'),
            'Examenanto',
            'manage_options',
            'examenanto-dashboard',
            array(
                $this,
                'dashboard'
            ),
            'dashicons-welcome-learn-more',
            30
        );

        add_submenu_page(
            'examenanto-dashboard',
            __('Examenanto Quizzes', 'eas-quiz'),
            __('Quizzes', 'eas-quiz'),
            'manage_options',
            'examenanto-quizzes',
            array(
                $this,
                'quizzes_page'
            )
        );

        add_submenu_page(
            'examenanto-dashboard',
            __('Examenanto Attempts', 'eas-quiz'),
            __('Attempts', 'eas-quiz'),
            'manage_options',
            'examenanto-attempts',
            array(
                $this,
                'attempts_page'
            )
        );

        // Register this internal screen under options.php so it remains
        // directly accessible even though it is intentionally absent from
        // the EAS submenu.
        add_submenu_page(
            'options.php',
            __('Grade Attempt', 'eas-quiz'),
            __('Grade Attempt', 'eas-quiz'),
            'manage_options',
            'examenanto-attempt-grade',
            array(
                $this,
                'grade_page'
            )
        );

        // Hidden legacy slugs keep previously bookmarked EAS admin URLs working.
        add_submenu_page(
            'options.php',
            __('Legacy EAS Dashboard', 'eas-quiz'),
            __('Legacy EAS Dashboard', 'eas-quiz'),
            'manage_options',
            'eas-dashboard',
            array($this, 'dashboard')
        );
        add_submenu_page(
            'options.php',
            __('Legacy EAS Quizzes', 'eas-quiz'),
            __('Legacy EAS Quizzes', 'eas-quiz'),
            'manage_options',
            'eas-quizzes',
            array($this, 'quizzes_page')
        );
        add_submenu_page(
            'options.php',
            __('Legacy EAS Attempts', 'eas-quiz'),
            __('Legacy EAS Attempts', 'eas-quiz'),
            'manage_options',
            'eas-attempts',
            array($this, 'attempts_page')
        );
        add_submenu_page(
            'options.php',
            __('Legacy EAS Grade Attempt', 'eas-quiz'),
            __('Legacy EAS Grade Attempt', 'eas-quiz'),
            'manage_options',
            'eas-attempt-grade',
            array($this, 'grade_page')
        );
    }

    public function enqueue_admin_assets(string $hook_suffix): void
    {
        // Load EAS admin assets only on plugin screens; these GET values are read-only selectors.
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $page = isset($_GET['page']) && is_string($_GET['page'])
            ? sanitize_key(wp_unslash($_GET['page']))
            : '';

        $plugin_pages = array(
            'examenanto-quizzes',
            'examenanto-pages',
            'examenanto-questions',
            'examenanto-attempts',
            'eas-quizzes',
            'eas-pages',
            'eas-questions',
            'eas-attempts'
        );

        if (!in_array($page, $plugin_pages, true)) {
            return;
        }

        if (in_array($page, array('examenanto-quizzes', 'examenanto-pages', 'eas-quizzes', 'eas-pages'), true)) {
            wp_enqueue_style(
                'examenanto-admin',
                EAS_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                EAS_VERSION
            );
        }

        wp_enqueue_script(
            'examenanto-admin',
            EAS_PLUGIN_URL . 'assets/js/admin.js',
            array(),
            EAS_VERSION,
            true
        );

        if (in_array($page, array('examenanto-questions', 'eas-questions'), true)) {
            wp_enqueue_script(
                'examenanto-admin-question-form',
                EAS_PLUGIN_URL . 'assets/js/admin-question-form.js',
                array(),
                EAS_VERSION,
                true
            );
        }

        if (in_array($page, array('examenanto-pages', 'eas-pages'), true)) {
            wp_enqueue_script(
                'examenanto-admin-pages',
                EAS_PLUGIN_URL . 'assets/js/admin-pages.js',
                array(),
                EAS_VERSION,
                true
            );

            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Reject non-string IDs before sanitizing and validating the accepted value.
            $submitted_quiz_id = isset($_GET['quiz_id']) && is_string($_GET['quiz_id'])
                ? sanitize_text_field(wp_unslash($_GET['quiz_id']))
                : '';
            $quiz_id = 0;

            if (preg_match('/\\A[1-9][0-9]*\\z/', $submitted_quiz_id)) {
                $validated_quiz_id = filter_var(
                    $submitted_quiz_id,
                    FILTER_VALIDATE_INT,
                    array('options' => array('min_range' => 1))
                );

                if ($validated_quiz_id !== false) {
                    $quiz_id = $validated_quiz_id;
                }
            }
            // phpcs:enable WordPress.Security.NonceVerification.Recommended

            wp_localize_script(
                'examenanto-admin-pages',
                'examenantoAdminPages',
                array(
                    'uploadUrl' => admin_url('admin-post.php'),
                    'quizId' => $quiz_id,
                    'nonce' => wp_create_nonce('eas_reorder_pages_' . $quiz_id),
                )
            );
        }
    }

    public function quizzes_page(): void
    {
        // This only selects a read-only admin screen; data-changing actions verify their own nonces.
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Reject non-string navigation input before sanitizing the accepted value.
        $submitted_action = isset($_GET['action']) && is_string($_GET['action'])
            ? sanitize_key(wp_unslash($_GET['action']))
            : '';
        $action = $submitted_action;
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if ($action === 'add') {
            $this->quizController->form();
            return;
        }

        if ($action === 'edit') {
            $this->quizController->edit();
            return;
        }

        $this->quizController->index();
    }

    public function attempts_page(): void
    {
        $this->attemptController->index();
    }

    public function grade_page(): void
    {
        $this->gradeController->index();
    }

    public function dashboard(): void
    {
        global $wpdb;

        $quiz_count = $this->quizzes->count();

        $question_count = wp_cache_get(
            'question_count',
            'examenanto_dashboard'
        );

        if ($question_count === false) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Counts questions in the plugin's custom table; no WordPress API provides this count.
            $question_count = (int) $wpdb->get_var(
                "SELECT COUNT(*)
                 FROM {$wpdb->prefix}eas_items"
            );

            wp_cache_set(
                'question_count',
                $question_count,
                'examenanto_dashboard',
                60
            );
        }

        $user_counts = count_users();
        $student_count = (int) ($user_counts['total_users'] ?? 0);

        $attemptRepository = new \Examenanto\Attempt\AttemptRepository();

        $pending_grading = $attemptRepository->count_pending_grading();

        $recent_attempts = wp_cache_get(
            'recent_attempts',
            'examenanto_dashboard'
        );

        if ($recent_attempts === false) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Retrieves recent records from the plugin's custom attempts table with WordPress user and quiz details; no core API provides this combined query.
            $recent_attempts = $wpdb->get_results(
                "
                SELECT
                    a.id,
                    a.score,
                    a.completed_at,
                    u.display_name,
                    q.title AS quiz_title

                FROM {$wpdb->prefix}eas_attempts a

                LEFT JOIN {$wpdb->users} u
                    ON a.user_id = u.ID

                LEFT JOIN {$wpdb->prefix}eas_quizzes q
                    ON a.quiz_id = q.id

                WHERE a.status = 'completed'

                ORDER BY a.completed_at DESC

                LIMIT 5
                ",
                ARRAY_A
            );

            wp_cache_set(
                'recent_attempts',
                $recent_attempts,
                'examenanto_dashboard',
                60
            );
        }

        require EAS_PLUGIN_DIR .
            'views/admin/dashboard.php';
    }
}
