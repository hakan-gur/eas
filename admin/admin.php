<?php

if (!defined('ABSPATH')) {
    exit;
}


require_once EAS_PLUGIN_DIR . 'admin/menu.php';


class EAS_Admin {


    public function __construct() {


        new EAS_Admin_Menu();


    }


}