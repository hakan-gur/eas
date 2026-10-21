<?php
declare(strict_types=1);
namespace EAS\Question;
if (!defined('ABSPATH')) {
    exit;
}
class QuestionRepository
{
    private \wpdb $wpdb;
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    private function items_table(): string
    {
        return $this->wpdb->prefix . 'eas_items';
    }
    private function answers_table(): string
    {
        return $this->wpdb->prefix . 'eas_answers';
    }
    private function page_items_table(): string
    {
        return $this->wpdb->prefix . 'eas_page_items';
    }
    /*
     * ---------------------------------------------------------
     * QUESTION BANK
     * ---------------------------------------------------------
     */
    /**
     * Get all questions belonging to a quiz.
     *
     * These are the questions in the quiz question bank,
     * regardless of whether they are currently attached
     * to a page.
     */
    public function all_by_quiz(int $quiz_id): array
    {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT *
                 FROM %i
                 WHERE quiz_id = %d
                 ORDER BY sort_order ASC, id ASC",
                $this->items_table(),
                $quiz_id
            ),
            ARRAY_A
        );
    }
    /**
     * Find a single question.
     */
    public function find(int $id): ?array
    {
        $question = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT *
                 FROM %i
                 WHERE id = %d",
                $this->items_table(),
                $id
            ),
            ARRAY_A
        );
        return $question ?: null;
    }
    /**
     * Get all answers belonging to a question.
     */
    public function get_answers(int $question_id): array
    {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT *
                 FROM %i
                 WHERE question_id = %d
                 ORDER BY sort_order ASC, id ASC",
                $this->answers_table(),
                $question_id
            ),
            ARRAY_A
        );
    }
    /**
     * Create a new question in the quiz question bank.
     *
     * IMPORTANT:
     * A question belongs to a quiz, not to a page.
     */
    public function create(
    int $quiz_id,
    array $data
): int
{
    $data['quiz_id'] = $quiz_id;
    $this->wpdb->insert(
        $this->items_table(),
        $data
    );
    return (int) $this->wpdb->insert_id;
}
    /**
     * Update a question in the question bank.
     */
    public function update(
        int $id,
        array $data
    ): bool {
        return (bool) $this->wpdb->update(
            $this->items_table(),
            $data,
            array(
                'id' => $id
            )
        );
    }
    /**
     * Delete a question completely from the question bank.
     *
     * This also deletes its answers and any page assignments.
     *
     * This method should ONLY be used by the "Delete" action
     * in the question bank.
     */
    public function delete(int $id): bool
{
    /*
     * ---------------------------------------------------------
     * Remove the question from all pages first.
     * ---------------------------------------------------------
     */
    $this->wpdb->delete(
        $this->page_items_table(),
        array(
            'item_id' => $id
        ),
        array(
            '%d'
        )
    );
    /*
     * ---------------------------------------------------------
     * Remove all answers belonging to the question.
     * ---------------------------------------------------------
     */
    $this->wpdb->delete(
        $this->answers_table(),
        array(
            'question_id' => $id
        ),
        array(
            '%d'
        )
    );
    /*
     * ---------------------------------------------------------
     * Finally remove the question itself.
     * ---------------------------------------------------------
     */
    return (bool) $this->wpdb->delete(
        $this->items_table(),
        array(
            'id' => $id
        ),
        array(
            '%d'
        )
    );
}
    /**
     * Delete all answers belonging to a question.
     */
    public function delete_answers(int $question_id): void
    {
        $this->wpdb->delete(
            $this->answers_table(),
            array(
                'question_id' => $question_id
            )
        );
    }
    /**
     * Save answers belonging to a question.
     */
    public function save_answers(
        int $question_id,
        array $answers
    ): void {
        $this->delete_answers($question_id);
        foreach ($answers as $index => $answer) {
            if (
                !isset($answer['text'])
                ||
                trim($answer['text']) === ''
            ) {
                continue;
            }
            $this->wpdb->insert(
                $this->answers_table(),
                array(
                    'question_id' => $question_id,
                    'answer_text' => sanitize_text_field(
                        $answer['text']
                    ),
                    'is_correct' => !empty(
                        $answer['correct']
                    ) ? 1 : 0,
                    'correct_position' => isset(
                        $answer['correct_position']
                    )
                        ? (int) $answer['correct_position']
                        : 0,
                    'sort_order' => $index + 1,
                    'created_at' => current_time(
                        'mysql'
                    ),
                    'updated_at' => current_time(
                        'mysql'
                    )
                )
            );
        }
    }
    /*
     * ---------------------------------------------------------
     * PAGE / QUESTION RELATIONSHIP
     * ---------------------------------------------------------
     */
    /**
     * Get questions currently assigned to a page.
     *
     * Questions themselves remain in the question bank.
     * The page_items table determines which questions appear
     * on this page and in what order.
     */
    public function all_by_page(int $page_id): array
    {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT
                    i.*,
                    pi.sort_order AS page_sort_order
                 FROM %i i
                 INNER JOIN %i pi
                    ON i.id = pi.item_id
                 WHERE pi.page_id = %d
                 ORDER BY
                    pi.sort_order ASC,
                    i.id ASC",
                $this->items_table(),
                $this->page_items_table(),
                $page_id
            ),
            ARRAY_A
        );
    }
    /**
     * Check whether a question is already assigned to a page.
     */
    public function is_on_page(
        int $question_id,
        int $page_id
    ): bool {
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*)
                 FROM %i
                 WHERE item_id = %d
                 AND page_id = %d",
                $this->page_items_table(),
                $question_id,
                $page_id
            )
        );
        return ((int) $count > 0);
    }
    /**
     * Add an existing question from the question bank
     * to a page.
     *
     * The question itself is NOT copied.
     */
    public function add_to_page(
        int $question_id,
        int $page_id
    ): bool {
        /*
         * Prevent duplicate assignment.
         */
        if (
            $this->is_on_page(
                $question_id,
                $page_id
            )
        ) {
            return true;
        }
        /*
         * Find the next position on this page.
         */
        $next_order = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT MAX(sort_order)
                 FROM %i
                 WHERE page_id = %d",
                $this->page_items_table(),
                $page_id
            )
        );
        $next_order++;
        return (bool) $this->wpdb->insert(
            $this->page_items_table(),
            array(
                'page_id' => $page_id,
                'item_id' => $question_id,
                'sort_order' => $next_order
            ),
            array(
                '%d',
                '%d',
                '%d'
            )
        );
    }
    /**
     * Remove a question from a page.
     *
     * IMPORTANT:
     * The question remains in the question bank.
     */
    public function remove_from_page(
        int $question_id,
        int $page_id
    ): bool {
        return (bool) $this->wpdb->delete(
            $this->page_items_table(),
            array(
                'item_id' => $question_id,
                'page_id' => $page_id
            ),
            array(
                '%d',
                '%d'
            )
        );
    }
    /**
     * Get all pages on which a question is currently used.
     */
     
    public function detach_from_page(
    int $page_id,
    int $item_id
): bool
{
    return (bool) $this->wpdb->delete(
        $this->page_items_table(),
        array(
            'page_id' => $page_id,
            'item_id' => $item_id
        ),
        array(
            '%d',
            '%d'
        )
    );
}
    public function pages_for_question(
        int $question_id
    ): array {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT
                    p.*,
                    pi.sort_order
                 FROM %i p
                 INNER JOIN %i pi
                    ON p.id = pi.page_id
                 WHERE pi.item_id = %d
                 ORDER BY
                    p.page_number ASC,
                    pi.sort_order ASC",
                $this->wpdb->prefix . 'eas_pages',
                $this->page_items_table(),
                $question_id
            ),
            ARRAY_A
        );
    }
    /*
     * ---------------------------------------------------------
     * QUIZ DUPLICATION
     * ---------------------------------------------------------
     */
    /**
     * Duplicate all questions from one quiz into another quiz.
     *
     * This creates NEW question-bank entries.
     *
     * It does NOT assign them to pages here.
     *
     * Returns:
     *
     * old_question_id => new_question_id
     */
    public function duplicate_by_quiz(
        int $old_quiz_id,
        int $new_quiz_id
    ): array {
        $questions = $this->all_by_quiz(
            $old_quiz_id
        );
        $map = array();
        foreach ($questions as $question) {
            $data = array(
                'quiz_id' => $new_quiz_id,
                'type' => $question['type'],
                'question_text' =>
                    $question['question_text'],
                'settings' =>
                    $question['settings'] ?? null,
                'feedback' =>
                    $question['feedback'] ?? null,
                'points' =>
                    $question['points'],
                'sort_order' =>
                    $question['sort_order'],
                'created_at' =>
                    current_time('mysql'),
                'updated_at' =>
                    current_time('mysql'),
                'audio_delay_seconds' =>
                    $question['audio_delay_seconds'] ?? 0,
                'audio_prepare_seconds' =>
                    $question['audio_prepare_seconds'] ?? 5,
                'audio_duration_seconds' =>
                    $question['audio_duration_seconds'] ?? 45,
                'audio_start_delay' =>
                    $question['audio_start_delay'] ?? 0,
                'audio_preparation_seconds' =>
                    $question['audio_preparation_seconds'] ?? 5,
                'audio_recording_seconds' =>
                    $question['audio_recording_seconds'] ?? 45
            );
            $this->wpdb->insert(
                $this->items_table(),
                $data
            );
            $new_question_id =
                (int) $this->wpdb->insert_id;
            $map[
                (int) $question['id']
            ] = $new_question_id;
            /*
             * Duplicate the answers belonging to this question.
             */
            $answers = $this->get_answers(
                (int) $question['id']
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
                            current_time('mysql')
                    )
                );
            }
        }
        return $map;
    }
public function find_page_item(
    int $page_id,
    int $item_id
): ?array
{
    $result = $this->wpdb->get_row(
        $this->wpdb->prepare(
            "
            SELECT *
            FROM %i
            WHERE page_id = %d
            AND item_id = %d
            LIMIT 1
            ",
                $this->page_items_table(),
            $page_id,
            $item_id
        ),
        ARRAY_A
    );
    return $result ?: null;
}
public function get_adjacent_page_item(
    int $page_id,
    int $current_order,
    string $direction
): ?array
{
    if ($direction === 'up') {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "
                SELECT *
                FROM %i
                WHERE page_id = %d
                AND sort_order < %d
                ORDER BY sort_order DESC
                LIMIT 1
                ",
                $this->page_items_table(),
                $page_id,
                $current_order
            ),
            ARRAY_A
        );
    } else {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "
                SELECT *
                FROM %i
                WHERE page_id = %d
                AND sort_order > %d
                ORDER BY sort_order ASC
                LIMIT 1
                ",
                $this->page_items_table(),
                $page_id,
                $current_order
            ),
            ARRAY_A
        );
    }
    return $result ?: null;
}
public function swap_page_item_order(
    int $page_id,
    int $item_id_a,
    int $item_id_b,
    int $order_a,
    int $order_b
): void
{
    /*
     * Use a temporary sort order so that the two
     * records can safely exchange their positions.
     */
    $temporary_order = -1;
    $this->wpdb->update(
        $this->page_items_table(),
        array(
            'sort_order' => $temporary_order
        ),
        array(
            'page_id' => $page_id,
            'item_id' => $item_id_a
        ),
        array(
            '%d'
        ),
        array(
            '%d',
            '%d'
        )
    );
    $this->wpdb->update(
        $this->page_items_table(),
        array(
            'sort_order' => $order_a
        ),
        array(
            'page_id' => $page_id,
            'item_id' => $item_id_b
        ),
        array(
            '%d'
        ),
        array(
            '%d',
            '%d'
        )
    );
    $this->wpdb->update(
        $this->page_items_table(),
        array(
            'sort_order' => $order_b
        ),
        array(
            'page_id' => $page_id,
            'item_id' => $item_id_a
        ),
        array(
            '%d'
        ),
        array(
            '%d',
            '%d'
        )
    );
}
public function next_page_sort_order(
    int $page_id
): int
{
    $max = $this->wpdb->get_var(
        $this->wpdb->prepare(
            "
            SELECT MAX(sort_order)
            FROM %i
            WHERE page_id = %d
            ",
                $this->page_items_table(),
            $page_id
        )
    );
    return ((int) $max) + 1;
}
/*
 * ---------------------------------------------------------
 * QUESTION ORDERING WITHIN A PAGE
 * ---------------------------------------------------------
 */
/**
 * Move a question one position upward on a page.
 */
public function move_question_up(
    int $page_id,
    int $item_id
): bool {
    $current = $this->wpdb->get_row(
        $this->wpdb->prepare(
            "
            SELECT *
            FROM %i
            WHERE page_id = %d
            AND item_id = %d
            LIMIT 1
            ",
                $this->page_items_table(),
            $page_id,
            $item_id
        ),
        ARRAY_A
    );
    if (!$current) {
        return false;
    }
    /*
     * Find the question immediately above this one.
     */
    $previous = $this->wpdb->get_row(
        $this->wpdb->prepare(
            "
            SELECT *
            FROM %i
            WHERE page_id = %d
            AND sort_order < %d
            ORDER BY sort_order DESC
            LIMIT 1
            ",
                $this->page_items_table(),
            $page_id,
            (int) $current['sort_order']
        ),
        ARRAY_A
    );
    /*
     * Already at the top.
     */
    if (!$previous) {
        return false;
    }
    /*
     * Swap the two sort orders.
     */
    $this->wpdb->update(
        $this->page_items_table(),
        array(
            'sort_order' => (int) $previous['sort_order']
        ),
        array(
            'page_id' => $page_id,
            'item_id' => $item_id
        ),
        array('%d'),
        array('%d', '%d')
    );
    $this->wpdb->update(
        $this->page_items_table(),
        array(
            'sort_order' => (int) $current['sort_order']
        ),
        array(
            'page_id' => $page_id,
            'item_id' => (int) $previous['item_id']
        ),
        array('%d'),
        array('%d', '%d')
    );
    return true;
}
/**
 * Move a question one position downward on a page.
 */
public function move_question_down(
    int $page_id,
    int $item_id
): bool {
    $current = $this->wpdb->get_row(
        $this->wpdb->prepare(
            "
            SELECT *
            FROM %i
            WHERE page_id = %d
            AND item_id = %d
            LIMIT 1
            ",
                $this->page_items_table(),
            $page_id,
            $item_id
        ),
        ARRAY_A
    );
    if (!$current) {
        return false;
    }
    /*
     * Find the question immediately below this one.
     */
    $next = $this->wpdb->get_row(
        $this->wpdb->prepare(
            "
            SELECT *
            FROM %i
            WHERE page_id = %d
            AND sort_order > %d
            ORDER BY sort_order ASC
            LIMIT 1
            ",
                $this->page_items_table(),
            $page_id,
            (int) $current['sort_order']
        ),
        ARRAY_A
    );
    /*
     * Already at the bottom.
     */
    if (!$next) {
        return false;
    }
    /*
     * Swap the two sort orders.
     */
    $this->wpdb->update(
        $this->page_items_table(),
        array(
            'sort_order' => (int) $next['sort_order']
        ),
        array(
            'page_id' => $page_id,
            'item_id' => $item_id
        ),
        array('%d'),
        array('%d', '%d')
    );
    $this->wpdb->update(
        $this->page_items_table(),
        array(
            'sort_order' => (int) $current['sort_order']
        ),
        array(
            'page_id' => $page_id,
            'item_id' => (int) $next['item_id']
        ),
        array('%d'),
        array('%d', '%d')
    );
    return true;
}
public function attach_to_page(
    int $page_id,
    int $item_id,
    int $sort_order
): bool
{
    return (bool) $this->wpdb->insert(
        $this->page_items_table(),
        array(
            'page_id' => $page_id,
            'item_id' => $item_id,
            'sort_order' => $sort_order
        ),
        array(
            '%d',
            '%d',
            '%d'
        )
    );
}
    
}