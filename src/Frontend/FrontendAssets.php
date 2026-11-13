<?php

declare(strict_types=1);

namespace Examenanto\Frontend;

if (!defined('ABSPATH')) {
    exit;
}

class FrontendAssets
{
    public function __construct()
    {
        add_action(
            'wp_enqueue_scripts',
            array($this, 'enqueue')
        );
    }

    public function enqueue(): void
    {
        wp_enqueue_style(
            'examenanto-frontend',
            EAS_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            EAS_VERSION
        );

        wp_enqueue_script(
            'examenanto-audio-recorder',
            EAS_PLUGIN_URL . 'assets/js/audio-recorder.js',
            array(),
            EAS_VERSION,
            true
        );

        wp_enqueue_script(
            'examenanto-frontend',
            EAS_PLUGIN_URL . 'assets/js/frontend.js',
            array(),
            EAS_VERSION,
            true
        );

        wp_localize_script(
            'examenanto-audio-recorder',
            'examenantoAudio',
            array(
                'uploadUrl' => admin_url(
                    'admin-post.php'
                ),
                'nonce' => wp_create_nonce(
                    'eas_upload_audio'
                ),
                'strings' => array(
                    'recordingReady' => __('Recording ready.', 'eas-quiz'),
                    'recordingUploaded' => __('Recording uploaded successfully.', 'eas-quiz'),
                    'uploadFailed' => __('Upload failed.', 'eas-quiz'),
                    'uploadError' => __('Upload error.', 'eas-quiz'),
                    'recording' => __('Recording...', 'eas-quiz'),
                ),
            )
        );
    }
}
