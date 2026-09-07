<?php
/**
 * Plugin Name: e-Assessment Suite
 * Plugin URI: https://test-of-english.com
 * Description: Test preparation and management platform for standardized tests like the TOEFL iBT and the TOEIC.
 * Version: 1.0
 * Authors: Test of English, ChatGPT
 * Text Domain: eas-quiz
 * Domain Path: /languages
 */
if (!defined('ABSPATH')) {
    exit;
}

define(
    'EAS_VERSION',
    '1.0'
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

/**
 * Load EAS translations from the plugin languages directory.
 */
function eas_load_textdomain(): void
{
    load_plugin_textdomain(
        'eas-quiz',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}

add_action(
    'init',
    'eas_load_textdomain'
);

function eas_initialize_plugin(): void
{
    $app = new \EAS\Core\Application();
    $app->run();
}

register_activation_hook(
    __FILE__,
    array(
        'EAS\\Core\\Activator',
        'activate'
    )
);

register_deactivation_hook(
    __FILE__,
    array(
        'EAS\\Core\\Deactivator',
        'deactivate'
    )
);

add_action(
    'plugins_loaded',
    'eas_initialize_plugin'
);
