<?php
declare(strict_types=1);

namespace EAS\Import;

if (!defined('ABSPATH')) {
    exit;
}

class GiftParser
{
    /**
     * Parse Moodle GIFT content.
     *
     * Currently supports:
     * - Description questions
     * - Multiple-choice questions
     * - Question numbers in ::number::
     * - ~ incorrect answers
     * - = correct answers
     * - #### general feedback
     * - HTML in question text and feedback
     */
    public function parse(string $content): array
    {
        $content = str_replace(
            array("\r\n", "\r"),
            "\n",
            $content
        );

        $content = trim($content);

        if ($content === '') {
            return array();
        }

        /*
         * A GIFT question starts with:
         *
         * ::identifier::question text
         *
         * We deliberately keep the identifier because
         * it will be placed at the beginning of the
         * EAS question text.
         */
        preg_match_all(
            '/::([^:]+)::(.*?)(?=\n\s*::[^:]+::|\z)/s',
            $content,
            $matches,
            PREG_SET_ORDER
        );

        $questions = array();

        foreach ($matches as $match) {

            $gift_number = trim($match[1]);
$number = (string) ((int) $gift_number);
            $body   = trim($match[2]);

            if ($body === '') {
                continue;
            }

            /*
             * Determine whether this is a multiple-choice
             * question by looking for the answer block.
             */
            if (
                preg_match(
                    '/\{(.*)\}/s',
                    $body,
                    $answer_match
                )
            ) {
                $questions[] =
                    $this->parse_multiple_choice(
                        $number,
                        $body,
                        $answer_match[0]
                    );
            } else {

                /*
                 * No answer block = Description.
                 */
                $questions[] = array(
                    'type' => 'description',

                    /*
                     * Keep the GIFT number as part of the
                     * question text.
                     */
                    'question_text' =>
                        $number . '. ' . $body,

                    'feedback' => '',
                    'answers' => array(),

                    /*
                     * Keep the original number available
                     * to the importer for later ordering.
                     */
                    'gift_number' => $gift_number
                );
            }
        }

        return $questions;
    }

    /**
     * Parse a Multiple Choice GIFT question.
     */
    private function parse_multiple_choice(
        string $number,
        string $body,
        string $answer_block
    ): array {

        /*
         * Remove the complete {...} answer block from
         * the question text.
         */
        $question_text = trim(
            substr(
                $body,
                0,
                strpos($body, $answer_block)
            )
        );

        /*
         * Remove the surrounding braces.
         */
        $answers_text = trim(
            substr(
                $answer_block,
                1,
                -1
            )
        );

        /*
         * Extract General Feedback.
         *
         * #### marks the beginning of General Feedback.
         */
        $feedback = '';

        $feedback_parts = preg_split(
            '/####/',
            $answers_text,
            2
        );

        $options_text =
            trim($feedback_parts[0]);

        if (isset($feedback_parts[1])) {
            $feedback =
                trim($feedback_parts[1]);
        }

        /*
         * Parse answer options.
         *
         * Each option begins with:
         *
         * ~ incorrect
         * = correct
         */
        preg_match_all(
            '/(?:^|\n)\s*([~=])([^\n]*)/m',
            $options_text,
            $option_matches,
            PREG_SET_ORDER
        );

        $answers = array();

        $position = 1;

        foreach ($option_matches as $option) {

            $marker =
                trim($option[1]);

            $text =
                trim($option[2]);

            if ($text === '') {
                continue;
            }

            $answers[] = array(
                'text' => $text,
                'correct' =>
                    ($marker === '='),
                'correct_position' =>
                    ($marker === '=')
                        ? $position
                        : 0
            );

            $position++;
        }

        return array(
            'type' => 'multiple_choice',

            /*
             * Preserve the GIFT number in the question text.
             */
            'question_text' =>
                $number . '. ' . $question_text,

            /*
             * General Feedback from ####.
             */
            'feedback' => $feedback,

            'answers' => $answers,

            /*
             * Retain the number for the importer.
             */
            'gift_number' => $gift_number
        );
    }
}