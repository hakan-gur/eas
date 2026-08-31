<?php

if (!defined('ABSPATH')) {
    exit;
}


class EAS_Admin_Menu {


    public function __construct() {


        add_action(
            'admin_menu',
            array($this, 'create_menu')
        );


    }



    public function create_menu() {


        add_menu_page(

            'EAS Quiz',
            'EAS Quiz',
            'manage_options',
            'eas-quiz',
            array($this, 'dashboard'),
            'dashicons-welcome-learn-more',
            30

        );


    }



    public function dashboard() {


        require_once EAS_PLUGIN_DIR .
        'admin/views/welcome.php';


    }


}