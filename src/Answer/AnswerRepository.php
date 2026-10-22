<?php

declare(strict_types=1);

namespace EAS\Answer;


if (!defined('ABSPATH')) {
    exit;
}


class AnswerRepository
{


    private \wpdb $wpdb;



    public function __construct()
    {

        global $wpdb;

        $this->wpdb = $wpdb;

    }



    private function table(): string
    {

        return $this->wpdb->prefix . 'eas_answers';

    }



    public function all_by_question(int $question_id): array
    {


        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- The table name uses the trusted WordPress prefix and fixed EAS suffix; the question ID is prepared.
        return $this->wpdb->get_results(

            $this->wpdb->prepare(

                "SELECT *
                 FROM {$this->table()}
                 WHERE question_id = %d
                 ORDER BY sort_order ASC, id ASC",

                $question_id

            ),

            ARRAY_A

        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter


    }



    public function find(int $id): ?array
    {


        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- The table name uses the trusted WordPress prefix and fixed EAS suffix; the answer ID is prepared.
        $answer = $this->wpdb->get_row(

            $this->wpdb->prepare(

                "SELECT *
                 FROM {$this->table()}
                 WHERE id = %d",

                $id

            ),

            ARRAY_A

        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter


        return $answer ?: null;


    }



    public function create(
        int $question_id,
        string $answer_text,
        bool $is_correct = false
    ): int
    {


        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- The table name uses the trusted WordPress prefix and fixed EAS suffix; the question ID is prepared.
        $sort_order = (int) $this->wpdb->get_var(

            $this->wpdb->prepare(

                "SELECT MAX(sort_order)
                 FROM {$this->table()}
                 WHERE question_id = %d",

                $question_id

            )

        );
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter


        $sort_order++;



        $this->wpdb->insert(

            $this->table(),

            array(

                'question_id' => $question_id,

                'answer_text' => $answer_text,

                'is_correct' => $is_correct ? 1 : 0,

                'sort_order' => $sort_order,

                'created_at' => current_time('mysql'),

                'updated_at' => current_time('mysql')

            )

        );



        return (int) $this->wpdb->insert_id;


    }



    public function update(
        int $id,
        string $answer_text,
        bool $is_correct
    ): bool
    {


        return (bool) $this->wpdb->update(

            $this->table(),

            array(

                'answer_text' => $answer_text,

                'is_correct' => $is_correct ? 1 : 0,

                'updated_at' => current_time('mysql')

            ),

            array(

                'id' => $id

            )

        );


    }



    public function delete(int $id): bool
    {


        return (bool) $this->wpdb->delete(

            $this->table(),

            array(

                'id' => $id

            )

        );


    }



    public function delete_by_question(int $question_id): bool
    {


        return (bool) $this->wpdb->delete(

            $this->table(),

            array(

                'question_id' => $question_id

            )

        );


    }



    public function clear_correct_answers(
        int $question_id
    ): bool
    {


        return (bool) $this->wpdb->update(

            $this->table(),

            array(

                'is_correct' => 0

            ),

            array(

                'question_id' => $question_id

            )

        );


    }
public function duplicate_by_question(

    int $old_question_id,

    int $new_question_id

): void
{

    $answers = $this->all_by_question(

        $old_question_id

    );


    foreach ($answers as $answer) {


        $this->wpdb->insert(

            $this->table(),

            array(

                'question_id' => $new_question_id,

                'answer_text' => $answer['answer_text'],

                'is_correct' => $answer['is_correct'],

                'sort_order' => $answer['sort_order'],

                'created_at' => current_time('mysql'),

                'updated_at' => current_time('mysql')

            )

        );


    }

}

}