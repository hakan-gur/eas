<?php

declare(strict_types=1);

namespace EAS\Admin;

use EAS\Admin\Attempt\GradeController;
use EAS\Admin\Attempt\AttemptController;
use EAS\Attempt\AttemptRepository;
use EAS\Admin\Quiz\QuizController;
use EAS\Admin\Page\PageController;
use EAS\Admin\Question\QuestionController;
use EAS\Quiz\QuizRepository;

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
            'admin_head',
            array(
                $this,
                'hide_internal_pages'
            )
        );
    }

    public function register_menu(): void
    {
        add_menu_page(
            __('e-Assessment Suite', 'eas-quiz'),
            'EAS',
            'manage_options',
            'eas-dashboard',
            array(
                $this,
                'dashboard'
            ),
            'dashicons-welcome-learn-more',
            30
        );

        add_submenu_page(
            'eas-dashboard',
            __('EAS Quizzes', 'eas-quiz'),
            __('Quizzes', 'eas-quiz'),
            'manage_options',
            'eas-quizzes',
            array(
                $this,
                'quizzes_page'
            )
        );

        add_submenu_page(
            'eas-dashboard',
            __('EAS Attempts', 'eas-quiz'),
            __('Attempts', 'eas-quiz'),
            'manage_options',
            'eas-attempts',
            array(
                $this,
                'attempts_page'
            )
        );

        add_submenu_page(
            'eas-dashboard',
            __('Grade Attempt', 'eas-quiz'),
            __('Grade Attempt', 'eas-quiz'),
            'manage_options',
            'eas-attempt-grade',
            array(
                $this,
                'grade_page'
            )
        );
    }

    public function hide_internal_pages(): void
    {
        ?>
        <style>
            #adminmenu li:has(> a[href*="page=eas-pages"]),
            #adminmenu li:has(> a[href*="page=eas-attempt-grade"]) {
                display: none;
            }
        </style>
        <?php
    }

    public function quizzes_page(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This only selects a read-only admin screen; data-changing actions verify their own nonces.
        $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';

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
            'eas_dashboard'
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
                'eas_dashboard',
                60
            );
        }

        $user_counts = count_users();
        $student_count = (int) ($user_counts['total_users'] ?? 0);

        $attemptRepository = new \EAS\Attempt\AttemptRepository();

        $pending_grading = $attemptRepository->count_pending_grading();

        $recent_attempts = wp_cache_get(
            'recent_attempts',
            'eas_dashboard'
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
                'eas_dashboard',
                60
            );
        }

        require EAS_PLUGIN_DIR .
            'views/admin/dashboard.php';
    }
}
