<?php

if (!defined('ABSPATH')) {
    exit;
}

$is_edit = !empty($quiz);

?>

<div class="wrap">

<h1>
<?php
printf(
    '%s',
    esc_html(
        $is_edit
            ? __('Edit Quiz', 'eas-quiz')
            : __('Add New Quiz', 'eas-quiz')
    )
);
?>
</h1>

<form method="post"
      action="<?php echo esc_url(admin_url('admin-post.php')); ?>">

<input type="hidden"
       name="action"
       value="eas_save_quiz">

<?php if ($is_edit): ?>

<input type="hidden"
       name="quiz_id"
       value="<?php echo esc_attr($quiz['id']); ?>">

<?php endif; ?>

<?php
wp_nonce_field(
    'eas_save_quiz'
);
?>

<table class="form-table">

<tr>
<th>
<label><?php esc_html_e('Title', 'eas-quiz'); ?></label>
</th>
<td>
<input type="text"
       name="title"
       class="regular-text"
       value="<?php
       echo $is_edit
           ? esc_attr($quiz['title'])
           : '';
       ?>"
       required>
</td>
</tr>

<tr>
<th>
<label><?php esc_html_e('Description', 'eas-quiz'); ?></label>
</th>
<td>
<?php
wp_editor(
    $is_edit
        ? $quiz['description']
        : '',
    'description',
    array(
        'textarea_name' => 'description',
        'media_buttons' => true,
        'textarea_rows' => 10
    )
);
?>
</td>
</tr>

<tr>
<th>
<label><?php esc_html_e('Attempts Allowed (0 for Unlimited)', 'eas-quiz'); ?></label>
</th>
<td>
<input type="number"
       name="attempts_allowed"
       value="<?php
       echo $is_edit
           ? esc_attr($quiz['attempts_allowed'])
           : 1;
       ?>"
       min="0">
</td>
</tr>

<tr>
<th>
<label><?php esc_html_e('Time Limit (minutes)', 'eas-quiz'); ?></label>
</th>
<td>
<input type="number"
       name="time_limit"
       value="<?php
       echo $is_edit
           ? esc_attr($quiz['time_limit'])
           : 0;
       ?>"
       min="0">
</td>
</tr>

<tr>
<th>
<label><?php esc_html_e('Backward Navigation', 'eas-quiz'); ?></label>
</th>
<td>
<input type="checkbox"
       name="backward_navigation"
<?php
if (
    !$is_edit ||
    $quiz['backward_navigation']
) {
    echo ' checked';
}
?>
>
<?php esc_html_e('Allow moving back between pages', 'eas-quiz'); ?>
</td>
</tr>

<tr>
<th>
<label><?php esc_html_e('Shuffle Options', 'eas-quiz'); ?></label>
</th>
<td>
<input type="checkbox"
       name="shuffle_options"
<?php
if (
    $is_edit
    &&
    !empty($quiz['shuffle_options'])
) {
    echo ' checked';
}
?>
>
<?php esc_html_e('Shuffle multiple-choice options', 'eas-quiz'); ?>
</td>
</tr>

</table>

<?php
submit_button(
    $is_edit
        ? __('Update Quiz', 'eas-quiz')
        : __('Save Quiz', 'eas-quiz')
);
?>

</form>

</div>