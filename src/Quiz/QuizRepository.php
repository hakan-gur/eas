<?php

declare(strict_types=1);

namespace EAS\Quiz;

use EAS\Page\PageRepository;
use EAS\Question\QuestionRepository;
use EAS\Answer\AnswerRepository;

if (!defined('ABSPATH')) {
    exit;
}

class QuizRepository
{
    private \wpdb $wpdb;

    private PageRepository $pages;

    private QuestionRepository $questions;

    private AnswerRepository $answers;

    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;

        $this->pages = new PageRepository();

        $this->questions = new QuestionRepository();

        $this->answers = new AnswerRepository();
    }

    private function table(): string
    {
        return $this->wpdb->prefix . 'eas_quizzes';
    }

    private function items_table(): string
    {
        return $this->wpdb->prefix . 'eas_items';
    }

    private function pages_table(): string
    {
        return $this->wpdb->prefix . 'eas_pages';
    }

    private function page_items_table(): string
    {
        return $this->wpdb->prefix . 'eas_page_items';
    }

    private function answers_table(): string
    {
        return $this->wpdb->prefix . 'eas_answers';
    }

    private function attempts_table(): string
    {
        return $this->wpdb->prefix . 'eas_attempts';
    }

    private function responses_table(): string
    {
        return $this->wpdb->prefix . 'eas_responses';
    }

    public function all(): array
    {
        return $this->wpdb->get_results(
            "SELECT *
             FROM {$this->table()}
             ORDER BY id DESC",
            ARRAY_A
        );
    }

    public function find(int $id): ?array
    {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT *
                 FROM {$this->table()}
                 WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

        return $result ?: null;
    }

    public function create(array $data): int
    {
        $this->wpdb->insert(
            $this->table(),
            $data
        );

        return (int) $this->wpdb->insert_id;
    }

    public function update(
        int $id,
        array $data
    ): bool {
        return (bool) $this->wpdb->update(
            $this->table(),
            $data,
            array(
                'id' => $id
            )
        );
    }

    public function delete(int $id): bool
    {
        $this->wpdb->query('START TRANSACTION');

        try {

            /*
             * Delete responses belonging to attempts
             * of this quiz.
             */
            $this->wpdb->query(
                $this->wpdb->prepare(
                    "
                    DELETE r
                    FROM {$this->responses_table()} r
                    INNER JOIN {$this->attempts_table()} a
                        ON r.attempt_id = a.id
                    WHERE a.quiz_id = %d
                    ",
                    $id
                )
            );

            /*
             * Delete attempts.
             */
            $deleted_attempts = $this->wpdb->delete(
                $this->attempts_table(),
                array(
                    'quiz_id' => $id
                ),
                array(
                    '%d'
                )
            );

            if ($deleted_attempts === false) {
                throw new \Exception(
                    'Attempt deletion failed.'
                );
            }

            /*
             * Delete page-question relationships
             * for pages belonging to this quiz.
             */
            $this->wpdb->query(
                $this->wpdb->prepare(
                    "
                    DELETE pi
                    FROM {$this->page_items_table()} pi
                    INNER JOIN {$this->pages_table()} p
                        ON pi.page_id = p.id
                    WHERE p.quiz_id = %d
                    ",
                    $id
                )
            );

            /*
             * Delete answers belonging to the
             * questions in this quiz's question bank.
             */
            $this->wpdb->query(
                $this->wpdb->prepare(
                    "
                    DELETE a
                    FROM {$this->answers_table()} a
                    INNER JOIN {$this->items_table()} i
                        ON a.question_id = i.id
                    WHERE i.quiz_id = %d
                    ",
                    $id
                )
            );

            /*
             * Delete the questions from this quiz's
             * question bank.
             *
             * Questions are owned by the quiz, not
             * by individual pages.
             */
            $deleted_items = $this->wpdb->query(
                $this->wpdb->prepare(
                    "
                    DELETE FROM {$this->items_table()}
                    WHERE quiz_id = %d
                    ",
                    $id
                )
            );

            if ($deleted_items === false) {
                throw new \Exception(
                    'Question deletion failed.'
                );
            }

            /*
             * Delete pages.
             */
            $deleted_pages = $this->wpdb->delete(
                $this->pages_table(),
                array(
                    'quiz_id' => $id
                ),
                array(
                    '%d'
                )
            );

            if ($deleted_pages === false) {
                throw new \Exception(
                    'Page deletion failed.'
                );
            }

            /*
             * Finally delete the quiz.
             */
            $deleted = $this->wpdb->delete(
                $this->table(),
                array(
                    'id' => $id
                ),
                array(
                    '%d'
                )
            );

            if ($deleted === false) {
                throw new \Exception(
                    'Quiz deletion failed.'
                );
            }

            $this->wpdb->query(
                'COMMIT'
            );

            return true;

        } catch (\Throwable $e) {

            $this->wpdb->query(
                'ROLLBACK'
            );

            return false;
        }
    }

    public function max_points(int $quiz_id): float
    {
        return (float) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "
                SELECT COALESCE(SUM(i.points), 0)

                FROM {$this->items_table()} i

                INNER JOIN {$this->page_items_table()} pi
                    ON i.id = pi.item_id

                INNER JOIN {$this->pages_table()} p
                    ON pi.page_id = p.id

                WHERE p.quiz_id = %d
                ",
                $quiz_id
            )
        );
    }

    public function count(): int
    {
        return (int) $this->wpdb->get_var(
            "SELECT COUNT(*)
             FROM {$this->table()}"
        );
    }

    public function duplicate(int $quiz_id): int
    {
        $quiz = $this->find($quiz_id);

        if (!$quiz) {
            return 0;
        }

        $new_title = $quiz['title'] . ' (Copy)';

        $copy_number = 2;

        while (
            $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT COUNT(*)
                     FROM {$this->table()}
                     WHERE title = %s",
                    $new_title
                )
            )
        ) {
            $new_title =
                $quiz['title'] .
                ' (Copy ' .
                $copy_number .
                ')';

            $copy_number++;
        }

        $data = array(
            'title' =>
                $new_title,

            'description' =>
                $quiz['description'],

            'allow_back_navigation' =>
                $quiz['allow_back_navigation'],

            'attempts_allowed' =>
                $quiz['attempts_allowed'],

            'time_limit' =>
                $quiz['time_limit'],

            'backward_navigation' =>
                $quiz['backward_navigation'],

            'shuffle_options' =>
                $quiz['shuffle_options'],

            'created_by' =>
                get_current_user_id(),

            'created_at' =>
                current_time('mysql'),

            'updated_at' =>
                current_time('mysql')
        );

        $this->wpdb->insert(
            $this->table(),
            $data
        );

        $new_quiz_id =
            (int) $this->wpdb->insert_id;

        if ($new_quiz_id <= 0) {
            return 0;
        }

        /*
         * Copy pages.
         */
        $page_map =
            $this->pages->duplicate_pages(
                $quiz_id,
                $new_quiz_id
            );

        /*
         * Copy the entire question bank.
         *
         * Questions are copied independently
         * of pages.
         */
        $questions =
            $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "
                    SELECT *
                    FROM {$this->items_table()}
                    WHERE quiz_id = %d
                    ORDER BY id ASC
                    ",
                    $quiz_id
                ),
                ARRAY_A
            );

        $question_map = array();

        foreach ($questions as $question) {

            $question_data = array(
                'quiz_id' =>
                    $new_quiz_id,

                'type' =>
                    $question['type'],

                'question_text' =>
                    $question['question_text'],

                'settings' =>
                    $question['settings'],

                'feedback' =>
                    $question['feedback'],

                'points' =>
                    $question['points'],

                'created_at' =>
                    current_time('mysql'),

                'updated_at' =>
                    current_time('mysql')
            );

            $this->wpdb->insert(
                $this->items_table(),
                $question_data
            );

            $new_question_id =
                (int) $this->wpdb->insert_id;

            if ($new_question_id <= 0) {
                continue;
            }

            $old_question_id =
                (int) $question['id'];

            $question_map[
                $old_question_id
            ] = $new_question_id;

            /*
             * Copy answers.
             */
            $answers =
                $this->wpdb->get_results(
                    $this->wpdb->prepare(
                        "
                        SELECT *
                        FROM {$this->answers_table()}
                        WHERE question_id = %d
                        ORDER BY sort_order ASC, id ASC
                        ",
                        $old_question_id
                    ),
                    ARRAY_A
                );

            foreach ($answers as $answer) {

                $this->wpdb->insert(
                    $this->answers_table(),
                    array(
                        'question_id' =>
                            $new_question_id,

                        'answer_text' =>
                            $answer['answer_text'],

                        'is_correct' =>
                            $answer['is_correct'],

                        'correct_position' =>
                            $answer['correct_position'],

                        'sort_order' =>
                            $answer['sort_order'],

                        'created_at' =>
                            current_time('mysql'),

                        'updated_at' =>
                            current_time('mysql'),

                        'feedback' =>
                            $answer['feedback']
                    )
                );
            }
        }

        /*
         * Re-create the page-question relationships.
         */
        foreach ($page_map as $old_page_id => $new_page_id) {

            $relationships =
                $this->wpdb->get_results(
                    $this->wpdb->prepare(
                        "
                        SELECT *
                        FROM {$this->page_items_table()}
                        WHERE page_id = %d
                        ORDER BY sort_order ASC, id ASC
                        ",
                        (int) $old_page_id
                    ),
                    ARRAY_A
                );

            foreach ($relationships as $relationship) {

                $old_question_id =
                    (int) $relationship['item_id'];

                if (
                    !isset(
                        $question_map[$old_question_id]
                    )
                ) {
                    continue;
                }

                $this->wpdb->insert(
                    $this->page_items_table(),
                    array(
                        'page_id' =>
                            (int) $new_page_id,

                        'item_id' =>
                            $question_map[
                                $old_question_id
                            ],

                        'sort_order' =>
                            $relationship['sort_order']
                    )
                );
            }
        }

        return $new_quiz_id;
    }

    public function page_count(int $quiz_id): int
    {
        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "
                SELECT COUNT(*)
                FROM {$this->pages_table()}
                WHERE quiz_id = %d
                ",
                $quiz_id
            )
        );
    }

    public function question_count(int $quiz_id): int
    {
        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "
                SELECT COUNT(*)

                FROM {$this->items_table()} i

                WHERE i.quiz_id = %d
                ",
                $quiz_id
            )
        );
    }
}