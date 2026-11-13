<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}


delete_option('examenanto_db_version');
delete_option('eas_db_version');
delete_option('eas_version');
delete_option('examenanto_attempt_page_id');
delete_option('eas_attempt_page_id');
delete_option('examenanto_results_page_id');
delete_option('eas_results_page_id');
delete_option('examenanto_my_results_page_id');
delete_option('eas_my_results_page_id');