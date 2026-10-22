<?php

declare(strict_types=1);

namespace EAS\Admin\Attempt;

use EAS\Attempt\AttemptRepository;
use EAS\Response\ResponseRepository;
use EAS\Quiz\QuizRepository;

if (!defined('ABSPATH')) {
    exit;
}

class GradeController
{
    private AttemptRepository $attempts;
    private ResponseRepository $responses;
    private QuizRepository $quizzes;

    public function __construct()
    {
        $this->attempts = new AttemptRepository();
        $this->quizzes = new QuizRepository();
        $this->responses = new ResponseRepository();

        add_action(
            'admin_post_eas_save_grades',
            array($this, 'save')
        );
    }

    public function index(): void
    {
        $this->require_admin_capability();

        // This GET parameter only selects the attempt to display; it does not perform a state-changing action.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation parameter; no state is changed.
        $request = wp_unslash($_GET);
        $attempt_id = $this->validated_request_id($request, 'attempt_id');

        if ($attempt_id === null) {
            wp_die(
                esc_html__('Invalid attempt.', 'eas-quiz'),
                '',
                array('response' => 400)
            );
        }

        $attempt = $this->attempts->find_with_details($attempt_id);

        if (!$attempt) {
            wp_die(
                esc_html__('Attempt not found.', 'eas-quiz'),
                '',
                array('response' => 404)
            );
        }

        $responses = $this->responses->all_by_attempt($attempt_id);

        require EAS_PLUGIN_DIR . 'views/admin/attempts/grade.php';
    }

    public function save(): void
    {
        $this->require_admin_capability();

        check_admin_referer('eas_save_grades');

        $request = wp_unslash($_POST);
        $attempt_id = $this->validated_request_id($request, 'attempt_id');

        if ($attempt_id === null) {
            wp_die(
                esc_html__('Invalid attempt.', 'eas-quiz'),
                '',
                array('response' => 400)
            );
        }

        $attempt = $this->attempts->find($attempt_id);

        if (!$attempt) {
            wp_die(
                esc_html__('Attempt not found.', 'eas-quiz'),
                '',
                array('response' => 404)
            );
        }

        $responses = $this->responses->all_by_attempt($attempt_id);
        $grades = array();

        /*
         * Validate every submitted grade before saving any of them.
         * The form's min/max attributes are client-side only and can
         * be bypassed by sending a direct request.
         */
        foreach ($responses as $response) {
            $item_id = (int) $response['item_id'];
            $submitted_points = $request['points_' . $item_id] ?? 0;

            if (!is_scalar($submitted_points)) {
                wp_die(
                    esc_html__('Invalid points value.', 'eas-quiz'),
                    '',
                    array('response' => 400)
                );
            }

            $submitted_points = sanitize_text_field((string) $submitted_points);

            // Preserve the existing behavior where an empty field means zero.
            if ($submitted_points === '') {
                $submitted_points = 0;
            }

            if (!is_numeric($submitted_points)) {
                wp_die(
                    esc_html__('Invalid points value.', 'eas-quiz'),
                    '',
                    array('response' => 400)
                );
            }

            $points = (float) $submitted_points;
            $max_points = $response['max_points'] ?? null;

            if (
                !is_finite($points)
                || $points < 0
                || !is_numeric($max_points)
                || !is_finite((float) $max_points)
                || $points > (float) $max_points
            ) {
                wp_die(
                    esc_html__('Points must be between zero and the question maximum.', 'eas-quiz'),
                    '',
                    array('response' => 400)
                );
            }

            $submitted_feedback = $request['feedback_' . $item_id] ?? '';

            if (!is_scalar($submitted_feedback)) {
                wp_die(
                    esc_html__('Invalid feedback value.', 'eas-quiz'),
                    '',
                    array('response' => 400)
                );
            }

            $grades[] = array(
                'item_id' => $item_id,
                'points' => $points,
                'feedback' => sanitize_textarea_field(
                    (string) $submitted_feedback
                )
            );
        }

        foreach ($grades as $grade) {
            $this->responses->update_grade(
                $attempt_id,
                $grade['item_id'],
                $grade['points'],
                $grade['feedback'],
                get_current_user_id()
            );
        }

        $this->attempts->recalculate_score($attempt_id);

        wp_safe_redirect(
            admin_url(
                'admin.php?page=eas-attempt-grade&attempt_id='
                . $attempt_id
                . '&graded=1'
            )
        );

        exit;
    }

    /**
     * Validate a request ID without allowing PHP casts to reinterpret malformed input.
     */
    private function validated_request_id(array $source, string $key): ?int
    {
        if (!array_key_exists($key, $source) || !is_scalar($source[$key])) {
            return null;
        }

        $value = (string) $source[$key];

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
