<?php

declare(strict_types=1);

namespace EAS\Core;

if (!defined('ABSPATH')) {
    exit;
}

class View
{
    /**
     * Render a PHP view.
     *
     * Example:
     * View::render(
     *     'frontend/attempt/page',
     *     [
     *         'page' => $page,
     *         'questions' => $questions
     *     ]
     * );
     */
    public static function render(
        string $view,
        array $data = []
    ): string {

        $file =
            EAS_PLUGIN_DIR .
            'views/' .
            $view .
            '.php';

        if (!file_exists($file)) {

            return '<p>View not found: ' .
                esc_html($view) .
                '</p>';

        }

        extract(
            $data,
            EXTR_SKIP
        );

        ob_start();

        require $file;

        return ob_get_clean();

    }
}