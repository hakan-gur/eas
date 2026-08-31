<?php
declare(strict_types=1);
namespace EAS\Database;
if (!defined('ABSPATH')) {
    exit;
}
class Schema
{
    public static function get_schema(): array
    {
        global $wpdb;
        $charset_collate =
            $wpdb->get_charset_collate();
        $prefix =
            $wpdb->prefix . 'eas_';
        return array(
            /*
             * QUIZZES
             */
            "CREATE TABLE {$prefix}quizzes (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                title varchar(255) NOT NULL,
                description longtext NULL,
                allow_back_navigation tinyint(1)
                    NOT NULL DEFAULT 1,
                attempts_allowed int(11)
                    NOT NULL DEFAULT 1,
                time_limit int(11)
                    NOT NULL DEFAULT 0,
                backward_navigation tinyint(1)
                    NOT NULL DEFAULT 1,
                shuffle_options tinyint(1)
                    NOT NULL DEFAULT 0,
                created_by bigint(20) unsigned NOT NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY created_by (created_by)
            ) {$charset_collate};",
            /*
             * PAGES
             *
             * Pages belong to quizzes.
             */
            "CREATE TABLE {$prefix}pages (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                quiz_id bigint(20) unsigned NOT NULL,
                page_number int(11)
                    NOT NULL DEFAULT 1,
                title varchar(255)
                    NOT NULL DEFAULT '',
                content longtext NULL,
                page_time_limit int(11)
                    NOT NULL DEFAULT 0,
                created_at datetime NULL,
                updated_at datetime NULL,
                PRIMARY KEY (id),
                KEY quiz_id (quiz_id),
                KEY quiz_page_number (
                    quiz_id,
                    page_number
                )
            ) {$charset_collate};",
            /*
             * QUESTION BANK
             *
             * IMPORTANT:
             * Questions belong to QUIZZES,
             * not to pages.
             *
             * We keep the existing table name
             * eas_items to minimize changes
             * elsewhere in the plugin.
             */
            "CREATE TABLE {$prefix}items (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                quiz_id bigint(20) unsigned NOT NULL,
                type varchar(50) NOT NULL,
                question_text longtext NULL,
                settings longtext NULL,
                feedback longtext NULL,
                points decimal(10,2)
                    NOT NULL DEFAULT 0,
                sort_order int(11)
                    NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                audio_delay_seconds int(11)
                    NOT NULL DEFAULT 0,
                audio_prepare_seconds int(11)
                    NOT NULL DEFAULT 5,
                audio_duration_seconds int(11)
                    NOT NULL DEFAULT 45,
                audio_start_delay int(11)
                    DEFAULT 0,
                audio_preparation_seconds int(11)
                    DEFAULT 5,
                audio_recording_seconds int(11)
                    DEFAULT 45,
                PRIMARY KEY (id),
                KEY quiz_id (quiz_id),
                KEY type (type),
                KEY quiz_sort_order (
                    quiz_id,
                    sort_order
                )
            ) {$charset_collate};",
            /*
             * PAGE ↔ QUESTION RELATIONSHIP
             *
             * This table determines which questions
             * appear on which pages.
             */
            "CREATE TABLE {$prefix}page_items (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                page_id bigint(20) unsigned NOT NULL,
                item_id bigint(20) unsigned NOT NULL,
                sort_order int(11)
                    NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                UNIQUE KEY page_item (
                    page_id,
                    item_id
                ),
                KEY page_id (page_id),
                KEY item_id (item_id),
                KEY page_sort_order (
                    page_id,
                    sort_order
                )
            ) {$charset_collate};",
            /*
             * ANSWERS
             *
             * Answers belong to questions,
             * not pages.
             */
            "CREATE TABLE {$prefix}answers (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                question_id bigint(20) unsigned NOT NULL,
                answer_text longtext NOT NULL,
                is_correct tinyint(1)
                    NOT NULL DEFAULT 0,
                correct_position int
                    NOT NULL DEFAULT 0,
                sort_order int(11)
                    NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                feedback longtext NULL,
                PRIMARY KEY (id),
                KEY question_id (question_id)
            ) {$charset_collate};",
            /*
             * ATTEMPTS
             */
            "CREATE TABLE {$prefix}attempts (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                quiz_id bigint(20) unsigned NOT NULL,
                user_id bigint(20) unsigned NOT NULL,
                current_page_id bigint(20) unsigned
                    DEFAULT 0,
                    page_started_at DATETIME NULL,
                status varchar(50)
                    NOT NULL DEFAULT 'in_progress',
                score decimal(10,2)
                    DEFAULT 0,
                started_at datetime NOT NULL,
                completed_at datetime NULL,
                finished_at datetime NULL,
                PRIMARY KEY (id),
                KEY quiz_id (quiz_id),
                KEY user_id (user_id),
                KEY quiz_user (
                    quiz_id,
                    user_id
                )
            ) {$charset_collate};",
            /*
             * RESPONSES
             */
            "CREATE TABLE {$prefix}responses (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                attempt_id bigint(20) unsigned NOT NULL,
                item_id bigint(20) unsigned NOT NULL,
                answer longtext NULL,
                points_awarded decimal(10,2)
                    NOT NULL DEFAULT 0,
                teacher_feedback longtext NULL,
                graded_by bigint(20) unsigned NULL,
                graded_at datetime NULL,
                PRIMARY KEY (id),
                KEY attempt_id (attempt_id),
                KEY item_id (item_id),
                UNIQUE KEY attempt_item (
                    attempt_id,
                    item_id
                )
            ) {$charset_collate};",
            /*
             * AUDIO
             */
            "CREATE TABLE {$prefix}audio (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                response_id bigint(20) unsigned NOT NULL,
                file_path text NOT NULL,
                duration int(11)
                    NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY response_id (response_id)
            ) {$charset_collate};"
        );
    }
}