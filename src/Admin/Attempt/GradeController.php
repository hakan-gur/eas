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

    array(

        $this,

        'save'

    )

);

    }





    public function index(): void
    {


        $attempt_id = (int) (

            $_GET['attempt_id'] ?? 0

        );



        if (!$attempt_id) {

            wp_die('Invalid attempt.');

        }




        $attempt = $this->attempts->find_with_details(
    $attempt_id
);



        $responses = $this->responses->all_by_attempt(

            $attempt_id

        );



        require EAS_PLUGIN_DIR .

            'views/admin/attempts/grade.php';


    }
public function save(): void
{

    check_admin_referer(
        'eas_save_grades'
    );


    $attempt_id = (int) (

        $_POST['attempt_id'] ?? 0

    );


    if (!$attempt_id) {

        wp_die(
            'Invalid attempt.'
        );

    }



    $responses = $this->responses->all_by_attempt(

        $attempt_id

    );



    foreach ($responses as $response) {


        $item_id = (int) $response['item_id'];



        $points = (float) (

            $_POST[

                'points_' . $item_id

            ] ?? 0

        );



        $feedback = sanitize_textarea_field(

            $_POST[

                'feedback_' . $item_id

            ] ?? ''

        );



        $this->responses->update_grade(

            $attempt_id,

            $item_id,

            $points,

            $feedback,

            get_current_user_id()

        );


    }



    wp_redirect(

        admin_url(

            'admin.php?page=eas-attempt-grade&attempt_id='

            . $attempt_id

            . '&graded=1'

        )

    );


    exit;

}

}