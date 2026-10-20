<?php

declare(strict_types=1);

namespace EAS\Page;


if (!defined('ABSPATH')) {
    exit;
}


class PageRepository
{


    private \wpdb $wpdb;



    public function __construct()
    {

        global $wpdb;

        $this->wpdb = $wpdb;

    }





    private function table(): string
    {

        return $this->wpdb->prefix . 'eas_pages';

    }





    public function all_by_quiz(int $quiz_id): array
{
    return $this->wpdb->get_results(

        $this->wpdb->prepare(

            "
            SELECT
                p.*,
                COUNT(pi.item_id) AS question_count,
                COALESCE(SUM(i.points), 0) AS page_points

            FROM {$this->table()} p

            LEFT JOIN {$this->wpdb->prefix}eas_page_items pi
                ON p.id = pi.page_id

            LEFT JOIN {$this->wpdb->prefix}eas_items i
                ON pi.item_id = i.id

            WHERE p.quiz_id = %d

            GROUP BY p.id

            ORDER BY p.page_number ASC
            ",

            $quiz_id

        ),

        ARRAY_A

    );
}





    public function firstByQuiz(int $quiz_id): ?array
    {

        $page = $this->wpdb->get_row(

            $this->wpdb->prepare(

                "SELECT *
                 FROM {$this->table()}
                 WHERE quiz_id = %d
                 ORDER BY page_number ASC, id ASC
                 LIMIT 1",

                $quiz_id

            ),

            ARRAY_A

        );


        return $page ?: null;

    }





    public function find(int $id): ?array
    {

        $page = $this->wpdb->get_row(

            $this->wpdb->prepare(

                "SELECT *
                 FROM {$this->table()}
                 WHERE id = %d",

                $id

            ),

            ARRAY_A

        );


        return $page ?: null;

    }





    public function nextByQuiz(

        int $quiz_id,

        int $current_page_number

    ): ?array
    {


        $page = $this->wpdb->get_row(

            $this->wpdb->prepare(

                "SELECT *
                 FROM {$this->table()}
                 WHERE quiz_id = %d
                 AND page_number > %d
                 ORDER BY page_number ASC
                 LIMIT 1",

                $quiz_id,

                $current_page_number

            ),

            ARRAY_A

        );


        return $page ?: null;


    }





    public function previousByQuiz(

        int $quiz_id,

        int $current_page_number

    ): ?array
    {


        $page = $this->wpdb->get_row(

            $this->wpdb->prepare(

                "SELECT *
                 FROM {$this->table()}
                 WHERE quiz_id = %d
                 AND page_number < %d
                 ORDER BY page_number DESC
                 LIMIT 1",

                $quiz_id,

                $current_page_number

            ),

            ARRAY_A

        );


        return $page ?: null;


    }





    public function create(

        int $quiz_id,

        array $data

    ): int
    {


        $next_number = (int) $this->wpdb->get_var(

            $this->wpdb->prepare(

                "SELECT MAX(page_number)
                 FROM {$this->table()}
                 WHERE quiz_id = %d",

                $quiz_id

            )

        );



        $data['quiz_id'] = $quiz_id;

        $data['page_number'] = $next_number + 1;



        $this->wpdb->insert(

            $this->table(),

            $data

        );



        return (int) $this->wpdb->insert_id;


    }





    public function update(

        int $id,

        array $data

    ): bool
    {

        return (bool) $this->wpdb->update(

            $this->table(),

            $data,

            array(

                'id' => $id

            )

        );

    }

public function move(int $id, string $direction): bool
{
    $page = $this->find($id);

    if (!$page) {
        return false;
    }

    $quiz_id = (int) $page['quiz_id'];
    $current_number = (int) $page['page_number'];

    if ($direction === 'up') {

        $neighbor = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT *
                 FROM {$this->table()}
                 WHERE quiz_id = %d
                 AND page_number < %d
                 ORDER BY page_number DESC
                 LIMIT 1",
                $quiz_id,
                $current_number
            ),
            ARRAY_A
        );

    } elseif ($direction === 'down') {

        $neighbor = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT *
                 FROM {$this->table()}
                 WHERE quiz_id = %d
                 AND page_number > %d
                 ORDER BY page_number ASC
                 LIMIT 1",
                $quiz_id,
                $current_number
            ),
            ARRAY_A
        );

    } else {
        return false;
    }

    if (!$neighbor) {
        return false;
    }

    $neighbor_id = (int) $neighbor['id'];
    $neighbor_number = (int) $neighbor['page_number'];

    // Swap the page numbers.
    $this->wpdb->update(
        $this->table(),
        array(
            'page_number' => $neighbor_number
        ),
        array(
            'id' => $id
        ),
        array('%d'),
        array('%d')
    );

    $this->wpdb->update(
        $this->table(),
        array(
            'page_number' => $current_number
        ),
        array(
            'id' => $neighbor_id
        ),
        array('%d'),
        array('%d')
    );

    return true;
}

public function reorder(
    int $quiz_id,
    array $page_ids
): bool {

    $position = 1;

    foreach ($page_ids as $page_id) {

        $page_id = (int) $page_id;

        if ($page_id <= 0) {
            continue;
        }

        $this->wpdb->update(
            $this->table(),
            array(
                'page_number' => $position
            ),
            array(
                'id'      => $page_id,
                'quiz_id' => $quiz_id
            ),
            array('%d'),
            array('%d', '%d')
        );

        $position++;
    }

    return true;
}

    public function delete(int $id): bool
    {
        $page = $this->find($id);

        if (!$page) {
            return false;
        }

        $quiz_id = (int) $page['quiz_id'];

        $deleted = $this->wpdb->delete(
            $this->table(),
            array('id' => $id),
            array('%d')
        );

        if ($deleted === false || $deleted === 0) {
            return false;
        }

        $remaining_pages = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT id
                 FROM {$this->table()}
                 WHERE quiz_id = %d
                 ORDER BY page_number ASC, id ASC",
                $quiz_id
            ),
            ARRAY_A
        );

        foreach ($remaining_pages as $index => $remaining_page) {
            $this->wpdb->update(
                $this->table(),
                array('page_number' => $index + 1),
                array(
                    'id' => (int) $remaining_page['id'],
                    'quiz_id' => $quiz_id
                ),
                array('%d'),
                array('%d', '%d')
            );
        }

        return true;
    }
public function duplicate_pages(

    int $old_quiz_id,

    int $new_quiz_id

): array
{

    $pages = $this->wpdb->get_results(

        $this->wpdb->prepare(

            "SELECT *
             FROM {$this->table()}
             WHERE quiz_id = %d
             ORDER BY page_number ASC",

            $old_quiz_id

        ),

        ARRAY_A

    );


    $map = array();


    foreach ($pages as $page) {


        $data = array(

            'quiz_id' => $new_quiz_id,

            'page_number' => $page['page_number'],

            'title' => $page['title'],

            'content' => $page['content']

        );


        $this->wpdb->insert(

            $this->table(),

            $data

        );


        $map[(int) $page['id']] = (int) $this->wpdb->insert_id;


    }


    return $map;

}
public function get_pages_with_time_limit(
    int $quiz_id
): array
{

    return $this->wpdb->get_results(

        $this->wpdb->prepare(

            "SELECT id
             FROM {$this->table()}
             WHERE quiz_id = %d
             AND page_time_limit > 0",

            $quiz_id

        ),

        ARRAY_A

    );

}
}