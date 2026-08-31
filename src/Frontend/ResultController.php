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

            return '<p>Please log in to view your result.</p>';

        }


        $attempt_id = (int) (
            $_GET['attempt_id'] ?? 0
        );


        if ($attempt_id <= 0) {

            return '<p>No attempt selected.</p>';

        }


        /*
         * Verify that the attempt exists.
         */
        $attempt =
            $this->attempts->find(
                $attempt_id
            );


        if (!$attempt) {

            return '<p>Attempt not found.</p>';

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

            return '<p>You are not allowed to view this result.</p>';

        }


        /*
         * Only completed attempts can be viewed.
         */
        if (
            ($attempt['status'] ?? '')
            !== 'completed'
        ) {

            return '<p>This quiz attempt has not been completed.</p>';

        }


        $review =
            !empty($_GET['review']);


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