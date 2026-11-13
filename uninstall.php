<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}


delete_option('examenanto_db_version');
delete_option('eas_db_version');
delete_option('eas_version');