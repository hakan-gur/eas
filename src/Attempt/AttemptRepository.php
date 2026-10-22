<?php
declare(strict_types=1);
namespace EAS\Attempt;
use EAS\Page\PageRepository;
if (!defined('ABSPATH')) {
    exit;
}
class AttemptRepository
{
    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table identifiers are escaped with esc_sql(); %i requires WordPress 6.2+.
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
        $wpdb = $this->wpdb;
        $attempts_table = esc_sql($this->table());
        $attempt = $this->wpdb->get_row(
            $wpdb->prepare(
                "SELECT *
                 FROM {$attempts_table}
                 WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
        return $attempt ?: null;
    }
    public function all_by_user(int $user_id): array
    {
        $wpdb = $this->wpdb;
        $attempts_table = esc_sql($this->table());
        return $this->wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                 FROM {$attempts_table}
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
    $wpdb = $this->wpdb;
    $attempts_table = esc_sql($this->table());
    $responses_table =
        $wpdb->prefix . 'eas_responses';
    $responses_sql_table = esc_sql($responses_table);

    $items_table =
        $wpdb->prefix . 'eas_items';
    $items_sql_table = esc_sql($items_table);
    $quizzes_sql_table = esc_sql($wpdb->prefix . 'eas_quizzes');

    return $this->wpdb->get_results(
        $wpdb->prepare(
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

            FROM {$attempts_table} a

            LEFT JOIN {$quizzes_sql_table} q
                ON a.quiz_id = q.id

            LEFT JOIN {$responses_sql_table} r
                ON a.id = r.attempt_id

            LEFT JOIN {$items_sql_table} i
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
    $wpdb = $this->wpdb;
    $attempts_table = esc_sql($this->table());
    return (int) $this->wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$attempts_table}
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
    $wpdb = $this->wpdb;
    $attempts_table = esc_sql($this->table());
    $users_sql_table = esc_sql($wpdb->users);
    $quizzes_sql_table = esc_sql($wpdb->prefix . 'eas_quizzes');
    return $this->wpdb->get_results(
        $wpdb->prepare(
            "SELECT 
                a.*,
                u.display_name,
                q.title AS quiz_title
             FROM {$attempts_table} a
             LEFT JOIN {$users_sql_table} u
                ON a.user_id = u.ID
             LEFT JOIN {$quizzes_sql_table} q
                ON a.quiz_id = q.id
             WHERE a.status = %s
             ORDER BY a.completed_at DESC",
            'completed'
        ),
        ARRAY_A
    );
}
public function find_with_details(int $id): ?array
{
    $wpdb = $this->wpdb;
    $attempts_table = esc_sql($this->table());
    $users_sql_table = esc_sql($wpdb->users);
    $quizzes_sql_table = esc_sql($wpdb->prefix . 'eas_quizzes');
    $attempt = $this->wpdb->get_row(
        $wpdb->prepare(
            "SELECT 
                a.*,
                u.display_name,
                q.title AS quiz_title
             FROM {$attempts_table} a
             LEFT JOIN {$users_sql_table} u
             ON a.user_id = u.ID
             LEFT JOIN {$quizzes_sql_table} q
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
    $wpdb = $this->wpdb;
    $attempts_table = esc_sql($this->table());
    $responses_table = $wpdb->prefix . 'eas_responses';
    $responses_sql_table = esc_sql($responses_table);
    $items_table = $wpdb->prefix . 'eas_items';
    $items_sql_table = esc_sql($items_table);
    return (int) $this->wpdb->get_var(
        $wpdb->prepare(
            "
        SELECT COUNT(DISTINCT a.id)
        FROM {$attempts_table} a
        INNER JOIN {$responses_sql_table} r
            ON a.id = r.attempt_id
        INNER JOIN {$items_sql_table} i
            ON r.item_id = i.id
        WHERE
            a.status = %s
            AND
            i.type IN (%s, %s)
            AND
            r.graded_at IS NULL
        ",
            'completed',
            'essay',
            'audio_response'
        )
    );
}
public function grading_status(int $attempt_id): string
{
    $wpdb = $this->wpdb;
    $responses_table = $wpdb->prefix . 'eas_responses';
    $responses_sql_table = esc_sql($responses_table);
    $items_table = $wpdb->prefix . 'eas_items';
    $items_sql_table = esc_sql($items_table);
    $pending = $this->wpdb->get_var(
        $wpdb->prepare(
            "
            SELECT COUNT(*)
            FROM {$responses_sql_table} r
            INNER JOIN {$items_sql_table} i
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
    $wpdb = $this->wpdb;
    $attempts_table = esc_sql($this->table());
    $users_sql_table = esc_sql($wpdb->users);
    $quizzes_sql_table = esc_sql($wpdb->prefix . 'eas_quizzes');
    $where = array(
        "a.status = %s"
    );
    $params = array('completed');
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
        FROM {$attempts_table} a
        LEFT JOIN {$users_sql_table} u
            ON a.user_id = u.ID
        LEFT JOIN {$quizzes_sql_table} q
            ON a.quiz_id = q.id
        WHERE " . implode(
            ' AND ',
            $where
        ) . "
        ORDER BY a.completed_at DESC
    ";
    $sql = $wpdb->prepare(
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The assembled query is prepared immediately after construction.
        $sql,
        $params
    );
    // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $sql is prepared with $wpdb->prepare() immediately above.
    return $this->wpdb->get_results(
        $sql,
        ARRAY_A
    );
    // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
}
public function recalculate_score(int $attempt_id): bool
{
    $wpdb = $this->wpdb;
    $responses_table = $wpdb->prefix . 'eas_responses';
    $responses_sql_table = esc_sql($responses_table);

    $score = $this->wpdb->get_var(
        $wpdb->prepare(
            "SELECT COALESCE(SUM(points_awarded), 0)
             FROM {$responses_sql_table}
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
    $wpdb = $this->wpdb;
    /*
     * Get all responses belonging to this attempt.
     */
    $responses_table =
        $wpdb->prefix . 'eas_responses';
    $responses_sql_table = esc_sql($responses_table);
    $response_ids = $this->wpdb->get_col(
        $wpdb->prepare(
            "SELECT id
             FROM {$responses_sql_table}
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
    // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}