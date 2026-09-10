<?php

declare(strict_types=1);

namespace EAS\Frontend;


use EAS\Quiz\QuizRepository;
use EAS\Attempt\AttemptRepository;
use EAS\Core\View;


if (!defined('ABSPATH')) {
    exit;
}


class QuizRenderer
{


    private QuizRepository $repository;

    private AttemptRepository $attempts;


    public function __construct()
    {

        $this->repository = new QuizRepository();

        $this->attempts = new AttemptRepository();

    }



    public function render(int $quiz_id): string
    {


        $quiz = $this->repository->find(

            $quiz_id

        );


        if (!$quiz) {


            return '<p>' . esc_html__('Quiz not found.', 'eas-quiz') . '</p>';


        }



        $completed_attempts = 0;

        $remaining_attempts = null;



        if (is_user_logged_in()) {


            $completed_attempts = $this->attempts->count_completed_attempts(

                get_current_user_id(),

                $quiz_id

            );


        }



        /*
         * Administrators are exempt from the attempt limit.
         *
         * Keep remaining_attempts as null for administrators so
         * the Start Attempt button is always displayed.
         *
         * Students remain subject to the configured attempt limit.
         */

        if (!current_user_can('manage_options')) {

            $allowed = (int) $quiz['attempts_allowed'];


            if ($allowed > 0) {

                $remaining_attempts = max(

                    0,

                    $allowed - $completed_attempts

                );

            }

        }



        return View::render(

            'frontend/quiz/start',

            [

                'quiz' => $quiz,

                'completed_attempts' => $completed_attempts,

                'remaining_attempts' => $remaining_attempts

            ]

        );


    }


}