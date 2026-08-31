<?php

declare(strict_types=1);

namespace EAS\Core;


if (!defined('ABSPATH')) {
    exit;
}


class Capabilities
{


    public static function add(): void
    {

        $role = get_role('administrator');


        if ($role) {

            $role->add_cap(
                'eas_manage_quizzes'
            );

        }


    }


    public static function remove(): void
    {

        $role = get_role('administrator');


        if ($role) {

            $role->remove_cap(
                'eas_manage_quizzes'
            );

        }

    }


}