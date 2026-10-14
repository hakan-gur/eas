<?php
declare(strict_types=1);

namespace EAS\Frontend;

use EAS\Result\ResultRenderer;
use EAS\Attempt\AttemptRepository;

if (!defined('ABSPATH')) {
    exit;
}

class ResultController
{
    private ResultRenderer $renderer;
    private AttemptRepository $attempts;

    public function __construct()
    {
        $this->renderer =
            new ResultRenderer();

        $this->attempts =
            new AttemptRepository();
    }

    public function register(): void
    {
        add_shortcode(
            'eas_results',
            array(
                $this,
                'render'
            )
        );
    }

    public function render(): string
    {
        /*
         * Results are student-facing.
         */
        if (!is_user_logged_in()) {

            return '<p>' . esc_html__('Please log in to view your result.', 'eas-quiz') . '</p>';

        }


        /*
         * Validate the request value before using it as an attempt ID.
         * Reject arrays, malformed integers, zero, negatives, and overflow.
         */
        $submitted_attempt_id = $_GET['attempt_id'] ?? null;

        if (
            !is_scalar($submitted_attempt_id)
            || !preg_match('/\A[1-9][0-9]*\z/', (string) wp_unslash($submitted_attempt_id))
        ) {
            return '<p>' . esc_html__('No attempt selected.', 'eas-quiz') . '</p>';
        }

        $attempt_id = filter_var(
            (string) wp_unslash($submitted_attempt_id),
            FILTER_VALIDATE_INT,
            array('options' => array('min_range' => 1))
        );

        if ($attempt_id === false) {
            return '<p>' . esc_html__('No attempt selected.', 'eas-quiz') . '</p>';
        }

        /*
         * Validate the optional review switch as a scalar request value.
         */
        $submitted_review = $_GET['review'] ?? null;

        if ($submitted_review !== null && !is_scalar($submitted_review)) {
            return '<p>' . esc_html__('Invalid result request.', 'eas-quiz') . '</p>';
        }


        /*
         * Verify that the attempt exists.
         */
        $attempt =
            $this->attempts->find(
                $attempt_id
            );


        if (!$attempt) {

            return '<p>' . esc_html__('Attempt not found.', 'eas-quiz') . '</p>';

        }


        /*
         * SECURITY:
         *
         * Students may only access their own attempts.
         */
        if (
            (int) $attempt['user_id']
            !==
            (int) get_current_user_id()
        ) {

            return '<p>' . esc_html__('You are not allowed to view this result.', 'eas-quiz') . '</p>';

        }


        /*
         * Only completed attempts can be viewed.
         */
        if (
            ($attempt['status'] ?? '')
            !== 'completed'
        ) {

            return '<p>' . esc_html__('This quiz attempt has not been completed.', 'eas-quiz') . '</p>';

        }


        $review =
            !empty($submitted_review);


        if ($review) {

            return $this->renderer->render(
                $attempt_id
            );

        }


        return $this->renderer->render_summary(
            $attempt_id
        );
    }
}