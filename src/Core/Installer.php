<?php

declare(strict_types=1);

namespace EAS\Core;


use EAS\Database\Schema;


if (!defined('ABSPATH')) {
    exit;
}


class Installer
{


    public static function install(): void
    {


        require_once ABSPATH . 'wp-admin/includes/upgrade.php';



        $schemas = Schema::get_schema();



        foreach ($schemas as $sql) {




            dbDelta($sql);


        }



        update_option(
            'eas_db_version',
            '1.5'
        );




    }


}