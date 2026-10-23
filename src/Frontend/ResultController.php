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
         * These GET parameters only select read-only result navigation.
         * Access is checked against the logged-in user and attempt owner below.
         */
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only result navigation; no data is changed.
        $submitted_attempt_id = null;

        if (isset($_GET['attempt_id'])) {
            $raw_attempt_id = wp_unslash($_GET['attempt_id']);

            if (!is_scalar($raw_attempt_id)) {
                return '<p>' . esc_html__('No attempt selected.', 'eas-quiz') . '</p>';
            }

            $submitted_attempt_id = sanitize_text_field(
                (string) $raw_attempt_id
            );
        }

        if (
            !is_string($submitted_attempt_id)
            || !preg_match('/\A[1-9][0-9]*\z/', $submitted_attempt_id)
        ) {
            return '<p>' . esc_html__('No attempt selected.', 'eas-quiz') . '</p>';
        }

        $attempt_id = filter_var(
            $submitted_attempt_id,
            FILTER_VALIDATE_INT,
            array('options' => array('min_range' => 1))
        );

        if ($attempt_id === false) {
            return '<p>' . esc_html__('No attempt selected.', 'eas-quiz') . '</p>';
        }

        /*
         * Validate the optional review switch as a scalar containing 0 or 1.
         */
        $submitted_review = null;

        if (isset($_GET['review'])) {
            $raw_review = wp_unslash($_GET['review']);

            if (!is_scalar($raw_review)) {
                return '<p>' . esc_html__('Invalid result request.', 'eas-quiz') . '</p>';
            }

            $submitted_review = sanitize_text_field(
                (string) $raw_review
            );

            if (!preg_match('/\A[01]\z/', $submitted_review)) {
                return '<p>' . esc_html__('Invalid result request.', 'eas-quiz') . '</p>';
            }
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

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