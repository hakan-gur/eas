<?php
/**
 * Plugin Name: English Assessment Suite
 * Plugin URI: https://test-of-english.com
 * Description: Assessment platform for the TOEFL and the TOEIC courses.
 * Version: 1.0
 * Authors: Test of English, ChatGPT
 * Text Domain: eas-quiz
 */
if (!defined('ABSPATH')) {
    exit;
}
define(
    'EAS_VERSION',
    '0.3.2'
);
define(
    'EAS_DB_VERSION',
    '1.7'
);
define(
    'EAS_PLUGIN_DIR',
    plugin_dir_path(__FILE__)
);
define(
    'EAS_PLUGIN_URL',
    plugin_dir_url(__FILE__)
);
require_once EAS_PLUGIN_DIR .
    'src/Core/Autoloader.php';
\EAS\Core\Autoloader::register();
function eas_initialize_plugin(): void
{
    $app = new \EAS\Core\Application();
    $app->run();
}
register_activation_hook(
    __FILE__,
    array(
        'EAS\Core\Activator',
        'activate'
    )
);
register_deactivation_hook(
    __FILE__,
    array(
        'EAS\Core\Deactivator',
        'deactivate'
    )
);
add_action(
    'plugins_loaded',
    'eas_initialize_plugin'
);