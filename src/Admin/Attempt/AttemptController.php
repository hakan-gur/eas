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
        array(
            $this,
            'delete'
        )
    );
}

    public function index(): void
    {

        $quiz_id = (int) (
    $_GET['quiz_id'] ?? 0
);


$status = sanitize_text_field(
    $_GET['status'] ?? ''
);



$attempts = $this->attempts->all_completed_filtered(
    $quiz_id,
    $status
);

        foreach ($attempts as &$attempt) {

            $attempt['grading_status'] =
                $this->attempts->grading_status(
                    (int) $attempt['id']
                );

            $attempt['max_points'] =
                $this->quizzes->max_points(
                    (int) $attempt['quiz_id']
                );
if (
    !empty($attempt['started_at'])
    &&
    !empty($attempt['completed_at'])
) {

    $start = strtotime(
        $attempt['started_at']
    );

    $end = strtotime(
        $attempt['completed_at']
    );


    $seconds = $end - $start;


    $minutes = floor(
        $seconds / 60
    );


    $remaining_seconds = $seconds % 60;


    $attempt['duration'] =
        $minutes . ' min ' .
        $remaining_seconds . ' sec';


} else {


    $attempt['duration'] = '-';


}
        }

        unset($attempt);

        require EAS_PLUGIN_DIR .
            'views/admin/attempts/index.php';

    }
public function delete(): void
{
    /*
     * Only logged-in WordPress users with the
     * appropriate administrative capability may
     * delete attempts.
     */
    if (!current_user_can('manage_options')) {

        wp_die(
            'You do not have permission to delete attempts.'
        );

    }

    /*
     * Get the attempt ID.
     */
    $attempt_id = (int) (
        $_GET['id'] ?? 0
    );

    if ($attempt_id <= 0) {

        wp_die(
            'Invalid attempt.'
        );

    }

    /*
     * Verify the deletion nonce.
     */
    check_admin_referer(
        'eas_delete_attempt_' . $attempt_id
    );

    /*
     * Make sure the attempt actually exists.
     */
    $attempt = $this->attempts->find(
        $attempt_id
    );

    if (!$attempt) {

        wp_die(
            'Attempt not found.'
        );

    }

    /*
     * Delete the attempt and all associated
     * responses/audio records.
     */
    $this->attempts->delete(
        $attempt_id
    );

    /*
     * Return to the Attempts page.
     */
    wp_redirect(
        admin_url(
            'admin.php?page=eas-attempts'
        )
    );

    exit;
}
}