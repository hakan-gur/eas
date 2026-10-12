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

        /*
         * Database version is updated by Database\\Upgrade only after the
         * corresponding upgrade steps have run successfully.
         */
    }
}
