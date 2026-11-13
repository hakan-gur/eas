<?php
/**
 * Plugin Name: e-Assessment Suite
 * Plugin URI: https://test-of-english.com
 * Description: Test preparation and management platform for standardized tests like the TOEFL iBT and the TOEIC.
 * Version: 1.0
 * Author: hakangur
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: eas-quiz
 * Domain Path: /languages
 */
if (!defined('ABSPATH')) {
    exit;
}

define(
    'EXAMENANTO_VERSION',
    '1.0'
);

define(
    'EXAMENANTO_DB_VERSION',
    '1.5'
);

define(
    'EXAMENANTO_PLUGIN_DIR',
    plugin_dir_path(__FILE__)
);

define(
    'EXAMENANTO_PLUGIN_URL',
    plugin_dir_url(__FILE__)
);

require_once EXAMENANTO_PLUGIN_DIR .
    'src/Core/Autoloader.php';

\EAS\Core\Autoloader::register();

/**
 * Apply database updates after plugin upgrades as well as activation.
 *
 * The database schema version is independent of the plugin release version.
 * Do not reset it for the first WordPress.org release: existing installations
 * may already contain EAS data that must be upgraded in place.
 */
function examenanto_maybe_upgrade_database(): void
{
    $installed_version = (string) get_option('examenanto_db_version', get_option('eas_db_version', '1.0'));

    if (version_compare($installed_version, EXAMENANTO_DB_VERSION, '>=')) {
        return;
    }

    \EAS\Core\Installer::install();
    \EAS\Database\Upgrade::run();

    update_option('examenanto_db_version', EXAMENANTO_DB_VERSION);
    update_option('eas_db_version', EXAMENANTO_DB_VERSION); // Keep the legacy option synchronized for compatibility.
}

add_action(
    'plugins_loaded',
    'examenanto_maybe_upgrade_database',
    5
);

function examenanto_initialize_plugin(): void
{
    $app = new \EAS\Core\Application();
    $app->run();

    /*
     * Register the standalone admin question preview after the normal
     * application has registered its controllers. The preview controller
     * replaces the old bare admin-post presentation without touching the
     * question renderer, scoring, or answer storage.
     */
    new \EAS\Admin\Question\QuestionPreviewController();
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
    'examenanto_initialize_plugin'
);
