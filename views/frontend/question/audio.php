<?php

if (!defined('ABSPATH')) {
    exit;
}

<?php

$audio_settings = json_decode(
    $question['settings'] ?? '',
    true
);

if (!is_array($audio_settings)) {
    $audio_settings = array();
}

$delay = max(0, (int) ($audio_settings['audio_delay_seconds'] ?? 10));
$prepare = max(0, (int) ($audio_settings['audio_prepare_seconds'] ?? 5));
$duration = max(0, (int) ($audio_settings['audio_duration_seconds'] ?? 45));

?>

<div
    class="eas-question eas-audio-question"
    data-delay="<?php echo esc_attr($delay); ?>"
    data-prepare="<?php echo esc_attr($prepare); ?>"
    data-duration="<?php echo esc_attr($duration); ?>"
    data-question-id="<?php echo esc_attr((int) $question['id']); ?>"
    data-upload-url="<?php echo esc_url(admin_url('admin-post.php')); ?>"
    data-upload-nonce="<?php echo esc_attr(wp_create_nonce('eas_upload_audio')); ?>"
    data-second-remaining="<?php echo esc_attr__('second remaining', 'eas-quiz'); ?>"
    data-seconds-remaining="<?php echo esc_attr__('seconds remaining', 'eas-quiz'); ?>"
    data-recording-label="<?php echo esc_attr__('Recording', 'eas-quiz'); ?>"
    data-recording-status="<?php echo esc_attr__('Recording...', 'eas-quiz'); ?>"
    data-uploading-status="<?php echo esc_attr__('Uploading...', 'eas-quiz'); ?>"
    data-microphone-denied="<?php echo esc_attr__('Microphone permission denied.', 'eas-quiz'); ?>"
    data-recording-saved="<?php echo esc_attr__('Recording saved.', 'eas-quiz'); ?>"
    data-upload-failed="<?php echo esc_attr__('Upload failed.', 'eas-quiz'); ?>"
    data-upload-try-again="<?php echo esc_attr__('Upload failed. Please try again.', 'eas-quiz'); ?>"
    data-get-ready="<?php echo esc_attr__('Get ready', 'eas-quiz'); ?>"
>

    <h3>
        <?php echo wp_kses_post($question['question_text']); ?>
    </h3>


    <div class="eas-audio-response">


        <p class="eas-speaking-message">
            <?php esc_html_e('Listen carefully. Your response will start automatically.', 'eas-quiz'); ?>
        </p>

            <div class="eas-speaking-countdown"
     style="display:none;">

    <div class="eas-progress-label">
        <?php esc_html_e('Preparing...', 'eas-quiz'); ?>
    </div>

    <div class="eas-progress-bar">

        <div class="eas-progress-fill"></div>

    </div>

    <div class="eas-progress-time">
        <?php esc_html_e('0 seconds remaining', 'eas-quiz'); ?>
    </div>

</div>



        <p class="eas-speaking-status">

            <!-- Status is populated by JavaScript. -->

        </p>



        <input type="hidden"

               class="eas-audio-file"

               name="question_<?php echo esc_attr($question['id']); ?>"

               value="">



    </div>

</div>