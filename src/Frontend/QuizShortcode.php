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



        $submitted_id = $atts['id'];

        if ($submitted_id === 0 || $submitted_id === '0' || $submitted_id === '') {
            return '<p>' . esc_html__('Quiz ID missing.', 'eas-quiz') . '</p>';
        }

        if (
            !is_scalar($submitted_id)
            || !preg_match('/\A[1-9][0-9]*\z/', (string) $submitted_id)
        ) {
            return '<p>' . esc_html__('Invalid quiz ID.', 'eas-quiz') . '</p>';
        }

        $quiz_id = filter_var(
            (string) $submitted_id,
            FILTER_VALIDATE_INT,
            array('options' => array('min_range' => 1))
        );

        if ($quiz_id === false) {
            return '<p>' . esc_html__('Invalid quiz ID.', 'eas-quiz') . '</p>';
        }



        return $this->renderer->render(

            $quiz_id

        );


    }


}