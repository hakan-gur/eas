<?php

if (!defined('ABSPATH')) {
    exit;
}


class EAS_Public {


    public function __construct() {


        add_shortcode(
            'eas_test',
            array($this, 'test_shortcode')
        );


    }



    public function test_shortcode() {


        return '<p>EAS Quiz is active.</p>';


    }


}