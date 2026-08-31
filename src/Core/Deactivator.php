<?php

declare(strict_types=1);

namespace EAS\Core;


if (!defined('ABSPATH')) {
    exit;
}


class Deactivator
{


    public static function deactivate(): void
    {

        flush_rewrite_rules();

    }

}