<?php

declare(strict_types=1);

namespace EAS\Database;


if (!defined('ABSPATH')) {
    exit;
}


class Upgrade
{


    public static function run(): void
    {


$current = (string) get_option(
    'eas_db_version',
    '1.0'
);



        if (version_compare($current, '1.1', '<')) {


            self::upgrade_1_1();



            update_option(
                'eas_db_version',
                '1.1'
            );


            $current = '1.1';


        }



        if (version_compare($current, '1.2', '<')) {


            self::upgrade_1_2();



            update_option(
                'eas_db_version',
                '1.2'
            );


            $current = '1.2';


        }



        if (version_compare($current, '1.3', '<')) {


    self::upgrade_1_3();



    update_option(
        'eas_db_version',
        '1.3'
    );


    $current = '1.3';


}
if (version_compare($current, '1.4', '<')) {


    self::upgrade_1_4();



    update_option(
        'eas_db_version',
        '1.4'
    );


}
if (version_compare($current, '1.5', '<')) {

    self::upgrade_1_5();

    update_option(
        'eas_db_version',
        '1.5'
    );

}

    }





    private static function upgrade_1_1(): void
    {


        global $wpdb;


        $table = $wpdb->prefix . 'eas_pages';



        $columns = $wpdb->get_results(

            "SHOW COLUMNS FROM {$table}"

        );



        $existing = array();



        foreach ($columns as $column) {

            $existing[] = $column->Field;

        }



        if (!in_array('title', $existing, true)) {


            $wpdb->query(

                "ALTER TABLE {$table}
                 ADD title varchar(255) NOT NULL DEFAULT ''
                 AFTER page_number"

            );


        }



        if (!in_array('content', $existing, true)) {


            $wpdb->query(

                "ALTER TABLE {$table}
                 ADD content longtext NULL
                 AFTER title"

            );


        }



        if (!in_array('created_at', $existing, true)) {


            $wpdb->query(

                "ALTER TABLE {$table}
                 ADD created_at datetime NULL
                 AFTER content"

            );


        }



        if (!in_array('updated_at', $existing, true)) {


            $wpdb->query(

                "ALTER TABLE {$table}
                 ADD updated_at datetime NULL
                 AFTER created_at"

            );


        }


    }





    private static function upgrade_1_2(): void
    {


        global $wpdb;


        require_once ABSPATH .
            'wp-admin/includes/upgrade.php';



        $table = $wpdb->prefix . 'eas_attempts';



        $charset_collate = $wpdb->get_charset_collate();



        $sql = "CREATE TABLE {$table} (

            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            quiz_id bigint(20) unsigned NOT NULL,

            user_id bigint(20) unsigned NOT NULL,

            status varchar(50) NOT NULL DEFAULT 'in_progress',

            score decimal(10,2) NOT NULL DEFAULT 0,

            started_at datetime NOT NULL,

            finished_at datetime NULL,

            PRIMARY KEY (id)

        ) {$charset_collate};";



        dbDelta($sql);


    }





    private static function upgrade_1_3(): void
    {


        error_log(
            'EAS upgrade_1_3 started'
        );



        global $wpdb;


        require_once ABSPATH .
            'wp-admin/includes/upgrade.php';



        $table = $wpdb->prefix . 'eas_answers';



        $charset_collate = $wpdb->get_charset_collate();



        $sql = "CREATE TABLE {$table} (

            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,

            question_id bigint(20) unsigned NOT NULL,

            answer_text text NOT NULL,

            feedback longtext NULL,

            is_correct tinyint(1) NOT NULL DEFAULT 0,

            sort_order int(11) NOT NULL DEFAULT 0,

            created_at datetime NULL,

            updated_at datetime NULL,

            PRIMARY KEY (id),

            KEY question_id (question_id)

        ) {$charset_collate};";



        $result = dbDelta($sql);



        error_log(
            'EAS answers dbDelta result: ' .
            print_r($result, true)
        );



        error_log(
            'EAS answers database error: ' .
            $wpdb->last_error
        );


    }
private static function upgrade_1_4(): void
{

    global $wpdb;


    $table = $wpdb->prefix . 'eas_pages';



    $columns = $wpdb->get_results(

        "SHOW COLUMNS FROM {$table}"

    );



    $existing = array();



    foreach ($columns as $column) {

        $existing[] = $column->Field;

    }



    if (!in_array('page_time_limit', $existing, true)) {


        $wpdb->query(

            "ALTER TABLE {$table}
             ADD page_time_limit int(11) NOT NULL DEFAULT 0
             AFTER content"

        );


    }


}
private static function upgrade_1_5(): void
{
    global $wpdb;

    $table = $wpdb->prefix . 'eas_answers';

    $columns = $wpdb->get_results(
        "SHOW COLUMNS FROM {$table}"
    );

    $existing = array();

    foreach ($columns as $column) {
        $existing[] = $column->Field;
    }

    if (!in_array('correct_position', $existing, true)) {

        $wpdb->query(
            "ALTER TABLE {$table}
             ADD correct_position int(11) NOT NULL DEFAULT 0
             AFTER is_correct"
        );

    }
}

}