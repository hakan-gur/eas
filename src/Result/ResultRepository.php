<?php
declare(strict_types=1);
namespace EAS\Result;
if (!defined('ABSPATH')) {
    exit;
}
class ResultRepository
{
    private \wpdb $wpdb;
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    private function attempts(): string
    {
        return $this->wpdb->prefix . 'eas_attempts';
    }
    private function responses(): string
    {
        return $this->wpdb->prefix . 'eas_responses';
    }
    private function items(): string
    {
        return $this->wpdb->prefix . 'eas_items';
    }
    private function quizzes(): string
    {
        return $this->wpdb->prefix . 'eas_quizzes';
    }
    public function get_attempt(int $id): ?array
    {
        $attempt = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT 
                    a.*,
                    q.title AS quiz_title
                 FROM {$this->attempts()} a
                 INNER JOIN {$this->quizzes()} q
                 ON a.quiz_id = q.id
                 WHERE a.id = %d",
                $id
            ),
            ARRAY_A
        );
        return $attempt ?: null;
    }
    public function get_responses(int $attempt_id): array
    {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT
                    r.*,
                    i.question_text,
                    i.type,
                    i.settings,
                    i.points AS max_points
                 FROM {$this->responses()} r
                 INNER JOIN {$this->items()} i
                 ON r.item_id = i.id
                 WHERE r.attempt_id = %d
                 ORDER BY i.sort_order ASC",
                $attempt_id
            ),
            ARRAY_A
        );
    }
}