<?php

declare(strict_types=1);

namespace EAS\Response;

if (!defined('ABSPATH')) {
    exit;
}


class ResponseRepository
{

    private \wpdb $wpdb;


    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;
    }



    private function table(): string
    {
        return $this->wpdb->prefix . 'eas_responses';
    }



    public function create(
    int $attempt_id,
    int $item_id,
    string $answer,
    float $points_awarded = 0
): int
{
    $this->wpdb->insert(

        $this->table(),

        array(

            'attempt_id' => $attempt_id,

            'item_id' => $item_id,

            'answer' => $answer,

            'points_awarded' => $points_awarded,

        ),

        array(

            '%d',

            '%d',

            '%s',

            '%f'

        )

    );

    return (int) $this->wpdb->insert_id;
}


public function all_by_attempt(
    int $attempt_id
): array
{


    return $this->wpdb->get_results(


        $this->wpdb->prepare(

            "SELECT 
                r.*,
                i.question_text,
                i.type,
                i.points AS max_points

             FROM {$this->table()} r

             LEFT JOIN {$this->wpdb->prefix}eas_items i

             ON r.item_id = i.id

             WHERE r.attempt_id = %d

             ORDER BY r.id ASC",


            $attempt_id

        ),


        ARRAY_A


    );


}




    public function find(
        int $attempt_id,
        int $item_id
    ): ?array
    {

        $response = $this->wpdb->get_row(

            $this->wpdb->prepare(

                "SELECT *
                 FROM {$this->table()}
                 WHERE attempt_id = %d
                 AND item_id = %d
                 LIMIT 1",

                $attempt_id,

                $item_id

            ),

            ARRAY_A

        );


        return $response ?: null;

    }

public function update_answer(
    int $attempt_id,
    int $item_id,
    string $answer
): bool
{
    $result = $this->wpdb->update(
        $this->table(),

        array(
            'answer' => $answer,
            'points_awarded' => 0
        ),

        array(
            'attempt_id' => $attempt_id,
            'item_id' => $item_id
        ),

        array(
            '%s',
            '%f'
        ),

        array(
            '%d',
            '%d'
        )
    );

    return $result !== false;
}


    public function delete_by_attempt(
        int $attempt_id
    ): bool
    {

        return (bool) $this->wpdb->delete(

            $this->table(),

            array(

                'attempt_id' => $attempt_id

            ),

            array(

                '%d'

            )

        );

    }




    public function count_by_attempt(
        int $attempt_id
    ): int
    {

        return (int) $this->wpdb->get_var(

            $this->wpdb->prepare(

                "SELECT COUNT(*)
                 FROM {$this->table()}
                 WHERE attempt_id = %d",

                $attempt_id

            )

        );

    }
public function update_points(

    int $attempt_id,

    int $item_id,

    float $points

): bool
{


    return (bool) $this->wpdb->update(

        $this->table(),

        array(

            'points_awarded' => $points

        ),

        array(

            'attempt_id' => $attempt_id,

            'item_id' => $item_id

        ),

        array(

            '%f'

        ),

        array(

            '%d',

            '%d'

        )

    );


}

public function update_grade(

    int $attempt_id,

    int $item_id,

    float $points,

    string $feedback,

    int $graded_by

): bool
{


    return (bool) $this->wpdb->update(

        $this->table(),

        array(

            'points_awarded' => $points,

            'teacher_feedback' => $feedback,

            'graded_by' => $graded_by,

            'graded_at' => current_time('mysql')

        ),

        array(

            'attempt_id' => $attempt_id,

            'item_id' => $item_id

        ),

        array(

            '%f',

            '%s',

            '%d',

            '%s'

        ),

        array(

            '%d',

            '%d'

        )

    );


}
}