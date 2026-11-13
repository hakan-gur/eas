<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included view variables are local rendering data, not plugin globals.
if (!defined('ABSPATH')) {
    exit;
}

$settings = json_decode(
    $question['settings'] ?? '',
    true
);
$min_lines = 10;

if (is_array($settings)) {

    $min_lines =
        (int) ($settings['essay_min_lines'] ?? 10);

}
?>


<div class="eas-question" data-paste-error="<?php echo esc_attr__('Paste is not allowed by your browser.', 'eas-quiz'); ?>">


<h3>

<?php echo wp_kses_post($question['question_text']); ?>

</h3>



<div class="eas-essay-response">


<div class="eas-essay-toolbar">

    <button type="button" class="eas-cut">
        <?php esc_html_e('Cut', 'eas-quiz'); ?>
    </button>

    <button type="button" class="eas-copy">
        <?php esc_html_e('Copy', 'eas-quiz'); ?>
    </button>

    <button type="button" class="eas-paste">
        <?php esc_html_e('Paste', 'eas-quiz'); ?>
    </button>

</div>


<textarea
    name="question_<?php echo esc_attr($question['id']); ?>"
    class="large-text eas-essay-box"
    style="height: <?php echo (int)$min_lines * 24; ?>px;"
    placeholder="<?php echo esc_attr__('Type your answer here;', 'eas-quiz'); ?>"><?php
echo esc_textarea(
    $response['answer'] ?? ''
);
?></textarea>
<div class="eas-word-count"
     style="text-align:center; margin-top:5px; color:#666;">

    <?php esc_html_e('Words:', 'eas-quiz'); ?> <span>0</span>
</div>

</div>

</div>