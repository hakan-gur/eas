<?php
declare(strict_types=1);
namespace EAS\Attempt;
use EAS\Page\PageRepository;
if (!defined('ABSPATH')) {
    exit;
}
class AttemptRepository
{
    private \wpdb $wpdb;
    private PageRepository $pages;
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->pages = new PageRepository();
    }
    private function table(): string
    {
        return $this->wpdb->prefix . 'eas_attempts';
    }
    public function create(
        int $quiz_id,
        int $user_id
    ): int
    {
        $first_page = $this->pages->firstByQuiz(
            $quiz_id
        );
        $page_id = $first_page
            ? (int) $first_page['id']
            : 0;
        $this->wpdb->insert(
    $this->table(),
    array(
        'quiz_id' => $quiz_id,
        'user_id' => $user_id,
        'current_page_id' => $page_id,
        'page_started_at' => current_time('mysql', true),
        'status' => 'in_progress',
        'score' => 0,
        'started_at' => current_time('mysql')
    ),
    array(
        '%d',
        '%d',
        '%d',
        '%s',
        '%s',
        '%f',
        '%s'
    )
);
        return (int) $this->wpdb->insert_id;
    }
    public function find(int $id): ?array
    {
        $attempt = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT *
                 FROM {$this->table()}
                 WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
        return $attempt ?: null;
    }
    public function all_by_user(int $user_id): array
    {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT *
                 FROM {$this->table()}
                 WHERE user_id = %d
                 ORDER BY started_at DESC",
                $user_id
            ),
            ARRAY_A
        );
    }
    public function all_completed_by_user(
    int $user_id
): array
{
    $responses_table =
        $this->wpdb->prefix . 'eas_responses';

    $items_table =
        $this->wpdb->prefix . 'eas_items';

    return $this->wpdb->get_results(
        $this->wpdb->prepare(
            "
            SELECT
                a.*,
                q.title AS quiz_title,

                COUNT(
                    CASE
                        WHEN i.type IN (
                            'essay',
                            'audio_response'
                        )
                        THEN 1
                    END
                ) AS manual_question_count,

                COUNT(
                    CASE
                        WHEN i.type IN (
                            'essay',
                            'audio_response'
                        )
                        AND r.graded_at IS NULL
                        THEN 1
                    END
                ) AS pending_manual_count

            FROM {$this->table()} a

            LEFT JOIN {$this->wpdb->prefix}eas_quizzes q
                ON a.quiz_id = q.id

            LEFT JOIN {$responses_table} r
                ON a.id = r.attempt_id

            LEFT JOIN {$items_table} i
                ON r.item_id = i.id

            WHERE a.user_id = %d
            AND a.status = 'completed'

            GROUP BY a.id

            ORDER BY a.completed_at DESC
            ",
            $user_id
        ),
        ARRAY_A
    );
}
    public function update_current_page(
    int $attempt_id,
    int $page_id
): bool
{
    return (bool) $this->wpdb->update(
        $this->table(),
        array(
            'current_page_id' => $page_id,
            'page_started_at' => current_time('mysql', true)
        ),
        array(
            'id' => $attempt_id
        ),
        array(
            '%d',
            '%s'
        ),
        array(
            '%d'
        )
    );
}
public function count_completed_attempts(
    int $user_id,
    int $quiz_id
): int
{
    return (int) $this->wpdb->get_var(
        $this->wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$this->table()}
             WHERE user_id = %d
             AND quiz_id = %d
             AND status = 'completed'",
            $user_id,
            $quiz_id
        )
    );
}
    public function complete(
        int $id,
        float $score
    ): bool
    {
        return (bool) $this->wpdb->update(
            $this->table(),
            array(
                'status' => 'completed',
                'score' => $score,
                'completed_at' => current_time('mysql')
            ),
            array(
                'id' => $id
            ),
            array(
                '%s',
                '%f',
                '%s'
            ),
            array(
                '%d'
            )
        );
    }
public function all_completed(): array
{
    return $this->wpdb->get_results(
        "SELECT 
            a.*,
            u.display_name,
            q.title AS quiz_title
         FROM {$this->table()} a
         LEFT JOIN {$this->wpdb->users} u
            ON a.user_id = u.ID
         LEFT JOIN {$this->wpdb->prefix}eas_quizzes q
            ON a.quiz_id = q.id
         WHERE a.status = 'completed'
         ORDER BY a.completed_at DESC",
        ARRAY_A
    );
}
public function find_with_details(int $id): ?array
{
    $attempt = $this->wpdb->get_row(
        $this->wpdb->prepare(
            "SELECT 
                a.*,
                u.display_name,
                q.title AS quiz_title
             FROM {$this->table()} a
             LEFT JOIN {$this->wpdb->users} u
             ON a.user_id = u.ID
             LEFT JOIN {$this->wpdb->prefix}eas_quizzes q
             ON a.quiz_id = q.id
             WHERE a.id = %d",
            $id
        ),
        ARRAY_A
    );
    return $attempt ?: null;
}
public function count_pending_grading(): int
{
    $responses_table = $this->wpdb->prefix . 'eas_responses';
    $items_table = $this->wpdb->prefix . 'eas_items';
    return (int) $this->wpdb->get_var(
        "
        SELECT COUNT(DISTINCT a.id)
        FROM {$this->table()} a
        INNER JOIN {$responses_table} r
            ON a.id = r.attempt_id
        INNER JOIN {$items_table} i
            ON r.item_id = i.id
        WHERE
            a.status = 'completed'
            AND
            i.type IN (
                'essay',
                'audio_response'
            )
            AND
            r.graded_at IS NULL
        "
    );
}
public function grading_status(int $attempt_id): string
{
    $responses_table = $this->wpdb->prefix . 'eas_responses';
    $items_table = $this->wpdb->prefix . 'eas_items';
    $pending = $this->wpdb->get_var(
        $this->wpdb->prepare(
            "
            SELECT COUNT(*)
            FROM {$responses_table} r
            INNER JOIN {$items_table} i
                ON r.item_id = i.id
            WHERE r.attempt_id = %d
            AND i.type IN (
                'essay',
                'audio_response'
            )
            AND r.graded_at IS NULL
            ",
            $attempt_id
        )
    );
    if ((int) $pending > 0) {
        return 'Needs grading';
    }
    return 'Graded';
}
public function all_completed_filtered(
    int $quiz_id = 0,
    string $status = ''
): array
{
    $where = array(
        "a.status = 'completed'"
    );
    $params = array();
    if ($quiz_id > 0) {
        $where[] = "a.quiz_id = %d";
        $params[] = $quiz_id;
    }
    if (
        $status !== ''
        &&
        in_array(
            $status,
            array(
                'completed'
            ),
            true
        )
    ) {
        $where[] = "a.status = %s";
        $params[] = $status;
    }
    $sql = "
        SELECT 
            a.*,
            u.display_name,
            q.title AS quiz_title
        FROM {$this->table()} a
        LEFT JOIN {$this->wpdb->users} u
            ON a.user_id = u.ID
        LEFT JOIN {$this->wpdb->prefix}eas_quizzes q
            ON a.quiz_id = q.id
        WHERE " . implode(
            ' AND ',
            $where
        ) . "
        ORDER BY a.completed_at DESC
    ";
    if (!empty($params)) {
        $sql = $this->wpdb->prepare(
            $sql,
            $params
        );
    }
    return $this->wpdb->get_results(
        $sql,
        ARRAY_A
    );
}
public function recalculate_score(int $attempt_id): bool
{
    $responses_table = $this->wpdb->prefix . 'eas_responses';

    $score = $this->wpdb->get_var(
        $this->wpdb->prepare(
            "SELECT COALESCE(SUM(points_awarded), 0)
             FROM {$responses_table}
             WHERE attempt_id = %d",
            $attempt_id
        )
    );

    return $this->wpdb->update(
        $this->table(),
        array(
            'score' => (float) $score
        ),
        array(
            'id' => $attempt_id
        ),
        array(
            '%f'
        ),
        array(
            '%d'
        )
    ) !== false;
}
public function delete(
    int $attempt_id
): bool {
    /*
     * Get all responses belonging to this attempt.
     */
    $responses_table =
        $this->wpdb->prefix . 'eas_responses';
    $response_ids = $this->wpdb->get_col(
        $this->wpdb->prepare(
            "SELECT id
             FROM {$responses_table}
             WHERE attempt_id = %d",
            $attempt_id
        )
    );
    /*
     * Delete associated audio records and files.
     */
    $audio = new \EAS\Audio\AudioRepository();
    foreach ($response_ids as $response_id) {
        $audio->delete_by_response(
            (int) $response_id
        );
    }
    /*
     * Delete all responses belonging to the attempt.
     */
    $this->wpdb->delete(
        $responses_table,
        array(
            'attempt_id' => $attempt_id
        ),
        array(
            '%d'
        )
    );
    /*
     * Finally delete the attempt itself.
     */
    return (bool) $this->wpdb->delete(
        $this->table(),
        array(
            'id' => $attempt_id
        ),
        array(
            '%d'
        )
    );
}
}