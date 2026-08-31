<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
 * Recover the student's previously saved answer.
 *
 * The response may contain:
 *
 * {"1":"ey","2":"ticated","3":"tures"}
 *
 * or, in the current EAS response format, it may contain
 * the question text followed by the JSON response:
 *
 * Question text..., {"1":"ey","2":"ticated","3":"tures"}
 *
 * We therefore extract the final JSON object from the response.
 */

$saved_answers = array();

if (
    is_array($response)
    &&
    !empty($response['answer'])
) {

    $saved_answer = (string) $response['answer'];

    /*
     * First try the complete response as JSON.
     */
    $decoded = json_decode(
        stripslashes($saved_answer),
        true
    );

    if (
        is_array($decoded)
        &&
        isset($decoded['1'])
    ) {

        $saved_answers = $decoded;

    } else {

        /*
         * The current stored value may contain additional
         * text before the JSON object.
         *
         * Extract everything from the final opening {
         * to the final }.
         */
        $json_start = strrpos(
            $saved_answer,
            '{'
        );

        $json_end = strrpos(
            $saved_answer,
            '}'
        );

        if (
            $json_start !== false
            &&
            $json_end !== false
            &&
            $json_end > $json_start
        ) {

            $json = substr(
                $saved_answer,
                $json_start,
                $json_end - $json_start + 1
            );

            $decoded = json_decode(
                stripslashes($json),
                true
            );

            if (is_array($decoded)) {

                $saved_answers = $decoded;

            }

        }

    }
}

?>

<div class="eas-question eas-fill-blank">

    <!--
     * Hidden field containing the complete response.
     *
     * The existing JavaScript can update this field while
     * the student is answering the question.
     -->
    <input type="hidden"
           class="eas-fill-answer"
           name="question_<?php echo esc_attr($question['id']); ?>"
           value="<?php echo esc_attr(wp_json_encode($saved_answers)); ?>">


    <div class="eas-question-text">

        <?php

        $text = $question['question_text'];

        /*
         * Convert [letters] into TOEFL C-Test blanks.
         *
         * Example:
         *
         * won[der]
         *
         * becomes:
         *
         * won + two input boxes
         *
         * If a saved response exists, the corresponding
         * letters are placed back into those input boxes.
         */

        $counter = 1;

        $text = preg_replace_callback(

            '/\[([^\]]+)\]/',

            function ($matches) use (
                &$counter,
                $saved_answers
            ) {

                $word = $matches[1];

                /*
                 * Saved answer for this blank.
                 *
                 * Example:
                 *
                 * $saved_answers[1] = "ey"
                 */
                $saved_word = (string) (
                    $saved_answers[
                        (string) $counter
                    ]
                    ??
                    $saved_answers[
                        $counter
                    ]
                    ??
                    ''
                );

                $html =
                    '<span class="eas-c-test-blank" data-position="' .
                    esc_attr($counter) .
                    '">';


                /*
                 * Create one input for each missing letter.
                 */
                for (
                    $i = 0;
                    $i < strlen($word);
                    $i++
                ) {

                    /*
                     * Recover the student's letter at
                     * this position.
                     */
                    $letter = '';

                    if (
                        isset(
                            $saved_word[$i]
                        )
                    ) {

                        $letter =
                            $saved_word[$i];

                    }

                    $html .=
                        '<input type="text"
                                maxlength="1"
                                class="eas-letter-input"
                                data-position="' .
                                esc_attr($counter) .
                                '"
                                data-letter="' .
                                esc_attr($i) .
                                '"
                                value="' .
                                esc_attr($letter) .
                                '">';

                }


                $html .= '</span>';

                $counter++;

                return $html;

            },

            $text

        );


        echo wp_kses(

            $text,

            array(

                'span' => array(

                    'class' => array(),

                    'data-position' => array()

                ),

                'input' => array(

                    'type' => array(),

                    'maxlength' => array(),

                    'class' => array(),

                    'data-position' => array(),

                    'data-letter' => array(),

                    'value' => array()

                ),

                'br' => array(),

                'strong' => array(),

                'em' => array(),

                'i' => array()

            )

        );

        ?>

    </div>

</div>