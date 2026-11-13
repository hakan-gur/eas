<?php

declare(strict_types=1);

namespace Examenanto\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Capabilities
{
    public static function add(): void
    {
        $role = get_role('administrator');

        if ($role) {
            $role->add_cap('examenanto_manage_quizzes');
            // Keep the previous capability available to older integrations.
            $role->add_cap('eas_manage_quizzes');
        }
    }

    public static function remove(): void
    {
        $role = get_role('administrator');

        if ($role) {
            $role->remove_cap('examenanto_manage_quizzes');
            $role->remove_cap('eas_manage_quizzes');
        }
    }
}
