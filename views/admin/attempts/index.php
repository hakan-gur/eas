<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<p>

<a class="button"
href="<?php echo esc_url(admin_url(
    'admin.php?page=eas-quizzes'
)); ?>">

← <?php esc_html_e('Back to Quizzes', 'eas-quiz'); ?>

</a>

</p>
<?php if (empty($attempts)) : ?>

<p>
<?php esc_html_e('No completed attempts found.', 'eas-quiz'); ?>
</p>


<?php else : ?>
<div class="wrap">

<h1>
<?php esc_html_e('Attempts', 'eas-quiz'); ?>
</h1>
<form method="get">

<input type="hidden"
       name="page"
       value="eas-attempts">

<label style="margin-left:20px;">

<?php esc_html_e('Status:', 'eas-quiz'); ?>

</label>


<select name="status">

<option value="">
<?php esc_html_e('All', 'eas-quiz'); ?>
</option>


<option value="completed"
<?php selected(
    $status,
    'completed'
); ?>>

<?php esc_html_e('Completed', 'eas-quiz'); ?>

</option>


</select>



<?php submit_button(
    __('Filter', 'eas-quiz'),
    'secondary',
    '',
    false
); ?>


</form>


<br>
<table class="widefat">


<thead>

<tr>

<th><?php esc_html_e('Student', 'eas-quiz'); ?></th>
<th><?php esc_html_e('Quiz', 'eas-quiz'); ?></th>
<th><?php esc_html_e('Status', 'eas-quiz'); ?></th>
<th><?php esc_html_e('Score', 'eas-quiz'); ?></th>
<th><?php esc_html_e('Started', 'eas-quiz'); ?></th>
<th><?php esc_html_e('Completed', 'eas-quiz'); ?></th>
<th><?php esc_html_e('Duration', 'eas-quiz'); ?></th>
<th><?php esc_html_e('Action', 'eas-quiz'); ?></th>
</tr>

</thead>



<tbody>


<?php foreach ($attempts as $attempt) : ?>


<tr>


<td>
<?php echo esc_html($attempt['display_name']); ?>
</td>


<td>
<?php echo esc_html($attempt['quiz_title']); ?>
</td>


<td>

<?php if ($attempt['grading_status'] === 'Needs grading') : ?>

<span style="
display:inline-block;
padding:4px 10px;
background:#fff3cd;
color:#856404;
border:1px solid #ffeeba;
border-radius:12px;
font-weight:600;
font-size:12px;
">
🟡 <?php esc_html_e('Needs grading', 'eas-quiz'); ?>
</span>

<?php else : ?>

<span style="
display:inline-block;
padding:4px 10px;
background:#d4edda;
color:#155724;
border:1px solid #c3e6cb;
border-radius:12px;
font-weight:600;
font-size:12px;
">
🟢 <?php esc_html_e('Graded', 'eas-quiz'); ?>
</span>

<?php endif; ?>

</td>


<td>

<?php echo esc_html(

number_format_i18n(
    (float) $attempt['score'],
    1
)

); ?>

/

<?php echo esc_html(

number_format_i18n(
    (float) $attempt['max_points'],
    1
)

); ?>


<br>


<?php

if ((float) $attempt['max_points'] > 0) {

    echo esc_html(

number_format_i18n(
    (
        (float) $attempt['score']
        /
        (float) $attempt['max_points']
    ) * 100,
    0
)

    );

    echo '%';

}

?>

</td>
<td>

<?php echo esc_html(
    $attempt['started_at']
); ?>

</td>


<td>

<?php echo esc_html(
    $attempt['completed_at']
); ?>

</td>


<td>

<?php echo esc_html(
    $attempt['duration']
); ?>

</td>

<td>
<?php if ($attempt['grading_status'] === 'Needs grading') : ?>
<a class="button button-primary"
   href="<?php echo esc_url(admin_url(
'admin.php?page=eas-attempt-grade&attempt_id=' . $attempt['id']
)); ?>">
📝 <?php esc_html_e('Grade', 'eas-quiz'); ?>
</a>
<?php else : ?>
<a class="button"
   href="<?php echo esc_url(admin_url(
'admin.php?page=eas-attempt-grade&attempt_id=' . $attempt['id']
)); ?>">
👁 <?php esc_html_e('Review', 'eas-quiz'); ?>
</a>
<?php endif; ?>

<?php
$delete_url = wp_nonce_url(
    admin_url(
        'admin-post.php?action=eas_delete_attempt&id='
        . (int) $attempt['id']
    ),
    'eas_delete_attempt_' . (int) $attempt['id']
);
?>

<a class="button"
   style="margin-left:5px;"
   href="<?php echo esc_url($delete_url); ?>"
   onclick="return confirm('<?php echo esc_js(__('Are you sure you want to permanently delete this student attempt? All answers and associated audio recordings will also be deleted.', 'eas-quiz')); ?>');">
    🗑 <?php esc_html_e('Delete', 'eas-quiz'); ?>
</a>

</td>

</tr>


<?php endforeach; ?>


</tbody>


</table>

</div>
<?php endif; ?>
