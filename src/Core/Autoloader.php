<?php

declare(strict_types=1);

namespace EAS\Core;


if (!defined('ABSPATH')) {
    exit;
}


class Autoloader
{

    public static function register(): void
    {

        spl_autoload_register(
            array(
                __CLASS__,
                'load'
            )
        );

    }


    private static function load(string $class): void
    {

        $prefix = 'EAS\\';


        if (strpos($class, $prefix) !== 0) {
            return;
        }


        $relative = substr(
            $class,
            strlen($prefix)
        );


        $file = EAS_PLUGIN_DIR .
            'src/' .
            str_replace(
                '\\',
                '/',
                $relative
            ) .
            '.php';


        if (file_exists($file)) {

            require_once $file;

        }

    }

}