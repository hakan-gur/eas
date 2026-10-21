<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
 * Recover the student's previously saved answer.
 *
 * Expected format:
 *
 * {"1":"to","2":"some"}
 *
 * The value may also contain escaped JSON.
 */

$saved_answers = array();

if (
    is_array($response)
    &&
    !empty($response['answer'])
) {

    $saved_answer =
        (string) $response['answer'];

    /*
     * First try the complete value as JSON.
     */
    $decoded = json_decode(
        stripslashes($saved_answer),
        true
    );

    if (is_array($decoded)) {

        $saved_answers = $decoded;

    } else {

        /*
         * Be tolerant if additional text exists
         * before the JSON object.
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

<div class="eas-question eas-drag-drop-text">

    <input type="hidden"
           class="eas-drag-answer"
           name="question_<?php echo esc_attr($question['id']); ?>"
           value="<?php echo esc_attr(wp_json_encode($saved_answers)); ?>">


    <?php

    /*
     * Question Description
     *
     * Keep the original HTML so that paragraphs,
     * headings, bold, italics, line breaks, etc.
     * remain available.
     *
     * Shortcodes are also processed here, allowing
     * H5P and other WordPress shortcodes to work.
     */

    $text = $question['question_text'];


    /*
     * Convert [[1]] ... [[7]] into drop zones.
     *
     * If a saved response exists, put the saved word
     * inside the corresponding drop zone.
     */

    for ($i = 1; $i <= 7; $i++) {

        /*
         * Look for the student's saved word.
         *
         * Support both string and integer array keys.
         */
        $saved_word = (string) (
            $saved_answers[
                (string) $i
            ]
            ??
            $saved_answers[
                $i
            ]
            ??
            ''
        );

        /*
         * Escape the saved word before inserting it
         * into the HTML.
         */
        $display_word = esc_html(
            $saved_word
        );

        /*
         * If the blank has an answer, display it.
         * Otherwise display the original placeholder.
         */
        if ($display_word !== '') {

            $blank_content =
                $display_word;

        } else {

            $blank_content =
                '______';

        }


        $replacement =
            '<span class="eas-drop-blank"
                   data-position="' .
                   esc_attr($i) .
                   '"
                   data-answer="' .
                   esc_attr($saved_word) .
                   '">' .
                   $blank_content .
                   '</span>';


        $text = str_replace(

            '[[' . $i . ']]',

            $replacement,

            $text

        );

    }


    /*
     * Process WordPress shortcodes.
     *
     * This allows H5P and other registered shortcodes
     * inside the Question Description field.
     */

    $text = do_shortcode(
        $text
    );


    /*
     * Allow the normal WordPress post HTML plus
     * the attributes required by the drag-and-drop
     * drop zones.
     */

    $allowed_html =
        wp_kses_allowed_html('post');


    $allowed_html['span'] = array(

        'class' => true,

        'data-position' => true,

        'data-answer' => true

    );



    ?>


    <div class="eas-question-text"
         data-question-text="<?php echo esc_attr(
             $question['question_text']
         ); ?>">

        <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses() sanitizes the question HTML with the post allowlist and required drag-and-drop span attributes.
        echo wp_kses($text, $allowed_html); ?>

    </div>


    <hr>


    <div class="eas-word-bank">

        <!-- <h3>Word Bank</h3> -->

        <div class="eas-drag-options">

            <?php foreach ($options as $option): ?>

                <span class="eas-draggable-word"
                      draggable="true">

                    <?php echo esc_html(
                        $option['answer_text']
                    ); ?>

                </span>

            <?php endforeach; ?>

        </div>

    </div>

</div>