<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="eas-question">
<h3>
<?php echo wp_kses_post($question['question_text']); ?>
</h3>
<?php if (!empty($options)): ?>
<div class="eas-options">
<?php foreach ($options as $option): ?>
<p>
<label>
<input type="radio"
       name="question_<?php echo esc_attr($question['id']); ?>"
       value="<?php echo esc_attr($option['id']); ?>"
       <?php
       checked(
           (string) ($response['answer'] ?? ''),
           (string) $option['id']
       );
       ?>>
<?php echo esc_html($option['answer_text']); ?>
</label>
</p>
<?php endforeach; ?>
</div>
<?php else: ?>
<p>
<?php esc_html_e('No answers found.', 'eas-quiz'); ?>
</p>
<?php endif; ?>
</div>