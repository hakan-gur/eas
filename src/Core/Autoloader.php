<?php

declare(strict_types=1);

namespace Examenanto\Core;


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

        $legacy_prefix = 'EAS\\';

        if (strpos($class, $legacy_prefix) === 0) {
            $relative = substr($class, strlen($legacy_prefix));
            $new_class = 'Examenanto\\' . $relative;

            if (class_exists($new_class)) {
                class_alias($new_class, $class);
            }

            return;
        }

        $prefix = 'Examenanto\\';


        if (strpos($class, $prefix) !== 0) {
            return;
        }


        $relative = substr(
            $class,
            strlen($prefix)
        );


        $file = EXAMENANTO_PLUGIN_DIR .
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