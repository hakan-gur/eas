<?php

declare(strict_types=1);

namespace EAS\Admin\Attempt;

use EAS\Attempt\AttemptRepository;
use EAS\Quiz\QuizRepository;

if (!defined('ABSPATH')) {
    exit;
}

class AttemptController
{
    private AttemptRepository $attempts;

    private QuizRepository $quizzes;

    public function __construct()
    {
        $this->attempts = new AttemptRepository();
        $this->quizzes = new QuizRepository();
    }

    public function register(): void
    {
        add_action(
            'admin_post_eas_delete_attempt',
            array($this, 'delete')
        );
    }

    public function index(): void
    {
        $quiz_id = 0;

        if (array_key_exists('quiz_id', $_GET)) {
            $validated_quiz_id = $this->validated_request_id(
                $_GET,
                'quiz_id',
                true
            );

            if ($validated_quiz_id === null) {
                wp_die(
                    esc_html__('Invalid quiz ID.', 'eas-quiz'),
                    '',
                    array('response' => 400)
                );
            }

            $quiz_id = $validated_quiz_id;
        }

        $status = sanitize_text_field(
            $_GET['status'] ?? ''
        );

        $attempts = $this->attempts->all_completed_filtered(
            $quiz_id,
            $status
        );

        foreach ($attempts as &$attempt) {
            $attempt['grading_status'] = $this->attempts->grading_status(
                (int) $attempt['id']
            );

            $attempt['max_points'] = $this->quizzes->max_points(
                (int) $attempt['quiz_id']
            );

            if (
                !empty($attempt['started_at'])
                && !empty($attempt['completed_at'])
            ) {
                $start = strtotime($attempt['started_at']);
                $end = strtotime($attempt['completed_at']);
                $seconds = $end - $start;
                $minutes = floor($seconds / 60);
                $remaining_seconds = $seconds % 60;

                $attempt['duration'] = sprintf(
                    esc_html__('%1$d min %2$d sec', 'eas-quiz'),
                    $minutes,
                    $remaining_seconds
                );
            } else {
                $attempt['duration'] = '-';
            }
        }

        unset($attempt);

        require EAS_PLUGIN_DIR . 'views/admin/attempts/index.php';
    }

    public function delete(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__('You do not have permission to delete attempts.', 'eas-quiz'),
                '',
                array('response' => 403)
            );
        }

        $attempt_id = $this->validated_request_id($_GET, 'id');

        if ($attempt_id === null) {
            wp_die(
                esc_html__('Invalid attempt.', 'eas-quiz'),
                '',
                array('response' => 400)
            );
        }

        check_admin_referer('eas_delete_attempt_' . $attempt_id);

        $attempt = $this->attempts->find($attempt_id);

        if (!$attempt) {
            wp_die(
                esc_html__('Attempt not found.', 'eas-quiz'),
                '',
                array('response' => 404)
            );
        }

        $this->attempts->delete($attempt_id);

        wp_safe_redirect(
            admin_url('admin.php?page=eas-attempts')
        );

        exit;
    }

    /**
     * Validate a request ID without allowing PHP casts to reinterpret malformed input.
     * Allows zero only for optional list filters where zero means "all quizzes".
     */
    private function validated_request_id(
        array $source,
        string $key,
        bool $allow_zero = false
    ): ?int {
        if (!array_key_exists($key, $source) || !is_scalar($source[$key])) {
            return null;
        }

        $value = (string) wp_unslash($source[$key]);
        $pattern = $allow_zero ? '/\A(?:0|[1-9][0-9]*)\z/' : '/\A[1-9][0-9]*\z/';

        if (!preg_match($pattern, $value)) {
            return null;
        }

        $id = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            array('options' => array('min_range' => $allow_zero ? 0 : 1))
        );

        return $id === false ? null : $id;
    }
}
