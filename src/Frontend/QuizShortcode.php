<?php

declare(strict_types=1);

namespace EAS\Frontend;


use EAS\Frontend\QuizRenderer;


if (!defined('ABSPATH')) {
    exit;
}


class QuizShortcode
{


    private QuizRenderer $renderer;



    public function __construct()
    {

        $this->renderer = new QuizRenderer();


    }



    public function register(): void
    {


        add_shortcode(

            'eas_quiz',

            array(

                $this,

                'render'

            )

        );


    }





    public function render(array $atts): string
    {


        $atts = shortcode_atts(

            array(

                'id' => 0

            ),

            $atts

        );



        $quiz_id = (int) $atts['id'];



        if ($quiz_id <= 0) {


            return '<p>' . esc_html__('Quiz ID missing.', 'eas-quiz') . '</p>';


        }



        return $this->renderer->render(

            $quiz_id

        );


    }


}